<?php

use App\Services\ReportExportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reports:run-scheduled-exports', function (ReportExportService $reportExportService) {
    $processed = $reportExportService->runDueScheduledExports();

    $this->info(
        $processed > 0
        ? $processed . ' scheduled report export(s) processed successfully.'
        : 'No scheduled report exports were due.'
    );
})->purpose('Generate due scheduled financial report exports');

Schedule::command('reports:run-scheduled-exports')->everyThirtyMinutes();
