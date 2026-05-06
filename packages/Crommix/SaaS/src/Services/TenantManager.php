<?php

namespace Crommix\SaaS\Services;

use App\Models\Company;
use Crommix\SaaS\Models\FeatureFlag;
use Crommix\SaaS\Models\TenantBillingEvent;
use Crommix\SaaS\Models\TenantOnboarding;
use Crommix\SaaS\Models\TenantPlan;
use Crommix\SaaS\Models\TenantSubscription;
use Crommix\SaaS\Models\UsageQuota;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Central SaaS management service.
 *
 * Provides a unified API for subscriptions, feature flags, usage quotas,
 * onboarding workflows, and billing event recording.
 */
class TenantManager
{
    // ── Subscriptions ──────────────────────────────────────────────────────────

    /**
     * Return the most recent active or trialing subscription for a company,
     * or null if none exists.
     */
    public function currentSubscription(Company $company): ?TenantSubscription
    {
        return Cache::remember(
            "saas.subscription.{$company->id}",
            now()->addMinutes(5),
            fn () => TenantSubscription::where('company_id', $company->id)
                ->whereIn('status', ['active', 'trialing'])
                ->latest()
                ->first(),
        );
    }

    /**
     * Create a new subscription for a company and record the billing event.
     * Cancels any existing active/trialing subscription first.
     */
    public function subscribe(
        Company $company,
        TenantPlan $plan,
        string $status = 'active',
        ?Carbon $periodStart = null,
        ?Carbon $periodEnd = null,
        ?string $externalReference = null,
    ): TenantSubscription {
        return DB::transaction(function () use ($company, $plan, $status, $periodStart, $periodEnd, $externalReference): TenantSubscription {
            // Cancel any current subscription.
            TenantSubscription::where('company_id', $company->id)
                ->whereIn('status', ['active', 'trialing'])
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $trialEndsAt = null;

            if ($status === 'trialing' && $plan->trial_days > 0) {
                $trialEndsAt = now()->addDays($plan->trial_days);
            }

            $subscription = TenantSubscription::create([
                'company_id'           => $company->id,
                'plan_id'              => $plan->id,
                'status'               => $status,
                'trial_ends_at'        => $trialEndsAt,
                'current_period_start' => $periodStart ?? now(),
                'current_period_end'   => $periodEnd,
                'external_reference'   => $externalReference,
            ]);

            // Sync quotas from the plan limits.
            $this->syncQuotasFromPlan($company, $plan);

            // Denormalise subscription status on company.
            $company->subscription_status = $status;
            if ($trialEndsAt !== null) {
                $company->trial_ends_at = $trialEndsAt;
            }
            $company->save();

            $this->recordBillingEvent($company, $plan, 'subscription_created');

            $this->flushSubscriptionCache($company);

            return $subscription;
        });
    }

    /**
     * Start a free trial for the company on the given plan.
     */
    public function startTrial(Company $company, TenantPlan $plan): TenantSubscription
    {
        return $this->subscribe(
            $company,
            $plan,
            status: 'trialing',
            periodEnd: now()->addDays(max(1, $plan->trial_days)),
        );
    }

    /**
     * Cancel the company's current subscription.
     */
    public function cancel(Company $company): void
    {
        TenantSubscription::where('company_id', $company->id)
            ->whereIn('status', ['active', 'trialing'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $company->subscription_status = 'cancelled';
        $company->save();

        $this->flushSubscriptionCache($company);
    }

    // ── Feature Flags ──────────────────────────────────────────────────────────

    /**
     * Check whether a feature is enabled for a company.
     *
     * Resolution order:
     *  1. Per-tenant FeatureFlag override (if a row exists).
     *  2. Plan features (if an active/trialing subscription exists).
     *  3. Returns false.
     */
    public function hasFeature(string $feature, Company $company): bool
    {
        // 1. Check for explicit per-tenant override.
        $flag = FeatureFlag::where('company_id', $company->id)
            ->where('feature', $feature)
            ->first();

        if ($flag !== null) {
            return (bool) $flag->enabled;
        }

        // 2. Check plan features.
        $subscription = $this->currentSubscription($company);

        if ($subscription !== null) {
            return $subscription->plan->hasFeature($feature);
        }

        return false;
    }

    /**
     * Set a per-tenant feature flag override.
     */
    public function setFeatureFlag(Company $company, string $feature, bool $enabled, array $metadata = []): FeatureFlag
    {
        return FeatureFlag::updateOrCreate(
            ['company_id' => $company->id, 'feature' => $feature],
            ['enabled' => $enabled, 'metadata' => $metadata ?: null],
        );
    }

    /**
     * Remove a per-tenant feature flag override (fall back to plan).
     */
    public function removeFeatureFlag(Company $company, string $feature): void
    {
        FeatureFlag::where('company_id', $company->id)
            ->where('feature', $feature)
            ->delete();
    }

    // ── Usage Quotas ───────────────────────────────────────────────────────────

    /**
     * Return the quota record for a company+metric, or null if none configured.
     */
    public function quota(string $metric, Company $company): ?UsageQuota
    {
        return UsageQuota::where('company_id', $company->id)
            ->where('metric', $metric)
            ->first();
    }

    /**
     * Whether the company has not exceeded the quota for a metric.
     * Returns true (allowed) when no quota record exists (unlimited).
     */
    public function checkQuota(string $metric, Company $company): bool
    {
        $quota = $this->quota($metric, $company);

        return $quota === null || !$quota->isExceeded();
    }

    /**
     * Increment the used counter for a metric by $by units.
     */
    public function incrementUsage(string $metric, Company $company, int $by = 1): void
    {
        UsageQuota::where('company_id', $company->id)
            ->where('metric', $metric)
            ->increment('used', $by);
    }

    /**
     * Reset the used counter for a metric to zero.
     */
    public function resetUsage(string $metric, Company $company): void
    {
        UsageQuota::where('company_id', $company->id)
            ->where('metric', $metric)
            ->update(['used' => 0, 'reset_at' => now()]);
    }

    /**
     * Sync quota records from a plan's limits JSON.
     * Creates or updates UsageQuota rows, preserving existing `used` values.
     */
    public function syncQuotasFromPlan(Company $company, TenantPlan $plan): void
    {
        $limits = (array) ($plan->limits ?? []);

        foreach ($limits as $metric => $limit) {
            UsageQuota::updateOrCreate(
                ['company_id' => $company->id, 'metric' => $metric],
                ['limit' => $limit === null ? null : (int) $limit],
            );
        }
    }

    // ── Onboarding ─────────────────────────────────────────────────────────────

    /**
     * Return (or create) the onboarding record for a company.
     */
    public function onboarding(Company $company): TenantOnboarding
    {
        return TenantOnboarding::firstOrCreate(
            ['company_id' => $company->id],
            ['completed_steps' => [], 'is_complete' => false],
        );
    }

    /**
     * Mark an onboarding step as complete.
     */
    public function completeOnboardingStep(Company $company, string $step): TenantOnboarding
    {
        $onboarding = $this->onboarding($company);
        $onboarding->completeStep($step);

        // Stamp company.onboarded_at when all steps are finished.
        if ($onboarding->is_complete && $company->onboarded_at === null) {
            $company->onboarded_at = now();
            $company->save();
        }

        return $onboarding;
    }

    // ── Billing Events ─────────────────────────────────────────────────────────

    /**
     * Record a SaaS billing event for a company.
     */
    public function recordBillingEvent(
        Company $company,
        ?TenantPlan $plan = null,
        string $eventType = 'manual',
        float $amount = 0.0,
        string $status = 'completed',
        ?string $externalReference = null,
        array $metadata = [],
    ): TenantBillingEvent {
        return TenantBillingEvent::create([
            'company_id'         => $company->id,
            'plan_id'            => $plan?->id,
            'event_type'         => $eventType,
            'amount'             => $amount,
            'currency'           => $plan?->currency ?? 'FCFA',
            'status'             => $status,
            'external_reference' => $externalReference,
            'metadata'           => $metadata ?: null,
        ]);
    }

    // ── Module Licensing ───────────────────────────────────────────────────────

    /**
     * Whether a given ERP module is licensed for the company based on the
     * module-to-feature mapping in saas.module_feature_map.
     */
    public function isModuleLicensed(string $moduleKey, Company $company): bool
    {
        $featureMap = (array) config('saas.module_feature_map', []);

        if (!array_key_exists($moduleKey, $featureMap)) {
            // No feature restriction defined → allow by default.
            return true;
        }

        return $this->hasFeature($featureMap[$moduleKey], $company);
    }

    // ── Internal helpers ───────────────────────────────────────────────────────

    private function flushSubscriptionCache(Company $company): void
    {
        Cache::forget("saas.subscription.{$company->id}");
    }
}
