<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Create the default company first so that subsequent seeders that
        // create company-scoped records (e.g. LedgerAccountsSeeder) have a
        // valid company_id to attach to.
        $companyName = env('COMPANY_NAME', 'My Company');
        $companySlug = env('COMPANY_SLUG', Str::slug($companyName));

        $company = Company::firstOrCreate(
            ['slug' => $companySlug],
            [
                'name'      => $companyName,
                'currency'  => env('COMPANY_CURRENCY', 'FCFA'),
                'email'     => env('COMPANY_EMAIL', 'contact@example.com'),
                'is_active' => true,
            ],
        );

        // Bind the company to the IoC container so that all models using
        // HasCompanyScope will automatically scope their queries and set
        // company_id on new records during this seed run.
        app()->instance('currentCompany', $company);

        $this->call(LedgerAccountsSeeder::class);

        $adminEmail    = env('ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('ADMIN_PASSWORD');

        abort_if(
            app()->isProduction() && blank($adminPassword),
            1,
            'ADMIN_PASSWORD must be set before seeding in production.',
        );

        // Use firstOrCreate so re-running the seeder does not produce duplicates.
        $user = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name'              => env('ADMIN_NAME', 'Super Admin'),
                'password'          => Hash::make($adminPassword ?? 'password'),
                'email_verified_at' => now(),
                'status'            => 'active',
            ],
        );

        $user->assignRole('Super Admin');

        // Attach the super admin to the default company as owner.
        $company->users()->syncWithoutDetaching([
            $user->id => ['role' => 'owner'],
        ]);
    }
}
