<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('demo.enabled')) {
            return;
        }

        $allowedEnvironments = config('demo.allowed_environments', ['local', 'development', 'staging', 'testing']);

        if (! app()->environment($allowedEnvironments)) {
            return;
        }

        $this->call(DemoCompanySeeder::class);
    }
}
