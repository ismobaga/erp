<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        $user->assignRole('Super Admin');

        $company->users()->syncWithoutDetaching([
            $user->id => ['role' => 'owner'],
        ]);
    }
}
