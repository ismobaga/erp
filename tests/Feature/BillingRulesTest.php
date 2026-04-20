<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BillingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_total_and_balance_are_computed_from_items_and_payments(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'ACME', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service A',
            'quantity' => 2,
            'unit_price' => 100,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service B',
            'quantity' => 1,
            'unit_price' => 50,
        ]);

        $invoice->refresh();

        $this->assertSame('250.00', $invoice->total);
        $this->assertSame('overdue', $invoice->status);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'payment_method' => 'bank transfer',
            'recorded_by' => $user->id,
        ]);

        $invoice->refresh();

        $this->assertSame('100.00', $invoice->paid_total);
        $this->assertSame('150.00', $invoice->balance_due);
        $this->assertSame('partially_paid', $invoice->status);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 150,
            'payment_method' => 'card',
            'recorded_by' => $user->id,
        ]);

        $invoice->refresh();

        $this->assertSame('250.00', $invoice->paid_total);
        $this->assertSame('0.00', $invoice->balance_due);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_overpayment_is_blocked_unless_explicitly_allowed(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'ACME', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-002',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 100,
            'balance_due' => 100,
            'created_by' => $user->id,
        ]);

        $this->expectException(ValidationException::class);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 150,
            'payment_method' => 'cash',
            'recorded_by' => $user->id,
        ]);
    }

    public function test_unmatched_payment_can_be_auto_reconciled_to_an_open_invoice(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'ACME', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-RECON-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'sent',
            'total' => 400,
            'balance_due' => 400,
            'created_by' => $user->id,
        ]);

        $payment = Payment::create([
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 150,
            'payment_method' => 'bank transfer',
            'reference' => 'TRX-001',
            'recorded_by' => $user->id,
        ]);

        $this->assertNull($payment->invoice_id);
        $this->assertTrue($payment->reconcileAgainstOpenInvoice());

        $payment->refresh();
        $invoice->refresh();

        $this->assertSame($invoice->id, $payment->invoice_id);
        $this->assertSame('150.00', $invoice->paid_total);
        $this->assertSame('250.00', $invoice->balance_due);
        $this->assertSame('partially_paid', $invoice->status);
    }

    public function test_expired_quote_cannot_be_accepted_without_reopening(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'ACME', 'status' => 'lead']);

        $quote = Quote::create([
            'quote_number' => 'Q-001',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
            'status' => 'expired',
            'created_by' => $user->id,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => 'Website Development',
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        $quote->refresh();

        $this->assertSame('500.00', $quote->total);
        $this->assertFalse($quote->canBeAccepted());

        $quote->update([
            'status' => 'draft',
            'valid_until' => now()->addDays(7)->toDateString(),
        ]);

        $this->assertTrue($quote->canBeAccepted());
    }

    public function test_expired_quote_can_be_accepted_within_configured_grace_period(): void
    {
        config()->set('erp.quotes.expired_acceptance_grace_days', 2);

        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Grace Corp', 'status' => 'lead']);

        $quote = Quote::create([
            'quote_number' => 'Q-002',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(5)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
            'status' => 'expired',
            'created_by' => $user->id,
        ]);

        $this->assertTrue($quote->canBeAccepted(now()));
    }

    public function test_invoice_overdue_status_respects_configured_grace_period(): void
    {
        config()->set('erp.billing.overdue_grace_days', 2);

        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Grace Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-GRACE-001',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'sent',
            'total' => 100,
            'balance_due' => 100,
            'created_by' => $user->id,
        ]);

        $invoice->refreshFinancials();
        $invoice->refresh();

        $this->assertSame('sent', $invoice->status);

        config()->set('erp.billing.overdue_grace_days', 0);

        $invoice->refreshFinancials();
        $invoice->refresh();

        $this->assertSame('overdue', $invoice->status);
    }

    public function test_invoice_tax_total_uses_the_configured_region_profile_when_available(): void
    {
        config()->set('erp.tax_profiles.default.rate', 0);
        config()->set('erp.tax_profiles.countries', [
            'Senegal' => [
                'label' => 'TVA Sénégal',
                'rate' => 18,
                'mode' => 'exclusive',
                'regions' => [
                    'Dakar' => [
                        'label' => 'TVA Dakar',
                        'rate' => 20,
                    ],
                ],
            ],
        ]);

        $user = User::factory()->create();
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Tax Region Corp',
            'city' => 'Dakar',
            'country' => 'Senegal',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2026-04-10',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Regional tax service',
            'quantity' => 1,
            'unit_price' => 1000,
        ]);

        $invoice->refresh();

        $this->assertSame('200.00', $invoice->tax_total);
        $this->assertSame('1200.00', $invoice->total);
    }

    public function test_invoice_tax_total_falls_back_to_the_country_profile(): void
    {
        config()->set('erp.tax_profiles.default.rate', 0);
        config()->set('erp.tax_profiles.countries', [
            'Senegal' => [
                'label' => 'TVA Sénégal',
                'rate' => 18,
                'mode' => 'exclusive',
            ],
        ]);

        $user = User::factory()->create();
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Tax Country Corp',
            'city' => 'Thiès',
            'country' => 'Senegal',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2026-04-12',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Country tax service',
            'quantity' => 2,
            'unit_price' => 500,
        ]);

        $invoice->refresh();

        $this->assertSame('180.00', $invoice->tax_total);
        $this->assertSame('1180.00', $invoice->total);
    }

    public function test_overpayment_can_be_allowed_explicitly(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'ACME', 'status' => 'active']);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-003',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 100,
            'balance_due' => 100,
            'created_by' => $user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 150,
            'payment_method' => 'cash',
            'allow_overpayment' => true,
            'recorded_by' => $user->id,
        ]);

        $invoice->refresh();

        $this->assertSame('150.00', $invoice->paid_total);
        $this->assertSame('0.00', $invoice->balance_due);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_invoice_numbers_are_generated_from_config_and_reset_by_year(): void
    {
        config()->set('erp.billing.invoice_numbering.prefix', 'FAC');
        config()->set('erp.billing.invoice_numbering.padding', 4);
        config()->set('erp.billing.invoice_numbering.reset', 'yearly');

        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Numbering Corp', 'status' => 'active']);

        $invoice2026a = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2026-01-10',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $invoice2026b = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2026-03-18',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $invoice2027 = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2027-01-05',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->assertSame('FAC-2026-0001', $invoice2026a->invoice_number);
        $this->assertSame('FAC-2026-0002', $invoice2026b->invoice_number);
        $this->assertSame('FAC-2027-0001', $invoice2027->invoice_number);
    }

    public function test_credit_note_reduces_invoice_balance_and_is_logged(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Credit Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2026-04-15',
            'status' => 'sent',
            'created_by' => $user->id,
            'total' => 1000,
            'balance_due' => 1000,
        ]);

        $creditNote = CreditNote::create([
            'invoice_id' => $invoice->id,
            'credit_number' => 'CN-2026-001',
            'issue_date' => '2026-04-16',
            'amount' => 250,
            'reason' => 'Service discount correction',
            'status' => 'issued',
            'created_by' => $user->id,
        ]);

        $invoice->refresh();

        $this->assertSame('250.00', $invoice->discount_total);
        $this->assertSame('750.00', $invoice->total);
        $this->assertSame('750.00', $invoice->balance_due);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'credit_note_issued',
            'subject_type' => CreditNote::class,
            'subject_id' => $creditNote->id,
        ]);
    }

    public function test_credit_note_cannot_exceed_invoice_total(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Credit Guard Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2026-04-15',
            'status' => 'sent',
            'created_by' => $user->id,
            'total' => 300,
            'balance_due' => 300,
        ]);

        $this->expectException(ValidationException::class);

        CreditNote::create([
            'invoice_id' => $invoice->id,
            'credit_number' => 'CN-2026-002',
            'issue_date' => '2026-04-16',
            'amount' => 500,
            'reason' => 'Invalid over-credit',
            'status' => 'issued',
            'created_by' => $user->id,
        ]);
    }

    public function test_large_credit_note_requires_approval_before_affecting_balance(): void
    {
        config()->set('erp.billing.credit_note_auto_issue_limit', 100);

        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Approval Credit Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2026-04-15',
            'status' => 'sent',
            'created_by' => $user->id,
            'total' => 1000,
            'balance_due' => 1000,
        ]);

        $creditNote = CreditNote::create([
            'invoice_id' => $invoice->id,
            'credit_number' => 'CN-2026-003',
            'issue_date' => '2026-04-16',
            'amount' => 250,
            'reason' => 'Major service adjustment',
            'status' => 'issued',
            'created_by' => $user->id,
        ]);

        $invoice->refresh();
        $creditNote->refresh();

        $this->assertSame('pending_approval', $creditNote->status);
        $this->assertSame('0.00', $invoice->discount_total);
        $this->assertSame('1000.00', $invoice->total);
        $this->assertSame('1000.00', $invoice->balance_due);
    }

    public function test_non_cash_payment_requires_a_reference(): void
    {
        config()->set('erp.billing.payment_reference_required_methods', ['bank transfer', 'card']);

        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Ref Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
            'total' => 400,
            'balance_due' => 400,
        ]);

        $this->expectException(ValidationException::class);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'payment_method' => 'bank transfer',
            'recorded_by' => $user->id,
        ]);
    }

    public function test_cancelled_invoice_cannot_accept_new_payments(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Cancelled Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'cancelled',
            'created_by' => $user->id,
            'total' => 400,
            'balance_due' => 400,
        ]);

        $this->expectException(ValidationException::class);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'payment_method' => 'cash',
            'reference' => 'PMT-001',
            'recorded_by' => $user->id,
        ]);
    }

    public function test_invoice_number_cannot_be_changed_once_assigned(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['type' => 'company', 'company_name' => 'Immutable Corp', 'status' => 'active']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => '2026-04-15',
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $this->expectException(ValidationException::class);

        $invoice->update([
            'invoice_number' => 'MANUAL-OVERRIDE-001',
        ]);
    }
}
