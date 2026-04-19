<?php

namespace Tests\Feature;

use App\Models\Client;
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
}
