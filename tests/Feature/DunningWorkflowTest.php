<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DunningLog;
use App\Models\Invoice;
use App\Models\User;
use App\Services\DunningService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DunningWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected DunningService $dunning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->dunning = app(DunningService::class);
    }

    public function test_resolve_stage_returns_null_for_non_overdue_invoice(): void
    {
        $invoice = $this->makeInvoice(daysOverdue: 0);

        $this->assertNull($this->dunning->resolveStage($invoice));
    }

    public function test_resolve_stage_returns_stage_1_for_early_overdue(): void
    {
        $invoice = $this->makeInvoice(daysOverdue: 7);

        $this->assertSame('1', $this->dunning->resolveStage($invoice));
    }

    public function test_resolve_stage_returns_stage_2_at_15_days(): void
    {
        $invoice = $this->makeInvoice(daysOverdue: 15);

        $this->assertSame('2', $this->dunning->resolveStage($invoice));
    }

    public function test_resolve_stage_returns_final_at_61_days(): void
    {
        $invoice = $this->makeInvoice(daysOverdue: 61);

        $this->assertSame('final', $this->dunning->resolveStage($invoice));
    }

    public function test_eligible_invoices_finds_overdue_invoices(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Dunning Co', 'status' => 'active']);

        $overdue = Invoice::create([
            'invoice_number' => 'INV-D-001',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(40)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'status' => 'overdue',
            'total' => 500.00,
            'balance_due' => 500.00,
            'created_by' => $user->id,
        ]);

        $eligible = $this->dunning->eligibleInvoices();

        $this->assertTrue($eligible->contains('id', $overdue->id));
    }

    public function test_log_reminder_creates_dunning_log_record(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Log Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-D-002',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => 'overdue',
            'total' => 300.00,
            'balance_due' => 300.00,
            'created_by' => $user->id,
        ]);

        $log = $this->dunning->logReminder($invoice, 'email', $user->id, 'Test reminder');

        $this->assertInstanceOf(DunningLog::class, $log);
        $this->assertDatabaseHas('dunning_logs', [
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'channel' => 'email',
            'sent_by' => $user->id,
        ]);
    }

    public function test_is_eligible_returns_false_after_recent_contact(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Recent Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-D-003',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => 'overdue',
            'total' => 200.00,
            'balance_due' => 200.00,
            'created_by' => $user->id,
        ]);

        $stage = $this->dunning->resolveStage($invoice);

        DunningLog::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'stage' => $stage,
            'channel' => 'email',
            'sent_at' => now(),
            'sent_by' => $user->id,
        ]);

        $this->assertFalse($this->dunning->isEligible($invoice));
    }

    public function test_days_overdue_returns_correct_count(): void
    {
        $invoice = $this->makeInvoice(daysOverdue: 14);

        $this->assertSame(14, $this->dunning->daysOverdue($invoice));
    }

    // -------------------------------------------------------------------------

    private function makeInvoice(int $daysOverdue): Invoice
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Co ' . uniqid(), 'status' => 'active']);

        return Invoice::create([
            'invoice_number' => 'INV-' . uniqid(),
            'client_id' => $client->id,
            'issue_date' => now()->subDays($daysOverdue + 30)->toDateString(),
            'due_date' => $daysOverdue > 0
                ? now()->subDays($daysOverdue)->toDateString()
                : now()->addDay()->toDateString(),
            'status' => $daysOverdue > 0 ? 'overdue' : 'sent',
            'total' => 100.00,
            'balance_due' => 100.00,
            'created_by' => $user->id,
        ]);
    }
}
