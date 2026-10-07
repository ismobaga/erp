<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Local Docker setup only (see docker/local/entrypoint.sh): switches on every
 * advanced feature (blog, quotes, ledger…) for all companies so everything
 * can be tested without going through the settings screens.
 */
class LocalDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('LocalDevelopmentSeeder skipped: APP_ENV is not "local".');

            return;
        }

        $features = array_fill_keys((array) config('erp.company_features.advanced', []), true);

        Company::query()->each(function (Company $company) use ($features): void {
            $company->forceFill([
                'advanced_options' => array_replace((array) $company->advanced_options, $features),
            ])->save();
        });
    }
}
