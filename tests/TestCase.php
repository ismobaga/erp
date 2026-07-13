<?php

namespace Tests;

use App\Models\Company;
use App\Models\FinancialPeriod;
use App\Models\User;
use App\Support\CurrentCompanyTeamResolver;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        FinancialPeriod::flushLockCache();

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

    /**
     * Assign the global (company_id = NULL) Super Admin role.
     *
     * With Spatie teams enabled, a plain assignRole('Super Admin') would be
     * scoped to the currently bound company and NOT be recognized by
     * User::isSuperAdmin(). Tests that need a real super admin must use this.
     */
    protected function assignSuperAdmin(User $user): User
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        try {
            $user->assignRole('Super Admin');
        } finally {
            CurrentCompanyTeamResolver::clearOverride();
            $user->unsetRelation('roles');
        }

        return $user;
    }
}
