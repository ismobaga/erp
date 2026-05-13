<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Crommix\SaaS\Models\TenantPlan;
use Crommix\SaaS\Models\UsageQuota;
use Crommix\SaaS\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the expanded API layer:
 *  - /v1/private/payments
 *  - /v1/private/expenses
 *  - /v1/private/quotes
 *  - /v1/private/projects
 *  - /v1/private/kpis
 *  - api.quota middleware (usage tracking + enforcement)
 */
class ApiExpansionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private string $privateToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Expansion Corp',
            'currency' => 'FCFA',
            'is_active' => true,
        ]);
        $this->setUpCompany($this->company);

        $this->user = User::factory()->create(['status' => 'active']);
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);

        $this->privateToken = ApiToken::issue(
            user: $this->user,
            company: $this->company,
            name: 'Expansion private token',
            scope: 'private',
        )['plainTextToken'];
    }

    // ── Payments ───────────────────────────────────────────────────────────────

    public function test_private_payments_index_returns_paginated_list(): void
    {
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Pay Client',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 50000,
            'balance_due' => 50000,
            'created_by' => $this->user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 25000,
            'payment_method' => 'bank_transfer',
            'reference' => 'EXP-PAY-001',
            'recorded_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_private_payments_show_returns_single_record(): void
    {
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Single Pay Client',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 30000,
            'balance_due' => 30000,
            'created_by' => $this->user->id,
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 15000,
            'payment_method' => 'cash',
            'recorded_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson("/api/v1/private/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $payment->id);
    }

    // ── Expenses ───────────────────────────────────────────────────────────────

    public function test_private_expenses_index_returns_paginated_list(): void
    {
        Expense::create([
            'category' => 'operations',
            'title' => 'Cloud infra',
            'amount' => 12000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'card',
            'recorded_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/expenses')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_private_expenses_show_returns_single_record(): void
    {
        $expense = Expense::create([
            'category' => 'travel',
            'title' => 'Flight Bamako–Dakar',
            'amount' => 45000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'recorded_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson("/api/v1/private/expenses/{$expense->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $expense->id)
            ->assertJsonPath('data.title', 'Flight Bamako–Dakar');
    }

    // ── Quotes ─────────────────────────────────────────────────────────────────

    public function test_private_quotes_index_returns_paginated_list(): void
    {
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Quote Client',
            'status' => 'active',
        ]);

        Quote::create([
            'quote_number' => 'QT-EXP-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/quotes')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_private_quotes_show_includes_line_items(): void
    {
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Quote Detail Client',
            'status' => 'active',
        ]);

        $quote = Quote::create([
            'quote_number' => 'QT-EXP-002',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $quote->items()->create([
            'description' => 'Consulting services',
            'quantity' => 3,
            'unit_price' => 75000,
            'line_total' => 225000,
        ]);

        $this->withToken($this->privateToken)
            ->getJson("/api/v1/private/quotes/{$quote->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $quote->id)
            ->assertJsonCount(1, 'data.items');
    }

    // ── Projects ───────────────────────────────────────────────────────────────

    public function test_private_projects_index_returns_paginated_list(): void
    {
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Project Client',
            'status' => 'active',
        ]);

        Project::create([
            'client_id' => $client->id,
            'name' => 'Digital Transformation',
            'status' => 'in_progress',
            'created_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_private_projects_show_returns_single_record(): void
    {
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Single Project Client',
            'status' => 'active',
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'ERP Integration',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson("/api/v1/private/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.name', 'ERP Integration');
    }

    // ── KPI Analytics endpoint ─────────────────────────────────────────────────

    public function test_kpi_endpoint_returns_cross_module_analytics(): void
    {
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'KPI Corp',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 100000,
            'balance_due' => 100000,
            'created_by' => $this->user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100000,
            'payment_method' => 'bank_transfer',
            'reference' => 'KPI-PAY-001',
            'recorded_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/kpis?'.http_build_query([
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonStructure([
                'period' => ['start', 'end'],
                'data' => ['finance', 'clients', 'projects', 'crm', 'hr'],
            ])
            ->assertJsonPath('data.finance.revenue', 100000)
            ->assertJsonPath('data.finance.collected', 100000);
    }

    public function test_kpi_endpoint_defaults_period_to_current_month(): void
    {
        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/kpis')
            ->assertOk()
            ->assertJsonPath('period.start', now()->startOfMonth()->toDateString())
            ->assertJsonPath('period.end', now()->toDateString());
    }

    public function test_kpi_clamps_inverted_dates(): void
    {
        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/kpis?start_date=2099-01-01&end_date=2026-01-01')
            ->assertOk()
            ->assertJsonPath('period.end', '2026-01-01');
    }

    // ── API quota tracking ────────────────────────────────────────────────────

    public function test_api_quota_middleware_increments_daily_api_calls(): void
    {
        $saas = app(TenantManager::class);

        $plan = TenantPlan::create([
            'name' => 'Basic',
            'slug' => 'basic-quota-test',
            'limits' => ['api_calls_daily' => 100],
        ]);

        $saas->subscribe($this->company, $plan);

        $this->assertDatabaseHas('tenant_usage_quotas', [
            'company_id' => $this->company->id,
            'metric' => 'api_calls_daily',
            'used' => 0,
        ]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/clients')
            ->assertOk();

        $this->assertDatabaseHas('tenant_usage_quotas', [
            'company_id' => $this->company->id,
            'metric' => 'api_calls_daily',
            'used' => 1,
        ]);
    }

    public function test_api_quota_middleware_rejects_requests_when_quota_exceeded(): void
    {
        $saas = app(TenantManager::class);

        $plan = TenantPlan::create([
            'name' => 'Nano',
            'slug' => 'nano-quota-test',
            'limits' => ['api_calls_daily' => 2],
        ]);

        $saas->subscribe($this->company, $plan);

        // Manually exhaust the quota.
        UsageQuota::where('company_id', $this->company->id)
            ->where('metric', 'api_calls_daily')
            ->update(['used' => 2]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/clients')
            ->assertStatus(429)
            ->assertJsonPath('metric', 'api_calls_daily');
    }
}
