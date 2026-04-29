<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_finance_user_can_access_finance_routes_but_not_user_admin(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $this->actingAs($user)
            ->get('/admin/payments')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/financial-periods')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_project_manager_can_access_projects_but_not_expenses(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Project Manager');

        $this->actingAs($user)
            ->get('/admin/projects')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/financial-periods')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/expenses')
            ->assertForbidden();
    }

    public function test_restricted_user_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['status' => 'restricted']);
        $user->assignRole('Staff');

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_simple_edition_keeps_core_finance_modules_and_dashboard_accessible(): void
    {
        config()->set('erp.edition.active', 'simple');
        config()->set('erp.edition.profiles.simple.enabled_modules', ['dashboard', 'quotes', 'invoices', 'payments', 'expenses']);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/quotes')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/invoices')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/payments')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/expenses')
            ->assertOk();
    }

    public function test_simple_edition_blocks_modules_outside_the_selected_profile_even_for_super_admin(): void
    {
        config()->set('erp.edition.active', 'simple');
        config()->set('erp.edition.profiles.simple.enabled_modules', ['dashboard', 'quotes', 'invoices', 'payments', 'expenses']);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->get('/admin/report-generation')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/clients')
            ->assertForbidden();
    }
}
