<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_view_the_invoice_pdf_template(): void
    {
        CompanySetting::create([
            'company_name' => 'CROMMIX MALI S.A.',
            'email' => 'contact@crommix-mali.com',
            'phone' => '+223 20 22 45 88',
            'address' => 'Zone Industrielle, Rue 14',
            'city' => 'Bamako',
            'country' => 'Mali',
            'currency' => 'FCFA',
            'tax_number' => '1234567890',
            'invoice_default_notes' => 'Paiement dû sous 30 jours.',
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Société de Développement Urbain',
            'status' => 'active',
            'address' => 'Avenue de l\'Indépendance',
            'city' => 'Bamako',
            'country' => 'Mali',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-2024-012',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'tax_total' => 18000,
            'notes' => 'Merci pour votre confiance.',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Architectural Blueprint Revision',
            'quantity' => 1,
            'unit_price' => 100000,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertSee('INV-2024-012');
        $response->assertSee('CROMMIX MALI S.A.');
        $response->assertSee('Architectural Blueprint Revision');
        $response->assertSee('Télécharger le PDF');
        $response->assertSee('Facture');
    }

    public function test_overdue_invoice_displays_a_visible_watermark(): void
    {
        CompanySetting::create([
            'company_name' => 'CROMMIX MALI S.A.',
            'currency' => 'FCFA',
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Client en retard',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-OVERDUE-001',
            'client_id' => $client->id,
            'issue_date' => now()->subDays(40)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'status' => 'overdue',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Facture en souffrance',
            'quantity' => 1,
            'unit_price' => 75000,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertSee('EN RETARD');
        $response->assertSee('OVERDUE');
    }

    public function test_authorized_user_can_download_a_real_invoice_pdf(): void
    {
        CompanySetting::create([
            'company_name' => 'CROMMIX MALI S.A.',
            'email' => 'contact@crommix-mali.com',
            'phone' => '+223 20 22 45 88',
            'address' => 'Zone Industrielle, Rue 14',
            'city' => 'Bamako',
            'country' => 'Mali',
            'currency' => 'FCFA',
            'tax_number' => '1234567890',
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Client PDF',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-PDF-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Prestation PDF',
            'quantity' => 2,
            'unit_price' => 50000,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.pdf', ['invoice' => $invoice, 'download' => 1]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
