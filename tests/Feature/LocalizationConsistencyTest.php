<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_invoice_listing_does_not_expose_raw_english_status_keys(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Crommix Audit',
            'status' => 'active',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-FR-001',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(2)->toDateString(),
            'status' => 'overdue',
            'total' => 150000,
            'balance_due' => 150000,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/invoices');

        $response->assertOk();
        $response->assertDontSeeText('Overdue');
        $response->assertDontSeeText('Partially paid');
    }
}
