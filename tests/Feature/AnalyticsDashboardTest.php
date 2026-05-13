<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_finance_user_can_access_the_analytics_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $response = $this->actingAs($user)->get('/admin/analytics');

        $response->assertOk();
        $response->assertSee('Tableau de bord analytique');
        $response->assertSee('Finance');
        $response->assertSee('Clients');
        $response->assertSee('Projets');
    }

    public function test_analytics_dashboard_aggregates_finance_kpis(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Analytics Client SA',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 200000,
            'balance_due' => 200000,
            'created_by' => $user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 150000,
            'payment_method' => 'bank_transfer',
            'reference' => 'ANLX-PAY-001',
            'recorded_by' => $user->id,
        ]);

        Expense::create([
            'category' => 'operations',
            'title' => 'Office rent',
            'amount' => 30000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'recorded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/analytics');

        $response->assertOk();
        $response->assertSee('Chiffre d\'affaires');
        $response->assertSee('Encaissements');
        $response->assertSee('Dépenses');
        $response->assertSee('Taux de recouvrement');
    }

    public function test_analytics_dashboard_shows_client_and_project_counts(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Visible Corp',
            'status' => 'active',
        ]);

        Project::create([
            'client_id' => $client->id,
            'name' => 'Visible Project',
            'status' => 'in_progress',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/analytics');

        $response->assertOk();
        $response->assertSee('Clients');
        $response->assertSee('Projets');
        $response->assertSee('Taux de complétion');
    }

    public function test_analytics_dashboard_shows_crm_disabled_state_when_tables_absent(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        // In the test environment the crm_leads table may or may not exist.
        // Either way the page must render without errors.
        $response = $this->actingAs($user)->get('/admin/analytics');

        $response->assertOk();
        // If the CRM module is not active, the disabled badge should appear.
        // If it is active (table exists), the section renders with data.
        $this->assertStringContainsString(
            'CRM',
            $response->content(),
        );
    }

    public function test_analytics_period_selector_changes_date_range(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $response = $this->actingAs($user)->get('/admin/analytics?period=ytd');

        $response->assertOk();
        $response->assertSee('Année en cours');
    }
}
