<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\User;
use App\Services\LedgerPostingService;
use Database\Seeders\LedgerAccountsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LedgerPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LedgerAccountsSeeder::class);
    }

    public function test_journal_entry_is_posted_when_invoice_becomes_sent(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 500.00,
            'tax_total' => 0.00,
            'total' => 500.00,
            'balance_due' => 500.00,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseCount('journal_entries', 0);

        $invoice->forceFill(['status' => 'sent', 'updated_by' => $user->id])->save();

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'status' => 'posted',
        ]);

        $entry = JournalEntry::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());
        $this->assertSame('posted', $entry->status);

        // Debit should be to Accounts Receivable (1100)
        $ar = LedgerAccount::where('code', '1100')->first();
        $debitLine = $entry->lines->firstWhere('account_id', $ar->id);
        $this->assertNotNull($debitLine);
        $this->assertEqualsWithDelta(500.00, (float) $debitLine->debit, 0.01);
    }

    public function test_journal_entry_is_posted_when_payment_is_recorded(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-002',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 200.00,
            'tax_total' => 0.00,
            'total' => 200.00,
            'balance_due' => 200.00,
            'created_by' => $user->id,
        ]);

        $entriesBeforePayment = JournalEntry::count();

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 200.00,
            'payment_method' => 'bank_transfer',
            'reference' => 'PAY-TESTREF',
            'recorded_by' => $user->id,
        ]);

        $this->assertGreaterThan($entriesBeforePayment, JournalEntry::count());

        $entry = JournalEntry::where('source_type', 'payment')
            ->where('source_id', $payment->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());
        $this->assertSame('posted', $entry->status);

        // Cash (1010) should be debited
        $cash = LedgerAccount::where('code', '1010')->first();
        $debitLine = $entry->lines->firstWhere('account_id', $cash->id);
        $this->assertNotNull($debitLine);
        $this->assertEqualsWithDelta(200.00, (float) $debitLine->debit, 0.01);

        // Accounts Receivable (1100) should be credited
        $ar = LedgerAccount::where('code', '1100')->first();
        $creditLine = $entry->lines->firstWhere('account_id', $ar->id);
        $this->assertNotNull($creditLine);
        $this->assertEqualsWithDelta(200.00, (float) $creditLine->credit, 0.01);
    }

    public function test_journal_entry_balances_debit_and_credit(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-003',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_total' => 0.00,
            'total' => 1000.00,
            'balance_due' => 1000.00,
            'created_by' => $user->id,
        ]);

        $invoice->forceFill(['status' => 'sent', 'updated_by' => $user->id])->save();

        $entries = JournalEntry::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->get();

        foreach ($entries as $entry) {
            $this->assertTrue(
                $entry->isBalanced(),
                "Journal entry {$entry->entry_number} is not balanced",
            );
            $this->assertEqualsWithDelta(
                $entry->totalDebit(),
                $entry->totalCredit(),
                0.005,
            );
        }
    }

    public function test_chart_of_accounts_seeder_creates_expected_accounts(): void
    {
        $this->assertDatabaseHas('ledger_accounts', ['code' => '1010', 'type' => 'asset']);
        $this->assertDatabaseHas('ledger_accounts', ['code' => '1100', 'type' => 'asset']);
        $this->assertDatabaseHas('ledger_accounts', ['code' => '4100', 'type' => 'revenue']);
        $this->assertDatabaseHas('ledger_accounts', ['code' => '2100', 'type' => 'liability']);
        $this->assertDatabaseHas('ledger_accounts', ['code' => '5300', 'type' => 'expense']);
    }

    public function test_posting_service_does_not_create_duplicate_entries_for_same_invoice_source(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-004',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 300.00,
            'tax_total' => 0.00,
            'total' => 300.00,
            'balance_due' => 300.00,
            'created_by' => $user->id,
        ]);

        $service = app(LedgerPostingService::class);

        $first = $service->postInvoice($invoice, $user->id);
        $second = $service->postInvoice($invoice, $user->id);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('journal_entries', 1);
    }

    public function test_journal_entry_is_posted_when_expense_is_approved(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $expense = Expense::create([
            'category' => 'operations',
            'title' => 'Test Expense',
            'amount' => 350.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'approval_status' => 'pending',
            'recorded_by' => $user->id,
        ]);

        $this->assertDatabaseCount('journal_entries', 0);

        $expense->approve($user, 'Approved for testing');

        $entry = JournalEntry::where('source_type', 'expense')
            ->where('source_id', $expense->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('posted', $entry->status);
        $this->assertTrue($entry->isBalanced());

        // Expense account (5300) should be debited
        $expenseAccount = LedgerAccount::where('code', '5300')->first();
        $debitLine = $entry->lines->firstWhere('account_id', $expenseAccount->id);
        $this->assertNotNull($debitLine);
        $this->assertEqualsWithDelta(350.00, (float) $debitLine->debit, 0.01);

        // Accounts Payable (2100) should be credited
        $ap = LedgerAccount::where('code', '2100')->first();
        $creditLine = $entry->lines->firstWhere('account_id', $ap->id);
        $this->assertNotNull($creditLine);
        $this->assertEqualsWithDelta(350.00, (float) $creditLine->credit, 0.01);
    }

    public function test_journal_entry_is_posted_when_credit_note_is_issued(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-CN-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 500.00,
            'tax_total' => 0.00,
            'total' => 500.00,
            'balance_due' => 500.00,
            'created_by' => $user->id,
        ]);

        $creditNote = CreditNote::create([
            'invoice_id' => $invoice->id,
            'credit_number' => 'CN-TEST-001',
            'issue_date' => now()->toDateString(),
            'amount' => 100.00,
            'reason' => 'Partial refund for testing',
            'status' => 'issued',
            'created_by' => $user->id,
        ]);

        $entry = JournalEntry::where('source_type', 'credit_note')
            ->where('source_id', $creditNote->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('posted', $entry->status);
        $this->assertTrue($entry->isBalanced());

        // Sales Revenue (4100) should be debited (revenue reversal)
        $revenue = LedgerAccount::where('code', '4100')->first();
        $debitLine = $entry->lines->firstWhere('account_id', $revenue->id);
        $this->assertNotNull($debitLine);
        $this->assertEqualsWithDelta(100.00, (float) $debitLine->debit, 0.01);

        // Accounts Receivable (1100) should be credited
        $ar = LedgerAccount::where('code', '1100')->first();
        $creditLine = $entry->lines->firstWhere('account_id', $ar->id);
        $this->assertNotNull($creditLine);
        $this->assertEqualsWithDelta(100.00, (float) $creditLine->credit, 0.01);
    }

    public function test_reversal_entry_swaps_debits_and_credits(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-REV-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 400.00,
            'tax_total' => 0.00,
            'total' => 400.00,
            'balance_due' => 400.00,
            'created_by' => $user->id,
        ]);

        $invoice->forceFill(['status' => 'sent', 'updated_by' => $user->id])->save();

        $original = JournalEntry::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->first();

        $this->assertNotNull($original);

        $service = app(LedgerPostingService::class);
        $reversal = $service->reverse($original, $user->id, 'Test reversal');

        $this->assertNotNull($reversal);
        $this->assertSame('posted', $reversal->status);
        $this->assertTrue($reversal->isBalanced());
        $this->assertSame($original->id, $reversal->reversal_of);
        $this->assertTrue($reversal->isReversal());

        // Each line in the reversal should be the opposite of the original
        $original->load('lines');
        $reversal->load('lines');

        foreach ($original->lines as $origLine) {
            $reversalLine = $reversal->lines->firstWhere('account_id', $origLine->account_id);
            $this->assertNotNull($reversalLine, "Reversal should have a line for account {$origLine->account_id}");
            $this->assertEqualsWithDelta((float) $origLine->debit, (float) $reversalLine->credit, 0.01);
            $this->assertEqualsWithDelta((float) $origLine->credit, (float) $reversalLine->debit, 0.01);
        }
    }

    public function test_reversal_of_non_posted_entry_throws_exception(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-REV-002',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 200.00,
            'tax_total' => 0.00,
            'total' => 200.00,
            'balance_due' => 200.00,
            'created_by' => $user->id,
        ]);

        // Manually create a draft entry without posting it
        $entry = JournalEntry::create([
            'entry_number' => 'JE-TEST-DRAFT',
            'entry_date' => now()->toDateString(),
            'description' => 'Draft entry',
            'status' => 'draft',
            'source_type' => null,
            'source_id' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(LedgerPostingService::class)->reverse($entry, $user->id);
    }

    public function test_reversal_is_blocked_when_entry_is_already_reversed(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-REV-003',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 500.00,
            'tax_total' => 0.00,
            'total' => 500.00,
            'balance_due' => 500.00,
            'created_by' => $user->id,
        ]);

        $invoice->forceFill(['status' => 'sent', 'updated_by' => $user->id])->save();

        $entry = JournalEntry::query()
            ->where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->first();

        $this->assertNotNull($entry);

        $service = app(LedgerPostingService::class);
        $service->reverse($entry, $user->id, 'First reversal');

        $this->expectException(ValidationException::class);

        $service->reverse($entry->fresh(), $user->id, 'Second reversal should fail');
    }

    public function test_journal_entry_line_cannot_have_both_debit_and_credit(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $entry = JournalEntry::create([
            'entry_number' => 'JE-TEST-LINE',
            'entry_date' => now()->toDateString(),
            'description' => 'Test entry',
            'status' => 'draft',
        ]);

        $account = LedgerAccount::where('code', '1010')->first();

        $this->expectException(ValidationException::class);

        $entry->lines()->create([
            'account_id' => $account->id,
            'debit' => 100.00,
            'credit' => 50.00,
            'description' => 'Invalid line',
        ]);
    }

    public function test_invoice_with_tax_creates_three_ledger_lines(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $client = Client::create(['type' => 'company', 'company_name' => 'Test Co', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-TAX-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_total' => 200.00,
            'total' => 1200.00,
            'balance_due' => 1200.00,
            'created_by' => $user->id,
        ]);

        $invoice->forceFill(['status' => 'sent', 'updated_by' => $user->id])->save();

        $entry = JournalEntry::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());

        // Should have 3 lines: AR (debit 1200), Revenue (credit 1000), Tax Payable (credit 200)
        $this->assertCount(3, $entry->lines);

        $ar = LedgerAccount::where('code', '1100')->first();
        $revenue = LedgerAccount::where('code', '4100')->first();
        $tax = LedgerAccount::where('code', '2200')->first();

        $arLine = $entry->lines->firstWhere('account_id', $ar->id);
        $revenueLine = $entry->lines->firstWhere('account_id', $revenue->id);
        $taxLine = $entry->lines->firstWhere('account_id', $tax->id);

        $this->assertNotNull($arLine);
        $this->assertEqualsWithDelta(1200.00, (float) $arLine->debit, 0.01);

        $this->assertNotNull($revenueLine);
        $this->assertEqualsWithDelta(1000.00, (float) $revenueLine->credit, 0.01);

        $this->assertNotNull($taxLine);
        $this->assertEqualsWithDelta(200.00, (float) $taxLine->credit, 0.01);
    }
}
