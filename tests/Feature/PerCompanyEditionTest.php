<?php

namespace Tests\Feature;

use App\Support\ErpEdition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerCompanyEditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_edition_falls_back_to_global_config(): void
    {
        $company = $this->setUpCompany();
        $company->update(['edition' => null]);

        config(['erp.edition.active' => 'full']);

        $this->assertSame('full', ErpEdition::current());
    }

    public function test_company_edition_overrides_global_config(): void
    {
        config(['erp.edition.active' => 'simple']);

        $company = $this->setUpCompany();
        $company->update(['edition' => 'growing']);
        // Refresh so the in-memory model reflects the DB value.
        app()->instance('currentCompany', $company->fresh());

        $this->assertSame('growing', ErpEdition::current());
    }

    public function test_isSimple_returns_false_when_company_overrides_to_full(): void
    {
        config(['erp.edition.active' => 'simple']);

        $company = $this->setUpCompany();
        $company->update(['edition' => 'full']);
        app()->instance('currentCompany', $company->fresh());

        $this->assertFalse(ErpEdition::isSimple());
    }

    public function test_isSimple_returns_true_when_company_overrides_to_simple(): void
    {
        config(['erp.edition.active' => 'full']);

        $company = $this->setUpCompany();
        $company->update(['edition' => 'simple']);
        app()->instance('currentCompany', $company->fresh());

        $this->assertTrue(ErpEdition::isSimple());
    }

    public function test_two_companies_with_different_editions_resolve_independently(): void
    {
        config(['erp.edition.active' => 'full']);

        $companyA = $this->setUpCompany();
        $companyA->update(['edition' => 'simple']);
        app()->instance('currentCompany', $companyA->fresh());
        $this->assertSame('simple', ErpEdition::current());

        $companyB = $this->setUpCompany(
            \App\Models\Company::create(['name' => 'Company B', 'currency' => 'FCFA', 'is_active' => true])
        );
        $companyB->update(['edition' => 'growing']);
        app()->instance('currentCompany', $companyB->fresh());
        $this->assertSame('growing', ErpEdition::current());
    }

    public function test_no_company_bound_falls_back_to_global_config(): void
    {
        config(['erp.edition.active' => 'growing']);
        app()->forgetInstance('currentCompany');

        $this->assertSame('growing', ErpEdition::current());
    }
}
