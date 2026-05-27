<?php

namespace Tests\Feature;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Client;
use App\Models\Company;
use App\Models\Quote;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_new_companies_receive_safe_default_advanced_feature_settings(): void
    {
        $company = Company::create([
            'name' => 'Feature Default Co',
            'currency' => 'FCFA',
            'is_active' => true,
        ]);

        $this->assertSame(config('erp.company_features.defaults'), $company->fresh()->advanced_options);
        $this->assertTrue(company_feature_enabled('clients', $company));
        $this->assertFalse(company_feature_enabled('quotes', $company));
        $this->assertFalse(company_feature_enabled('unknown_advanced_module', $company));
    }

    public function test_advanced_features_are_isolated_per_company_context(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Admin');
        $this->actingAs($user);

        $companyA = Company::create([
            'name' => 'Company A',
            'currency' => 'FCFA',
            'is_active' => true,
            'advanced_options' => ['quotes' => true],
        ]);
        $companyB = Company::create([
            'name' => 'Company B',
            'currency' => 'FCFA',
            'is_active' => true,
        ]);

        $this->setUpCompany($companyA);
        $this->assertTrue(QuoteResource::shouldRegisterNavigation());

        $this->setUpCompany($companyB);
        $this->assertFalse(QuoteResource::shouldRegisterNavigation());
    }

    public function test_disabled_quote_feature_blocks_navigation_and_direct_access(): void
    {
        $company = Company::create([
            'name' => 'Disabled Quotes Co',
            'currency' => 'FCFA',
            'is_active' => true,
        ]);
        $this->setUpCompany($company);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Admin');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Feature Client',
            'status' => 'active',
        ]);
        $quote = Quote::create([
            'client_id' => $client->id,
            'quote_number' => 'QT-FEATURE-001',
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 100,
            'subtotal' => 100,
        ]);

        $this->actingAs($user);

        $this->assertFalse(QuoteResource::shouldRegisterNavigation());
        $this->get('/admin/quotes')->assertForbidden();
        $this->get(route('quotes.pdf', $quote))->assertForbidden();
    }

    public function test_enabled_quote_feature_only_applies_to_the_active_company(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Admin');

        $companyA = Company::create([
            'name' => 'Enabled Quotes Co',
            'currency' => 'FCFA',
            'is_active' => true,
            'advanced_options' => ['quotes' => true],
        ]);
        $companyB = Company::create([
            'name' => 'Disabled Quotes Co',
            'currency' => 'FCFA',
            'is_active' => true,
        ]);

        $user->companies()->syncWithoutDetaching([
            $companyA->id => ['role' => 'owner'],
            $companyB->id => ['role' => 'owner'],
        ]);

        $this->actingAs($user)
            ->withSession(['current_company_id' => $companyA->id])
            ->get('/admin/quotes')
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['current_company_id' => $companyB->id])
            ->get('/admin/quotes')
            ->assertForbidden();
    }
}
