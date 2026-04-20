<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectDetails;
use App\Models\Client;
use App\Models\Project;
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
