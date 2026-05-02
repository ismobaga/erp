<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\DunningService;
use Illuminate\Console\Command;

class RunAutomatedDunning extends Command
{
    protected $signature = 'erp:run-dunning';

    protected $description = 'Process overdue invoices and dispatch automated dunning reminders';

    public function handle(DunningService $dunningService): int
    {
        $totalCount = 0;

        // Iterate over every active company so that HasCompanyScope isolates
        // each tenant's overdue invoices correctly.
        Company::query()->where('is_active', true)->each(function (Company $company) use ($dunningService, &$totalCount): void {
            app()->instance('currentCompany', $company);

            $count = $dunningService->runAutomatedDunning();
            $totalCount += $count;
        });

        if ($totalCount > 0) {
            $this->info(trans('erp.dunning.auto_dunning_run', ['count' => $totalCount]));
        } else {
            $this->info(trans('erp.dunning.no_auto_dunning'));
        }

        return self::SUCCESS;
    }
}
