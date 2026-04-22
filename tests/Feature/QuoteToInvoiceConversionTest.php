<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteToInvoiceConversionTest extends TestCase
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
            'company_name' => 'Acme Corp',
            'status' => 'active',
        ]);
    }

    public function test_convert_to_invoice_creates_invoice_from_quote(): void
    {
        $this->actingAs($this->user);

        $quote = Quote::create([
            'quote_number' => 'QT-2026-0001',
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 1000.00,
            'discount_total' => 50.00,
            'tax_total' => 100.00,
            'total' => 1050.00,
            'notes' => 'Contrat de service annuel',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => 'Consulting mensuel',
            'quantity' => 2,
            'unit_price' => 500.00,
            'line_total' => 1000.00,
        ]);

        $invoice = $quote->convertToInvoice($this->user->id);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame($this->client->id, $invoice->client_id);
        $this->assertSame($quote->id, $invoice->quote_id);
        $this->assertSame('draft', $invoice->status);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertSame('50.00', (string) $invoice->discount_total);
        $this->assertSame('100.00', (string) $invoice->tax_total);
        $this->assertSame('Contrat de service annuel', $invoice->notes);
    }

    public function test_convert_to_invoice_copies_all_line_items(): void
    {
        $this->actingAs($this->user);

        $quote = Quote::create([
            'quote_number' => 'QT-2026-0002',
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 2000.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'total' => 2000.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        QuoteItem::create(['quote_id' => $quote->id, 'description' => 'Item A', 'quantity' => 1, 'unit_price' => 800.00, 'line_total' => 800.00]);
        QuoteItem::create(['quote_id' => $quote->id, 'description' => 'Item B', 'quantity' => 2, 'unit_price' => 600.00, 'line_total' => 1200.00]);

        $invoice = $quote->convertToInvoice($this->user->id);

        $this->assertCount(2, $invoice->items);

        $descriptions = $invoice->items->pluck('description')->toArray();
        $this->assertContains('Item A', $descriptions);
        $this->assertContains('Item B', $descriptions);
    }

    public function test_convert_to_invoice_marks_quote_as_accepted(): void
    {
        $this->actingAs($this->user);

        $quote = Quote::create([
            'quote_number' => 'QT-2026-0003',
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 500.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'total' => 500.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $quote->convertToInvoice($this->user->id);

        $this->assertSame('accepted', $quote->fresh()->status);
    }

    public function test_convert_to_invoice_writes_audit_log(): void
    {
        $this->actingAs($this->user);

        $quote = Quote::create([
            'quote_number' => 'QT-2026-0004',
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 300.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'total' => 300.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $quote->convertToInvoice($this->user->id);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'quote_converted_to_invoice',
            'subject_type' => Invoice::class,
        ]);
    }

    public function test_convert_to_invoice_is_idempotent_returns_existing_invoice(): void
    {
        $this->actingAs($this->user);

        $quote = Quote::create([
            'quote_number' => 'QT-2026-0005',
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 200.00,
            'discount_total' => 0.00,
            'tax_total' => 0.00,
            'total' => 200.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $first = $quote->convertToInvoice($this->user->id);
        $second = $quote->fresh()->convertToInvoice($this->user->id);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Invoice::where('quote_id', $quote->id)->count());
    }
}
