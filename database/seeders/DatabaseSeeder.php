<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Create the default company first so that subsequent seeders that
        // create company-scoped records (e.g. LedgerAccountsSeeder) have a
        // valid company_id to attach to.
        $company = Company::firstOrCreate(
            ['slug' => 'crommix-mali'],
            [
                'name'      => env('COMPANY_NAME', 'CROMMIX MALI - SA'),
                'currency'  => 'FCFA',
                'email'     => env('COMPANY_EMAIL', 'contact@crommixmali.com'),
                'is_active' => true,
            ],
        );

        // Bind the company to the IoC container so that all models using
        // HasCompanyScope will automatically scope their queries and set
        // company_id on new records during this seed run.
        app()->instance('currentCompany', $company);

        $this->call(LedgerAccountsSeeder::class);

        $user = User::factory()->create([
            'name'   => 'Super Admin',
            'email'  => env('ADMIN_EMAIL', 'admin@example.com'),
            'status' => 'active',
        ]);

        $user->assignRole('Super Admin');

        // Attach the super admin to the default company as owner.
        $company->users()->syncWithoutDetaching([
            $user->id => ['role' => 'owner'],
        ]);
    }
}
