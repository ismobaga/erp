<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectDetails;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_project_details_page_is_available_inside_the_admin_panel(): void
    {
        [$user, $project] = $this->makeProjectContext();

        $response = $this->actingAs($user)->get('/admin/projects/' . $project->id . '/details');

        $response->assertOk();
        $response->assertSeeText('Expansion Phoenix');
        $response->assertSeeText('Détails du projet');
        $response->assertSeeText('Horizon Ventures SAS');
    }

    public function test_internal_note_field_updates_the_project(): void
    {
        [$user, $project] = $this->makeProjectContext();

        Livewire::actingAs($user)
            ->test(ViewProjectDetails::class, ['record' => $project->getKey()])
            ->set('internalNote', 'Point d’alignement confirmé avec le client.')
            ->call('saveInternalNote')
            ->assertHasNoErrors();

        $this->assertStringContainsString('Point d’alignement confirmé avec le client.', (string) $project->fresh()->notes);
    }

    public function test_project_details_page_displays_related_documents_financial_records_and_quotes(): void
    {
        [$user, $project] = $this->makeProjectContext();

        $quote = Quote::create([
            'quote_number' => 'DEV-2026-014',
            'client_id' => $project->client_id,
            'issue_date' => now()->subDays(15)->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'status' => 'sent',
            'total' => 180000,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-2026-014',
            'client_id' => $project->client_id,
            'quote_id' => $quote->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'partially_paid',
            'total' => 180000,
            'paid_total' => 80000,
            'balance_due' => 100000,
            'created_by' => $user->id,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $project->client_id,
            'payment_date' => now()->subDays(3)->toDateString(),
            'amount' => 80000,
            'payment_method' => 'bank transfer',
            'reference' => 'TRX-PHX-01',
            'recorded_by' => $user->id,
        ]);

        Attachment::create([
            'attachable_type' => Project::class,
            'attachable_id' => $project->id,
            'file_name' => 'cahier-des-charges.pdf',
            'category' => 'Contrats',
            'file_path' => 'attachments/test/cahier-des-charges.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2457600,
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/projects/' . $project->id . '/details');

        $response->assertOk();
        $response->assertSeeText('Documents');
        $response->assertSeeText('Paiements');
        $response->assertSeeText('DEV-2026-014');
        $response->assertSeeText('INV-2026-014');
        $response->assertSeeText('TRX-PHX-01');
        $response->assertSeeText('cahier-des-charges.pdf');
    }

    protected function makeProjectContext(): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Project Manager');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Horizon Ventures SAS',
            'contact_name' => 'Marc-Antoine Valois',
            'email' => 'm.valois@horizon-v.com',
            'city' => 'Paris',
            'country' => 'France',
            'status' => 'active',
        ]);

        $service = Service::create([
            'name' => 'SEO & Visibilité',
            'category' => 'Marketing',
            'default_price' => 150000,
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'name' => 'Expansion Phoenix',
            'description' => 'Projet stratégique de visibilité digitale.',
            'status' => 'in_progress',
            'start_date' => now()->subDays(30)->toDateString(),
            'due_date' => now()->addDays(142)->toDateString(),
            'assigned_to' => $user->id,
            'created_by' => $user->id,
        ]);

        return [$user, $project];
    }
}
