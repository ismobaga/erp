<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Support\CurrentCompanyTeamResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DefaultCompanySeeder extends Seeder
{
    public function run(): void
    {
        $companyName = env('COMPANY_NAME', 'My Company');
        $companySlug = env('COMPANY_SLUG', Str::slug($companyName));

        $company = Company::firstOrCreate(
            ['slug' => $companySlug],
            [
                'name' => $companyName,
                'currency' => env('COMPANY_CURRENCY', 'FCFA'),
                'email' => env('COMPANY_EMAIL', 'contact@example.com'),
                'is_active' => true,
                'is_demo' => false,
                'logo_path' => 'company-assets/cm-logo.png',
            ],
        );

        app()->instance('currentCompany', $company);

        $this->call(LedgerAccountsSeeder::class);

        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD');

        abort_if(
            app()->isProduction() && blank($adminPassword),
            1,
            'ADMIN_PASSWORD must be set before seeding in production.',
        );

        $user = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => env('ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make($adminPassword ?: 'password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
        );

        // Super Admin is a global role assignment (company_id = NULL) — it
        // must not be scoped to the company bound above.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        try {
            $user->assignRole('Super Admin');
        } finally {
            CurrentCompanyTeamResolver::clearOverride();
            $user->unsetRelation('roles');
        }

        $company->users()->syncWithoutDetaching([
            $user->id => ['role' => 'owner'],
        ]);
    }
}
