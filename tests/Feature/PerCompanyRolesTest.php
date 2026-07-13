<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\CurrentCompanyTeamResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Verifies company-scoped roles (Spatie teams, team = company).
 *
 *  1. A role granted in company A does not exist in company B.
 *  2. Super Admin is a global (company_id = NULL) assignment, recognized in
 *     any company context via isSuperAdmin() — and grants abilities
 *     everywhere through Gate::before.
 *  3. The team resolver falls back to the user's own company when no
 *     currentCompany is bound (the login flow, where canAccessPanel runs
 *     before SetCurrentCompany).
 */
class PerCompanyRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        CurrentCompanyTeamResolver::clearOverride();
    }

    protected function tearDown(): void
    {
        CurrentCompanyTeamResolver::clearOverride();
        parent::tearDown();
    }

    public function test_role_in_one_company_does_not_leak_into_another(): void
    {
        $companyA = Company::create(['name' => 'Role Co A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Role Co B', 'currency' => 'FCFA', 'is_active' => true]);

        $user = User::factory()->create(['status' => 'active']);
        $user->companies()->attach([$companyA->id, $companyB->id]);

        // Grant Admin within company A's context only.
        $this->setUpCompany($companyA);
        $user->assignRole('Admin');
        $user->unsetRelation('roles');

        $this->assertTrue($user->hasRole('Admin'), 'Role should be visible in the company it was granted in.');

        // Switch to company B — the role must not follow.
        $this->setUpCompany($companyB);
        $user->unsetRelation('roles');

        $this->assertFalse($user->hasRole('Admin'), 'Role granted in company A must not exist in company B.');

        // Permissions follow the same scoping.
        $this->assertFalse($user->can('clients.view'));

        $this->setUpCompany($companyA);
        $user->unsetRelation('roles');
        $this->assertTrue($user->can('clients.view'));
    }

    public function test_super_admin_is_global_across_companies(): void
    {
        $companyA = Company::create(['name' => 'SA Co A', 'currency' => 'FCFA', 'is_active' => true]);

        $user = User::factory()->create(['status' => 'active']);

        // Assign Super Admin globally (company_id = NULL).
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $user->assignRole('Super Admin');
        CurrentCompanyTeamResolver::clearOverride();
        $user->unsetRelation('roles');

        // Recognized in any company context…
        $this->setUpCompany($companyA);
        $this->assertTrue($user->isSuperAdmin());

        // …even though the team-scoped hasRole() does not see it (by design).
        $user->unsetRelation('roles');
        $this->assertFalse($user->hasRole('Super Admin'));

        // Gate::before grants every ability regardless of company context.
        $this->assertTrue($user->can('users.delete'));
        $this->assertTrue($user->can('saas.plans.manage'));
    }

    public function test_team_resolver_falls_back_to_user_company_when_none_is_bound(): void
    {
        $company = Company::create(['name' => 'Fallback Co', 'currency' => 'FCFA', 'is_active' => true]);

        $user = User::factory()->create(['status' => 'active']);
        $user->companies()->attach($company->id);

        // Grant the role within the company context.
        $this->setUpCompany($company);
        $user->assignRole('Staff');
        $user->unsetRelation('roles');

        // Simulate the login flow: authenticated user, but no currentCompany
        // bound yet (canAccessPanel runs before SetCurrentCompany).
        app()->forgetInstance('currentCompany');
        Auth::setUser($user);
        $user->unsetRelation('roles');

        $this->assertTrue(
            $user->hasRole('Staff'),
            'Resolver must fall back to the user\'s own company when no currentCompany is bound.',
        );
    }
}
