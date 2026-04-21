<?php

namespace App\Console\Commands;

use App\Services\DunningService;
use Illuminate\Console\Command;

class RunAutomatedDunning extends Command
{
    protected $signature = 'erp:run-dunning';

    protected $description = 'Process overdue invoices and dispatch automated dunning reminders';

    public function handle(DunningService $dunningService): int
    {
        $count = $dunningService->runAutomatedDunning();

        if ($count > 0) {
            $this->info(trans('erp.dunning.auto_dunning_run', ['count' => $count]));
        } else {
            $this->info(trans('erp.dunning.no_auto_dunning'));
        }

        return self::SUCCESS;
    }
}
