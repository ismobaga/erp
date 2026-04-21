<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\LedgerAccountsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
