<?php

namespace Tests\Feature;

use App\Models\Company;
use Crommix\SaaS\Models\FeatureFlag;
use Crommix\SaaS\Models\TenantBillingEvent;
use Crommix\SaaS\Models\TenantOnboarding;
use Crommix\SaaS\Models\TenantPlan;
use Crommix\SaaS\Models\TenantSubscription;
use Crommix\SaaS\Models\UsageQuota;
use Crommix\SaaS\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantSaasTest extends TestCase
{
    use RefreshDatabase;

    private TenantManager $saas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saas = app(TenantManager::class);
    }

    // ── Plans ──────────────────────────────────────────────────────────────────

    public function test_tenant_plan_can_be_created_with_features_and_limits(): void
    {
        $plan = TenantPlan::create([
            'name'          => 'Starter',
            'slug'          => 'starter',
            'price_monthly' => 9900,
            'features'      => ['invoicing', 'crm'],
            'limits'        => ['users' => 3, 'clients' => 50],
            'trial_days'    => 14,
        ]);

        $this->assertTrue($plan->hasFeature('invoicing'));
        $this->assertFalse($plan->hasFeature('hr'));
        $this->assertSame(3, $plan->limitFor('users'));
        $this->assertSame(50, $plan->limitFor('clients'));
        $this->assertNull($plan->limitFor('storage_mb'));
    }

    // ── Subscriptions ──────────────────────────────────────────────────────────

    public function test_subscribe_creates_subscription_and_billing_event(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['features' => ['invoicing'], 'limits' => ['users' => 5]]);

        $sub = $this->saas->subscribe($company, $plan, status: 'active');

        $this->assertSame('active', $sub->status);
        $this->assertTrue($sub->isActive());
        $this->assertFalse($sub->isTrialing());

        $this->assertDatabaseHas('tenant_billing_events', [
            'company_id' => $company->id,
            'event_type' => 'subscription_created',
        ]);

        // Subscription status should be denormalised onto the company row.
        $this->assertSame('active', $company->fresh()->subscription_status);
    }

    public function test_start_trial_creates_trialing_subscription(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['trial_days' => 14]);

        $sub = $this->saas->startTrial($company, $plan);

        $this->assertSame('trialing', $sub->status);
        $this->assertTrue($sub->isTrialing());
        $this->assertNotNull($sub->trial_ends_at);
        $this->assertGreaterThan(0, $sub->daysRemaining());
    }

    public function test_cancel_marks_subscription_cancelled(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan();
        $this->saas->subscribe($company, $plan);

        $this->saas->cancel($company);

        $this->assertNull($this->saas->currentSubscription($company));
        $this->assertSame('cancelled', $company->fresh()->subscription_status);
    }

    public function test_subscribe_cancels_previous_subscription(): void
    {
        $company = $this->setUpCompany();
        $plan1   = $this->makePlan(['slug' => 'basic']);
        $plan2   = $this->makePlan(['slug' => 'pro']);

        $this->saas->subscribe($company, $plan1);
        $this->saas->subscribe($company, $plan2);

        $cancelled = TenantSubscription::where('company_id', $company->id)
            ->where('plan_id', $plan1->id)
            ->first();

        $this->assertSame('cancelled', $cancelled?->status);
        $this->assertSame('active', $this->saas->currentSubscription($company)?->status);
    }

    // ── Feature flags ──────────────────────────────────────────────────────────

    public function test_has_feature_returns_true_when_included_in_plan(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['features' => ['crm', 'invoicing']]);
        $this->saas->subscribe($company, $plan);

        $this->assertTrue($this->saas->hasFeature('crm', $company));
        $this->assertFalse($this->saas->hasFeature('hr', $company));
    }

    public function test_set_feature_flag_overrides_plan(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['features' => ['invoicing']]);
        $this->saas->subscribe($company, $plan);

        // Feature not in plan – enable via override.
        $this->saas->setFeatureFlag($company, 'hr', true);
        $this->assertTrue($this->saas->hasFeature('hr', $company));

        // Feature in plan – disable via override.
        $this->saas->setFeatureFlag($company, 'invoicing', false);
        $this->assertFalse($this->saas->hasFeature('invoicing', $company));
    }

    public function test_remove_feature_flag_falls_back_to_plan(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['features' => ['invoicing']]);
        $this->saas->subscribe($company, $plan);

        $this->saas->setFeatureFlag($company, 'invoicing', false);
        $this->assertFalse($this->saas->hasFeature('invoicing', $company));

        $this->saas->removeFeatureFlag($company, 'invoicing');
        $this->assertTrue($this->saas->hasFeature('invoicing', $company));
    }

    public function test_has_feature_returns_false_when_no_subscription(): void
    {
        $company = $this->setUpCompany();

        $this->assertFalse($this->saas->hasFeature('invoicing', $company));
    }

    // ── Usage quotas ───────────────────────────────────────────────────────────

    public function test_sync_quotas_from_plan_creates_quota_rows(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['limits' => ['users' => 5, 'clients' => 100]]);
        $this->saas->subscribe($company, $plan);

        $quota = $this->saas->quota('users', $company);

        $this->assertNotNull($quota);
        $this->assertSame(5, $quota->limit);
        $this->assertSame(0, $quota->used);
    }

    public function test_increment_usage_increases_used_counter(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['limits' => ['users' => 5]]);
        $this->saas->subscribe($company, $plan);

        $this->saas->incrementUsage('users', $company, 3);

        $quota = $this->saas->quota('users', $company);
        $this->assertSame(3, $quota->used);
        $this->assertFalse($quota->isExceeded());
    }

    public function test_quota_is_exceeded_when_used_reaches_limit(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['limits' => ['users' => 2]]);
        $this->saas->subscribe($company, $plan);

        $this->saas->incrementUsage('users', $company, 2);

        $quota = $this->saas->quota('users', $company);
        $this->assertTrue($quota->isExceeded());
        $this->assertFalse($this->saas->checkQuota('users', $company));
    }

    public function test_null_limit_means_unlimited(): void
    {
        $company = $this->setUpCompany();
        UsageQuota::create([
            'company_id' => $company->id,
            'metric'     => 'storage_mb',
            'used'       => 99999,
            'limit'      => null,
        ]);

        $quota = $this->saas->quota('storage_mb', $company);
        $this->assertFalse($quota->isExceeded());
        $this->assertNull($quota->remaining());
        $this->assertSame(0.0, $quota->percentUsed());
    }

    public function test_check_quota_returns_true_when_no_quota_row(): void
    {
        $company = $this->setUpCompany();

        $this->assertTrue($this->saas->checkQuota('api_calls_daily', $company));
    }

    // ── Onboarding ─────────────────────────────────────────────────────────────

    public function test_onboarding_record_is_created_on_first_access(): void
    {
        $company    = $this->setUpCompany();
        $onboarding = $this->saas->onboarding($company);

        $this->assertInstanceOf(TenantOnboarding::class, $onboarding);
        $this->assertSame($company->id, $onboarding->company_id);
        $this->assertFalse($onboarding->is_complete);
    }

    public function test_completing_onboarding_steps_marks_progress(): void
    {
        config()->set('saas.onboarding_steps', [
            'company_profile' => 'Complete company profile',
            'first_client'    => 'Add first client',
        ]);

        $company    = $this->setUpCompany();
        $onboarding = $this->saas->onboarding($company);

        $this->saas->completeOnboardingStep($company, 'company_profile');
        $onboarding->refresh();

        $this->assertTrue($onboarding->hasStep('company_profile'));
        $this->assertFalse($onboarding->is_complete);
        $this->assertSame(50.0, $onboarding->progressPercent());

        $this->saas->completeOnboardingStep($company, 'first_client');
        $onboarding->refresh();

        $this->assertTrue($onboarding->is_complete);
        $this->assertNotNull($onboarding->completed_at);
        $this->assertNotNull($company->fresh()->onboarded_at);
    }

    public function test_pending_steps_returns_steps_not_yet_completed(): void
    {
        config()->set('saas.onboarding_steps', [
            'company_profile' => 'Complete company profile',
            'first_client'    => 'Add first client',
            'first_invoice'   => 'Create first invoice',
        ]);

        $company    = $this->setUpCompany();
        $this->saas->completeOnboardingStep($company, 'first_client');

        $pending = $this->saas->onboarding($company)->pendingSteps();

        $this->assertContains('company_profile', $pending);
        $this->assertContains('first_invoice', $pending);
        $this->assertNotContains('first_client', $pending);
    }

    // ── Module licensing ───────────────────────────────────────────────────────

    public function test_module_is_licensed_when_feature_included_in_plan(): void
    {
        config()->set('saas.module_feature_map', ['crm' => 'crm', 'hr' => 'hr']);

        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['features' => ['crm']]);
        $this->saas->subscribe($company, $plan);

        $this->assertTrue($this->saas->isModuleLicensed('crm', $company));
        $this->assertFalse($this->saas->isModuleLicensed('hr', $company));
    }

    public function test_module_is_always_licensed_when_not_in_feature_map(): void
    {
        config()->set('saas.module_feature_map', []);

        $company = $this->setUpCompany();

        $this->assertTrue($this->saas->isModuleLicensed('any_module', $company));
    }

    // ── White-label ────────────────────────────────────────────────────────────

    public function test_company_white_label_fields_are_persisted(): void
    {
        $company = Company::create([
            'name'                  => 'White Label Corp',
            'currency'              => 'USD',
            'is_active'             => true,
            'custom_domain'         => 'app.whitelabel.com',
            'brand_primary_color'   => '#FF5733',
            'white_label_app_name'  => 'My ERP',
        ]);

        $this->assertDatabaseHas('companies', [
            'custom_domain'        => 'app.whitelabel.com',
            'brand_primary_color'  => '#FF5733',
            'white_label_app_name' => 'My ERP',
        ]);
    }

    // ── HasSubscription contract on Company ────────────────────────────────────

    public function test_company_implements_has_subscription_contract(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan(['features' => ['invoicing']]);
        $this->saas->subscribe($company, $plan);

        $company->refresh();

        $this->assertTrue($company->isSubscribed());
        $this->assertFalse($company->isOnTrial());
        $this->assertTrue($company->hasFeature('invoicing'));
        $this->assertFalse($company->hasFeature('hr'));
    }

    // ── Billing events ─────────────────────────────────────────────────────────

    public function test_billing_event_is_recorded(): void
    {
        $company = $this->setUpCompany();
        $plan    = $this->makePlan();

        $this->saas->recordBillingEvent($company, $plan, 'invoice_paid', 9900.0);

        $this->assertDatabaseHas('tenant_billing_events', [
            'company_id' => $company->id,
            'event_type' => 'invoice_paid',
            'amount'     => '9900.00',
            'status'     => 'completed',
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function makePlan(array $overrides = []): TenantPlan
    {
        static $seq = 0;
        $seq++;

        return TenantPlan::create(array_merge([
            'name'       => "Plan {$seq}",
            'slug'       => "plan-{$seq}",
            'trial_days' => 0,
            'features'   => [],
            'limits'     => [],
        ], $overrides));
    }
}
