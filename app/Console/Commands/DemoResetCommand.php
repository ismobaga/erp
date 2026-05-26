<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset demo data by deleting and reseeding the demo tenant.';

    public function handle(): int
    {
        $allowedEnvironments = config('demo.allowed_environments', ['local', 'development', 'staging', 'testing']);

        if (! app()->environment($allowedEnvironments)) {
            $this->error('Demo reset is not allowed in this environment.');

            return self::FAILURE;
        }

        $originalReadOnly = (bool) config('demo.read_only');
        config(['demo.enabled' => true, 'demo.read_only' => false]);

        try {
            $demoCompanies = Company::query()
                ->where('is_demo', true)
                ->get();

            $demoUserIds = collect();

            $demoCompanies->each(function (Company $company) use (&$demoUserIds): void {
                $demoUserIds = $demoUserIds->merge($company->users()->pluck('users.id'));
                $company->delete();
            });

            $this->cleanupOrphanedDemoUsers($demoUserIds->unique());

            $this->call('db:seed', [
                '--class' => 'Database\\Seeders\\DemoCompanySeeder',
                '--force' => true,
            ]);
        } finally {
            config(['demo.read_only' => $originalReadOnly]);
        }

        $this->info('Demo environment reset completed successfully.');

        return self::SUCCESS;
    }

    private function cleanupOrphanedDemoUsers(Collection $candidateIds): void
    {
        if ($candidateIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $candidateIds->all())
            ->whereDoesntHave('companies')
            ->delete();
    }
}
