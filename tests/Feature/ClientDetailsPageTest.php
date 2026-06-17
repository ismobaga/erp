<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_client_details_page_is_available_inside_the_admin_panel(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Admin');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Bamako Digital Studio',
            'contact_name' => 'Awa Traoré',
            'email' => 'awa@bamako-digital.studio',
            'phone' => '+22370000000',
            'city' => 'Bamako',
            'country' => 'Mali',
            'notes' => 'Client prioritaire.',
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(ClientResource::getUrl('details', ['record' => $client]));

        $response->assertOk();
        $response->assertSeeText('Fiche client');
        $response->assertSeeText('Bamako Digital Studio');
        $response->assertSeeText('Modifier la fiche');
        $response->assertSeeText('Client prioritaire.');
    }

    public function test_filament_clients_resource_enforces_tenant_isolation(): void
    {
        $companyA = app('currentCompany');
        $companyB = Company::create([
            'name' => 'Other Tenant',
            'currency' => 'FCFA',
            'is_active' => true,
        ]);

        $this->setUpCompany($companyA);
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Admin');
        $user->companies()->attach($companyA->id, ['role' => 'admin']);

        $clientA = Client::create([
            'type' => 'company',
            'company_name' => 'Tenant A Client',
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->setUpCompany($companyB);
        $clientB = Client::create([
            'type' => 'company',
            'company_name' => 'Tenant B Client',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['current_company_id' => $companyA->id])
            ->get(ClientResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText('Tenant A Client')
            ->assertDontSeeText('Tenant B Client');

        $this->actingAs($user)
            ->withSession(['current_company_id' => $companyA->id])
            ->get(ClientResource::getUrl('details', ['record' => $clientA]))
            ->assertOk()
            ->assertSeeText('Tenant A Client');

        $this->actingAs($user)
            ->withSession(['current_company_id' => $companyA->id])
            ->get(ClientResource::getUrl('details', ['record' => $clientB]))
            ->assertNotFound();
    }
}
