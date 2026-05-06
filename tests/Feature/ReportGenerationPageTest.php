<?php

namespace Tests\Feature;

use App\Filament\Pages\ReportGeneration;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReportGenerationPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_finance_user_can_access_the_report_generation_page(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $response = $this->actingAs($user)->get('/admin/report-generation');

        $response->assertOk();
        $response->assertSeeText('Exportation Analytique Financière');
        $response->assertSeeText('Générer le Rapport d’Exportation');
        $response->assertSeeText('Automatisation et Planification');
        $response->assertSeeText('Plans d’exportation actifs');
    }

    public function test_user_can_generate_a_financial_report_preview(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Horizon Ventures SAS',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-REP-001',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => 'sent',
            'total' => 240000,
            'paid_total' => 0,
            'balance_due' => 240000,
            'created_by' => $user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->subDays(2)->toDateString(),
            'amount' => 120000,
            'payment_method' => 'bank transfer',
            'reference' => 'RPT-TRX-001',
            'recorded_by' => $user->id,
        ]);

        Expense::create([
            'category' => 'operations',
            'title' => 'Audit cloud spend',
            'amount' => 45000,
            'expense_date' => now()->subDay()->toDateString(),
            'payment_method' => 'card',
            'recorded_by' => $user->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(ReportGeneration::class)
            ->set('startDate', now()->subDays(30)->toDateString())
            ->set('endDate', now()->toDateString())
            ->set('exportFormat', 'pdf')
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertSet('reportReady', true)
            ->assertSeeText('INV-REP-001')
            ->assertSeeText('RPT-TRX-001');

        $generatedPath = $component->get('generatedReportPath');

        $this->assertNotSame('', $generatedPath);
        $this->assertNotSame('', $component->get('generatedDownloadUrl'));
        Storage::disk('local')->assertExists($generatedPath);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'report_generated',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_schedule_an_automatic_export_plan(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        Livewire::actingAs($user)
            ->test(ReportGeneration::class)
            ->set('autoScheduleEnabled', true)
            ->set('scheduleFrequency', 'Hebdomadaire')
            ->set('nextExecutionAt', now()->subDay()->setTime(8, 30)->format('Y-m-d\TH:i'))
            ->set('scheduleEmail', 'exports.planifies@entreprise.com')
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertSet('reportReady', true)
            ->assertSet('scheduledPlans.0.email', 'exports.planifies@entreprise.com')
            ->assertSeeText('exports.planifies@entreprise.com')
            ->assertSeeText('Hebdomadaire');

        $this->artisan('reports:run-scheduled-exports')->assertExitCode(0);

        Livewire::actingAs($user)
            ->test(ReportGeneration::class)
            ->assertSet('scheduledPlans.0.status', __('erp.reports.scheduled_statuses.active'));
    }

    public function test_accountant_ready_csv_export_contains_ledger_and_audit_sections(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Audit Ledger SAS',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->subDays(4)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'sent',
            'total' => 300000,
            'tax_total' => 54000,
            'balance_due' => 300000,
            'created_by' => $user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->subDays(2)->toDateString(),
            'amount' => 100000,
            'payment_method' => 'bank transfer',
            'reference' => 'AUD-TRX-100',
            'recorded_by' => $user->id,
        ]);

        Expense::create([
            'category' => 'compliance',
            'title' => 'Audit légal externe',
            'amount' => 40000,
            'expense_date' => now()->subDay()->toDateString(),
            'payment_method' => 'card',
            'recorded_by' => $user->id,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'manual_audit_check',
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'meta_json' => ['note' => 'Audit sample'],
        ]);

        $component = Livewire::actingAs($user)
            ->test(ReportGeneration::class)
            ->set('startDate', now()->subDays(30)->toDateString())
            ->set('endDate', now()->toDateString())
            ->set('exportFormat', 'csv')
            ->set('selectedModules.audit', true)
            ->set('selectedModules.expenses', true)
            ->set('selectedModules.payments', true)
            ->set('selectedModules.taxes', true)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertSet('reportReady', true);

        $generatedPath = $component->get('generatedReportPath');
        $content = Storage::disk('local')->get($generatedPath);

        $this->assertStringContainsString('Journal comptable prêt pour audit', $content);
        $this->assertStringContainsString('Date;Type de pièce;Référence;Tiers;Description;Débit;Crédit;Taxes;Solde dû;Statut', $content);
        $this->assertStringContainsString('Piste d\'audit', $content);
        $this->assertStringContainsString((string) $invoice->invoice_number, $content);
        $this->assertStringContainsString('AUD-TRX-100', $content);
        $this->assertStringContainsString('manual_audit_check', $content);
    }

    public function test_cleanup_command_removes_expired_report_exports(): void
    {
        $path = 'reports/legacy/expired-export.csv';

        Storage::disk('local')->put($path, 'legacy-data');
        touch(Storage::disk('local')->path($path), now()->subDays(40)->timestamp);
        config()->set('erp.enterprise.report_retention_days', 7);

        $this->artisan('reports:cleanup-exports')->assertExitCode(0);

        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_excel_export_includes_whatsapp_and_client_engagement_analytics(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Engagement Studio',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-ENG-001',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'sent',
            'total' => 180000,
            'balance_due' => 180000,
            'created_by' => $user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->subDay()->toDateString(),
            'amount' => 90000,
            'payment_method' => 'bank transfer',
            'reference' => 'ENG-TRX-001',
            'recorded_by' => $user->id,
        ]);

        $conversation = WhatsappConversation::create([
            'client_id' => $client->id,
            'chat_id' => '223700000001@s.whatsapp.net',
            'contact_name' => 'Engagement Studio',
            'status' => 'open',
            'last_message_at' => now()->subHours(2),
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'message_id' => 'wamid-analytics-1',
            'direction' => 'inbound',
            'type' => 'text',
            'body' => 'Need monthly report',
            'ack_status' => 'read',
            'sent_at' => now()->subHours(1),
            'read_at' => now()->subMinutes(30),
        ]);

        $component = Livewire::actingAs($user)
            ->test(ReportGeneration::class)
            ->set('startDate', now()->subDays(30)->toDateString())
            ->set('endDate', now()->toDateString())
            ->set('exportFormat', 'excel')
            ->set('selectedModules.whatsapp', true)
            ->set('selectedModules.engagement', true)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertSet('reportReady', true);

        $generatedPath = $component->get('generatedReportPath');
        $content = Storage::disk('local')->get($generatedPath);

        $this->assertStringEndsWith('.xls', $generatedPath);
        $this->assertStringContainsString('WhatsApp Analytics', $content);
        $this->assertStringContainsString('Client Engagement Analytics', $content);
        $this->assertStringContainsString('Flux de trésorerie', $content);
        $this->assertStringContainsString('Taux d&#039;engagement client', $content);
    }
}
