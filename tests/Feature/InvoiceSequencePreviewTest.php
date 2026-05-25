<?php

namespace Tests\Feature;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceSequencePreviewTest extends TestCase
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
            'company_name' => 'Sequence Test Client',
            'status' => 'active',
        ]);
    }

    public function test_opening_create_invoice_form_does_not_increment_sequence(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/invoices/create')
            ->assertOk();

        $this->assertDatabaseCount('sequences', 0);
    }

    public function test_changing_issue_date_on_create_invoice_form_does_not_increment_sequence(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreateInvoice::class)
            ->set('data.issue_date', '2026-07-12');

        $this->assertDatabaseCount('sequences', 0);
    }

    public function test_actual_invoice_creation_increments_sequence_once(): void
    {
        Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => '2026-05-10',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $sequence = DB::table('sequences')
            ->where('company_id', app('currentCompany')->id)
            ->where('key', 'invoice')
            ->where('period', '2026')
            ->first();

        $this->assertNotNull($sequence);
        $this->assertSame(2, (int) $sequence->next_val);
    }

    public function test_selecting_quote_on_create_invoice_form_populates_linked_fields(): void
    {
        $quote = Quote::create([
            'quote_number' => 'QT-2026-POPULATE',
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'subtotal' => 1000.00,
            'discount_total' => 50.00,
            'tax_total' => 100.00,
            'total' => 1050.00,
            'notes' => 'Imported from quote',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(CreateInvoice::class)
            ->set('data.quote_id', $quote->id)
            ->assertSet('data.client_id', $this->client->id)
            ->assertSet('data.discount_total', '50.00')
            ->assertSet('data.tax_total', '100.00')
            ->assertSet('data.notes', 'Imported from quote');
    }

    public function test_selecting_missing_quote_on_create_invoice_form_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(CreateInvoice::class)
            ->set('data.quote_id', 999999);
    }

    public function test_two_invoices_receive_unique_numbers(): void
    {
        $first = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => '2026-06-01',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $second = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => '2026-06-02',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $sequence = DB::table('sequences')
            ->where('company_id', app('currentCompany')->id)
            ->where('key', 'invoice')
            ->where('period', '2026')
            ->first();

        $this->assertNotSame($first->invoice_number, $second->invoice_number);
        $this->assertSame(3, (int) $sequence->next_val);
    }
}
