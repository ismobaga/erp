<?php

namespace Tests\Feature;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Expense;
use App\Models\FinancialPeriod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialPeriodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_period_tracks_date_range_and_status(): void
    {
        $period = FinancialPeriod::create([
            'name' => 'April 2026',
            'code' => '2026-04',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'status' => 'open',
        ]);

        $this->assertTrue($period->isOpen());
        $this->assertTrue($period->containsDate('2026-04-15'));
        $this->assertFalse($period->containsDate('2026-05-01'));
    }

    public function test_financial_period_can_be_closed_and_reopened(): void
    {
        $user = User::factory()->create();

        $period = FinancialPeriod::create([
            'name' => 'Q2 2026',
            'code' => '2026-Q2',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-06-30',
        ]);

        $period->close($user->id, 'Month-end validated');

        $this->assertTrue($period->fresh()->isClosed());
        $this->assertSame($user->id, $period->fresh()->closed_by);
        $this->assertNotNull($period->fresh()->closed_at);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'financial_period_closed',
            'subject_type' => FinancialPeriod::class,
            'subject_id' => $period->id,
        ]);

        $period->fresh()->reopen($user->id, 'Adjustment required');

        $this->assertTrue($period->fresh()->isOpen());
        $this->assertSame($user->id, $period->fresh()->reopened_by);
        $this->assertNotNull($period->fresh()->reopened_at);
        $this->assertSame('Adjustment required', $period->fresh()->notes);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'financial_period_reopened',
            'subject_type' => FinancialPeriod::class,
            'subject_id' => $period->id,
        ]);
    }

    public function test_invoice_updates_are_blocked_when_the_accounting_period_is_closed(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Lock Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-LOCK-001',
            'client_id' => $client->id,
            'issue_date' => '2026-04-10',
            'due_date' => '2026-04-25',
            'status' => 'sent',
            'total' => 100,
            'balance_due' => 100,
            'created_by' => $user->id,
        ]);

        FinancialPeriod::create([
            'name' => 'April 2026',
            'code' => 'LOCK-APR-2026',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'status' => 'closed',
        ]);

        $this->expectException(ValidationException::class);

        $invoice->update(['notes' => 'Should be blocked']);
    }

    public function test_payment_and_expense_updates_are_blocked_when_the_accounting_period_is_closed(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Lock Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-LOCK-002',
            'client_id' => $client->id,
            'issue_date' => '2026-04-05',
            'due_date' => '2026-04-20',
            'status' => 'sent',
            'total' => 300,
            'balance_due' => 300,
            'created_by' => $user->id,
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => '2026-04-12',
            'amount' => 100,
            'payment_method' => 'bank transfer',
            'recorded_by' => $user->id,
        ]);

        $expense = Expense::create([
            'category' => 'operations',
            'title' => 'Cloud hosting',
            'amount' => 80,
            'expense_date' => '2026-04-18',
            'recorded_by' => $user->id,
        ]);

        FinancialPeriod::create([
            'name' => 'April 2026',
            'code' => 'LOCK-APR-2026-OPS',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'status' => 'closed',
        ]);

        try {
            $payment->update(['reference' => 'LOCKED-REF']);
            $this->fail('Expected payment update to be blocked for a closed period.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('closed accounting period', $exception->errors()['financial_period'][0] ?? '');
        }

        try {
            $expense->update(['reference' => 'LOCKED-EXP']);
            $this->fail('Expected expense update to be blocked for a closed period.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('closed accounting period', $exception->errors()['financial_period'][0] ?? '');
        }
    }

    public function test_invoice_payment_and_expense_deletions_are_blocked_when_the_period_is_closed(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Delete Lock Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-LOCK-DELETE',
            'client_id' => $client->id,
            'issue_date' => '2026-04-08',
            'due_date' => '2026-04-28',
            'status' => 'sent',
            'total' => 220,
            'balance_due' => 220,
            'created_by' => $user->id,
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => '2026-04-12',
            'amount' => 50,
            'payment_method' => 'cash',
            'recorded_by' => $user->id,
        ]);

        $expense = Expense::create([
            'category' => 'operations',
            'title' => 'Office internet',
            'amount' => 60,
            'expense_date' => '2026-04-14',
            'recorded_by' => $user->id,
        ]);

        FinancialPeriod::create([
            'name' => 'April 2026',
            'code' => 'LOCK-APR-2026-DELETE',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'status' => 'closed',
        ]);

        foreach ([
            [$invoice, 'invoice'],
            [$payment, 'payment'],
            [$expense, 'expense'],
        ] as [$record, $label]) {
            try {
                $record->delete();
                $this->fail("Expected {$label} deletion to be blocked for a closed period.");
            } catch (ValidationException $exception) {
                $this->assertStringContainsString('closed accounting period', $exception->errors()['financial_period'][0] ?? '');
            }
        }
    }

    public function test_locked_period_records_are_read_only_in_filament_resources(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');
        $this->actingAs($user);

        $client = Client::create(['type' => 'company', 'company_name' => 'UI Lock Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-LOCK-UI',
            'client_id' => $client->id,
            'issue_date' => '2026-04-09',
            'due_date' => '2026-04-29',
            'status' => 'sent',
            'total' => 140,
            'balance_due' => 140,
            'created_by' => $user->id,
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => '2026-04-10',
            'amount' => 40,
            'payment_method' => 'cash',
            'recorded_by' => $user->id,
        ]);

        $expense = Expense::create([
            'category' => 'operations',
            'title' => 'Locked UI expense',
            'amount' => 30,
            'expense_date' => '2026-04-11',
            'recorded_by' => $user->id,
        ]);

        FinancialPeriod::create([
            'name' => 'April 2026',
            'code' => 'LOCK-APR-2026-UI',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'status' => 'closed',
        ]);

        $this->assertFalse(InvoiceResource::canEdit($invoice));
        $this->assertFalse(InvoiceResource::canDelete($invoice));
        $this->assertFalse(PaymentResource::canEdit($payment));
        $this->assertFalse(PaymentResource::canDelete($payment));
        $this->assertFalse(ExpenseResource::canEdit($expense));
        $this->assertFalse(ExpenseResource::canDelete($expense));
    }

    public function test_admin_can_apply_a_locked_period_override_with_audit_trace(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Admin');
        $this->actingAs($user);

        $client = Client::create(['type' => 'company', 'company_name' => 'Override Corp', 'status' => 'active']);

        $period = FinancialPeriod::create([
            'name' => 'April 2026',
            'code' => 'LOCK-APR-2026-OVERRIDE',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'status' => 'closed',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-LOCK-OVERRIDE',
            'client_id' => $client->id,
            'issue_date' => '2026-04-12',
            'due_date' => '2026-04-22',
            'status' => 'sent',
            'total' => 180,
            'balance_due' => 180,
            'created_by' => $user->id,
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => '2026-04-13',
            'amount' => 60,
            'payment_method' => 'bank transfer',
            'recorded_by' => $user->id,
        ]);

        $expense = Expense::create([
            'category' => 'operations',
            'title' => 'Override expense',
            'amount' => 45,
            'expense_date' => '2026-04-14',
            'recorded_by' => $user->id,
        ]);

        $this->assertTrue(InvoiceResource::canEdit($invoice));
        $this->assertTrue(PaymentResource::canEdit($payment));
        $this->assertTrue(ExpenseResource::canDelete($expense));

        $invoice->update(['notes' => 'Approved finance override']);
        $payment->update(['reference' => 'OVERRIDE-REF']);
        $expense->delete();

        $this->assertSame('Approved finance override', $invoice->fresh()->notes);
        $this->assertSame('OVERRIDE-REF', $payment->fresh()->reference);
        $this->assertModelMissing($expense);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'financial_period_override_used',
            'subject_type' => FinancialPeriod::class,
            'subject_id' => $period->id,
        ]);
    }
}
