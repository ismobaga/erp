<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
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
        $this->setUpCompany(Company::create([
            'name' => 'CROMMIX MALI S.A.',
            'email' => 'contact@crommix-mali.com',
            'phone' => '+223 20 22 45 88',
            'address' => 'Zone Industrielle, Rue 14',
            'city' => 'Bamako',
            'country' => 'Mali',
            'currency' => 'FCFA',
            'nif' => '1234567890',
            'rccm' => 'Ma.Bko.12323',
            'nina' => '0987654321N',
            'invoice_default_notes' => 'Paiement dû sous 30 jours.',
            'is_active' => true,
        ]));

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
        $this->setUpCompany(Company::create([
            'name' => 'CROMMIX MALI S.A.',
            'currency' => 'FCFA',
            'is_active' => true,
        ]));

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

    public function test_invoice_pdf_uses_compact_mode_for_short_documents_and_disables_it_for_long_documents(): void
    {
        $this->setUpCompany(Company::create([
            'name' => 'CROMMIX MALI S.A.',
            'currency' => 'FCFA',
            'is_active' => true,
        ]));

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Compact Mode Test Client',
            'status' => 'active',
        ]);

        $compactInvoice = Invoice::create([
            'invoice_number' => 'INV-COMPACT-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'notes' => 'Short note.',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $compactInvoice->id,
            'description' => 'Compact line',
            'quantity' => 1,
            'unit_price' => 15000,
        ]);

        $longInvoice = Invoice::create([
            'invoice_number' => 'INV-COMPACT-002',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'notes' => str_repeat('Long note ', 80),
            'created_by' => $user->id,
        ]);

        for ($i = 1; $i <= 7; $i++) {
            InvoiceItem::create([
                'invoice_id' => $longInvoice->id,
                'description' => 'Long line ' . $i,
                'quantity' => 1,
                'unit_price' => 2000,
            ]);
        }

        $this->actingAs($user)
            ->get(route('invoices.pdf', $compactInvoice))
            ->assertOk()
            ->assertSee('<body class="compact">', false);

        $this->actingAs($user)
            ->get(route('invoices.pdf', $longInvoice))
            ->assertOk()
            ->assertSee('<body class="">', false);
    }

    public function test_authorized_user_can_download_a_real_invoice_pdf(): void
    {
        $this->setUpCompany(Company::create([
            'name' => 'CROMMIX MALI S.A.',
            'email' => 'contact@crommix-mali.com',
            'phone' => '+223 20 22 45 88',
            'address' => 'Zone Industrielle, Rue 14',
            'city' => 'Bamako',
            'country' => 'Mali',
            'currency' => 'FCFA',
            'nif' => '1234567890',
            'rccm' => 'Ma.Bko.12323',
            'nina' => '0987654321N',
            'is_active' => true,
        ]));

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

    public function test_user_cannot_access_invoice_pdf_from_another_company(): void
    {
        $companyA = Company::create(['name' => 'Tenant A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Tenant B', 'currency' => 'FCFA', 'is_active' => true]);

        $this->setUpCompany($companyA);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $this->setUpCompany($companyB);
        $otherClient = Client::create([
            'type' => 'company',
            'company_name' => 'Other Tenant Client',
            'status' => 'active',
        ]);

        $foreignInvoice = Invoice::create([
            'invoice_number' => 'INV-FOREIGN-PDF',
            'client_id' => $otherClient->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $this->setUpCompany($companyA);

        $this->actingAs($user)
            ->get(route('invoices.pdf', $foreignInvoice))
            ->assertNotFound();
    }
}
