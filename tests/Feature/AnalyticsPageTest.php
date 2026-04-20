<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\FinancialPeriod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_finance_user_can_access_the_analytics_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Crommix Client',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-AN-001',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Analytics retainer',
            'quantity' => 2,
            'unit_price' => 100000,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 50000,
            'payment_method' => 'bank transfer',
            'reference' => 'AN-TRX-1',
            'recorded_by' => $user->id,
        ]);

        Expense::create([
            'category' => 'operations',
            'title' => 'Cloud services',
            'amount' => 25000,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'card',
            'recorded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/financial-insights');

        $response->assertOk();
        $response->assertSee('Analyses financières');
        $response->assertSee('Efficacité de recouvrement');
        $response->assertSee('Transactions récentes importantes');
    }

    public function test_period_selector_filters_live_transactions_for_the_selected_range(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $recentClient = Client::create([
            'type' => 'company',
            'company_name' => 'Recent Labs',
            'status' => 'active',
        ]);

        $legacyClient = Client::create([
            'type' => 'company',
            'company_name' => 'Legacy Systems',
            'status' => 'active',
        ]);

        $recentInvoice = Invoice::create([
            'invoice_number' => 'INV-AN-RECENT',
            'client_id' => $recentClient->id,
            'issue_date' => now()->subDays(8)->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $recentInvoice->id,
            'description' => 'Monthly growth analytics',
            'quantity' => 1,
            'unit_price' => 150000,
        ]);

        Payment::create([
            'invoice_id' => $recentInvoice->id,
            'client_id' => $recentClient->id,
            'payment_date' => now()->subDays(3)->toDateString(),
            'amount' => 100000,
            'payment_method' => 'bank transfer',
            'reference' => 'LIVE-RECENT',
            'recorded_by' => $user->id,
        ]);

        $legacyInvoice = Invoice::create([
            'invoice_number' => 'INV-AN-LEGACY',
            'client_id' => $legacyClient->id,
            'issue_date' => now()->subDays(150)->toDateString(),
            'due_date' => now()->subDays(120)->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $legacyInvoice->id,
            'description' => 'Historical reporting',
            'quantity' => 1,
            'unit_price' => 210000,
        ]);

        Payment::create([
            'invoice_id' => $legacyInvoice->id,
            'client_id' => $legacyClient->id,
            'payment_date' => now()->subDays(140)->toDateString(),
            'amount' => 210000,
            'payment_method' => 'bank transfer',
            'reference' => 'LIVE-OLD',
            'recorded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/financial-insights?period=30d');

        $response->assertOk();
        $response->assertSee('30 derniers jours');
        $response->assertSee('Recent Labs');
        $response->assertDontSee('Legacy Systems');
    }

    public function test_dashboard_surfaces_accounting_period_overview(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        FinancialPeriod::create([
            'name' => 'April 2026',
            'code' => 'DASH-APR-2026',
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        FinancialPeriod::create([
            'name' => 'March 2026',
            'code' => 'DASH-MAR-2026',
            'starts_on' => now()->subMonth()->startOfMonth()->toDateString(),
            'ends_on' => now()->subMonth()->endOfMonth()->toDateString(),
            'status' => 'closed',
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertSee('Périodes comptables');
        $response->assertSee('Périodes ouvertes');
        $response->assertSee('Périodes clôturées');
    }
}
