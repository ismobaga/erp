<?php

namespace Tests\Feature;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
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
