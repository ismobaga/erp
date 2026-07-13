<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers company archival (soft deletes) and user offboarding.
 */
class CompanyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_a_company_hides_it_from_memberships(): void
    {
        $company = Company::create(['name' => 'Archive Co', 'currency' => 'FCFA', 'is_active' => true]);

        $user = User::factory()->create(['status' => 'active']);
        $user->companies()->attach($company->id);

        $this->assertSame(1, $user->companies()->count());

        $company->delete();

        $this->assertSoftDeleted('companies', ['id' => $company->id]);

        // Archived companies vanish from memberships (and therefore from the
        // switcher and the SetCurrentCompany fallback chain).
        $this->assertSame(0, $user->companies()->count());

        $company->restore();

        $this->assertSame(1, $user->companies()->count());
    }

    public function test_force_deleting_a_company_with_financial_records_is_blocked(): void
    {
        $company = Company::create(['name' => 'Ledger Co', 'currency' => 'FCFA', 'is_active' => true]);

        $clientId = DB::table('clients')->insertGetId([
            'company_id' => $company->id,
            'type' => 'company',
            'company_name' => 'Client X',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoices')->insert([
            'company_id' => $company->id,
            'client_id' => $clientId,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'invoice_number' => 'INV-DEL-001',
            'total' => 100,
            'balance_due' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Archiving is fine…
        $company->delete();
        $this->assertSoftDeleted('companies', ['id' => $company->id]);

        // …but permanent destruction is not.
        $this->expectException(RuntimeException::class);
        $company->forceDelete();
    }

    public function test_force_deleting_an_empty_company_is_allowed(): void
    {
        $company = Company::create(['name' => 'Empty Co', 'currency' => 'FCFA', 'is_active' => true]);

        $company->forceDelete();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_offboarding_revokes_roles_and_membership_in_current_company_only(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $companyA = Company::create(['name' => 'Off Co A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Off Co B', 'currency' => 'FCFA', 'is_active' => true]);

        $user = User::factory()->create(['status' => 'active']);
        $user->companies()->attach([$companyA->id, $companyB->id]);

        // Grant roles in both companies.
        $this->setUpCompany($companyA);
        $user->assignRole('Admin');
        $user->unsetRelation('roles');

        $this->setUpCompany($companyB);
        $user->assignRole('Staff');
        $user->unsetRelation('roles');

        // Offboard from company A.
        $this->setUpCompany($companyA);
        UserResource::removeFromCurrentCompany($user);

        $this->assertFalse($user->companies()->whereKey($companyA->id)->exists());
        $user->unsetRelation('roles');
        $this->assertFalse($user->hasRole('Admin'), 'Roles in the offboarded company must be revoked.');

        // Membership and roles in company B are untouched.
        $this->assertTrue($user->companies()->whereKey($companyB->id)->exists());

        $this->setUpCompany($companyB);
        $user->unsetRelation('roles');
        $this->assertTrue($user->hasRole('Staff'), 'Roles in other companies must survive offboarding.');
    }
}
