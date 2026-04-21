<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(LedgerAccountsSeeder::class);

        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@crommix.local',
            'status' => 'active',
        ]);

        $user->assignRole('Super Admin');
    }
}
