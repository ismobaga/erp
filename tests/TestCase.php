<?php

namespace Tests;

use App\Models\Company;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Automatically bind a default company to the IoC container whenever
        // the companies table is available (i.e. after migrations have run).
        // This ensures all HasCompanyScope models receive a company_id without
        // requiring every individual test to set one up manually.
        if (Schema::hasTable('companies')) {
            $this->setUpCompany();
        }
    }

    /**
     * Create a company (or use the provided one) and bind it as 'currentCompany'
     * in the IoC container.  Tests that need a specific company can call this
     * method explicitly to override the default.
     */
    protected function setUpCompany(?Company $company = null): Company
    {
        $company ??= Company::create([
            'name'      => 'Test Company',
            'currency'  => 'FCFA',
            'is_active' => true,
        ]);

        app()->instance('currentCompany', $company);

        return $company;
    }
}
