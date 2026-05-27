<?php

namespace App\Models;

use App\Support\DemoGuard;
use Crommix\SaaS\Contracts\HasSubscription;
use Crommix\SaaS\Models\FeatureFlag;
use Crommix\SaaS\Models\TenantBillingEvent;
use Crommix\SaaS\Models\TenantOnboarding;
use Crommix\SaaS\Models\TenantSubscription;
use Crommix\SaaS\Models\UsageQuota;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'legal_name',
    'slug',
    'email',
    'phone',
    'website',
    'address',
    'city',
    'country',
    'currency',
    'tax_number',
    'logo_path',
    'slogan',
    'bank_name',
    'bank_account_name',
    'bank_account_number',
    'bank_swift_code',
    'invoice_default_notes',
    'quote_default_notes',
    'advanced_options',
    'is_active',
    'is_demo',
    'whatsapp_device_id',
    'whatsapp_enabled',
    // White-label & SaaS fields
    'custom_domain',
    'brand_primary_color',
    'brand_secondary_color',
    'white_label_logo_path',
    'white_label_favicon_path',
    'white_label_app_name',
    'onboarded_at',
    'trial_ends_at',
    'subscription_status',
])]
class Company extends Model implements HasSubscription
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'advanced_options' => 'array',
            'bank_account_number' => 'encrypted',
            'bank_swift_code' => 'encrypted',
            'onboarded_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            $company->advanced_options = array_replace(
                config('erp.company_features.defaults', []),
                is_array($company->advanced_options) ? $company->advanced_options : []
            );
        });

        static::deleting(function (Company $company): void {
            DemoGuard::ensureCompanyDeletionAllowed($company);
        });
    }

    /**
     * Backward-compatible accessor so that views and services that previously
     * read `CompanySetting->company_name` continue to work with the unified
     * Company model without requiring view changes.
     */
    public function getCompanyNameAttribute(): string
    {
        return $this->name;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function financialPeriods(): HasMany
    {
        return $this->hasMany(FinancialPeriod::class);
    }

    public function ledgerAccounts(): HasMany
    {
        return $this->hasMany(LedgerAccount::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    // ── SaaS relationships ─────────────────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function featureFlags(): HasMany
    {
        return $this->hasMany(FeatureFlag::class);
    }

    public function usageQuotas(): HasMany
    {
        return $this->hasMany(UsageQuota::class);
    }

    public function billingEvents(): HasMany
    {
        return $this->hasMany(TenantBillingEvent::class);
    }

    public function onboarding(): HasOne
    {
        return $this->hasOne(TenantOnboarding::class);
    }

    // ── HasSubscription contract ───────────────────────────────────────────────

    public function activeSubscription(): ?TenantSubscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->latest()
            ->first();
    }

    public function isSubscribed(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function isOnTrial(): bool
    {
        $sub = $this->activeSubscription();

        return $sub !== null && $sub->isTrialing();
    }

    public function hasFeature(string $feature): bool
    {
        // Check per-tenant override first.
        $flag = $this->featureFlags()
            ->where('feature', $feature)
            ->first();

        if ($flag !== null) {
            return (bool) $flag->enabled;
        }

        // Fall back to plan features.
        $sub = $this->activeSubscription();

        return $sub !== null && $sub->plan->hasFeature($feature);
    }
}
