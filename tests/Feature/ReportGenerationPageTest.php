<?php

namespace Tests\Feature;

use App\Filament\Pages\ReportGeneration;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
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
            ->assertSet('scheduledPlans.0.status', 'Traité récemment');
    }
}
