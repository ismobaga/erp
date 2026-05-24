<?php

namespace Tests\Feature;

use App\Actions\ConvertQuoteToInvoiceAction;
use App\Events\InvoiceIssued;
use App\Events\PaymentRecorded;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Services\InvoiceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['status' => 'active']);
        $this->user->assignRole('Finance');

        $this->client = Client::create([
            'type' => 'company',
            'company_name' => 'Test Corp',
            'status' => 'active',
        ]);
    }

    public function test_recalculate_totals_updates_invoice_from_items(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Widget',
            'quantity' => 2,
            'unit_price' => 100.00,
        ]);

        $invoice->refresh();

        // recalculateTotals is called by InvoiceItem::saved; verify via service too
        $service = app(InvoiceService::class);
        $service->recalculateTotals($invoice);

        $invoice->refresh();

        $this->assertSame('200.00', (string) $invoice->subtotal);
        $this->assertSame('200.00', (string) $invoice->total);
    }

    public function test_refresh_financials_updates_paid_total_and_balance(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 500.00,
            'total' => 500.00,
            'created_by' => $this->user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $this->client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 200.00,
            'payment_method' => 'bank transfer',
            'recorded_by' => $this->user->id,
        ]);

        $service = app(InvoiceService::class);
        $service->refreshFinancials($invoice);

        $invoice->refresh();

        $this->assertSame('200.00', (string) $invoice->paid_total);
        $this->assertSame('300.00', (string) $invoice->balance_due);
        $this->assertSame('partially_paid', $invoice->status);
    }

    public function test_issue_transitions_draft_invoice_to_sent_and_fires_event(): void
    {
        Event::fake([InvoiceIssued::class]);

        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'total' => 100.00,
            'created_by' => $this->user->id,
        ]);

        $service = app(InvoiceService::class);
        $service->issue($invoice, $this->user->id);

        $invoice->refresh();

        $this->assertSame('sent', $invoice->status);

        Event::assertDispatched(InvoiceIssued::class, function (InvoiceIssued $event) use ($invoice): bool {
            return $event->invoice->id === $invoice->id
                && $event->userId === $this->user->id;
        });
    }

    public function test_issue_does_nothing_for_already_cancelled_invoice(): void
    {
        Event::fake([InvoiceIssued::class]);

        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'cancelled',
            'total' => 100.00,
            'created_by' => $this->user->id,
        ]);

        $service = app(InvoiceService::class);
        $service->issue($invoice, $this->user->id);

        $invoice->refresh();

        $this->assertSame('cancelled', $invoice->status);
        Event::assertNotDispatched(InvoiceIssued::class);
    }

    public function test_convert_quote_to_invoice_action_creates_invoice(): void
    {
        $this->actingAs($this->user);

        $quote = Quote::create([
            'quote_number' => 'QT-TEST-001',
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 400.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'total' => 400.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => 'Service',
            'quantity' => 4,
            'unit_price' => 100.00,
            'line_total' => 400.00,
        ]);

        $action = app(ConvertQuoteToInvoiceAction::class);
        $invoice = $action->execute($quote, $this->user->id);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame($this->client->id, $invoice->client_id);
        $this->assertSame($quote->id, $invoice->quote_id);
        $this->assertSame('draft', $invoice->status);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertCount(1, $invoice->items);
    }

    public function test_apply_payment_action_dispatches_payment_recorded_event(): void
    {
        Event::fake([PaymentRecorded::class]);

        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'total' => 300.00,
            'created_by' => $this->user->id,
        ]);

        $payment = new Payment([
            'invoice_id' => $invoice->id,
            'client_id' => $this->client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 150.00,
            'payment_method' => 'cash',
            'recorded_by' => $this->user->id,
        ]);

        $action = app(\App\Actions\ApplyPaymentAction::class);
        $action->execute($payment);

        Event::assertDispatched(PaymentRecorded::class, function (PaymentRecorded $event) use ($payment): bool {
            return $event->payment->id === $payment->id;
        });
    }
}
