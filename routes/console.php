<?php

use App\Models\ActivityLog;
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

Artisan::command('reports:cleanup-exports', function (ReportExportService $reportExportService) {
    $deleted = $reportExportService->cleanupExpiredExports();

    $this->info(
        $deleted > 0
        ? $deleted . ' expired report export(s) deleted.'
        : 'No expired report exports to clean up.'
    );
})->purpose('Delete expired generated financial report exports');

Artisan::command('erp:prune-audit-logs', function () {
    $retentionDays = max(7, (int) config('erp.enterprise.audit_retention_days', 365));

    $deleted = ActivityLog::query()
        ->where('created_at', '<', now()->subDays($retentionDays))
        ->delete();

    $this->info(
        $deleted > 0
        ? $deleted . ' audit log record(s) pruned.'
        : 'No audit log records required pruning.'
    );
})->purpose('Prune old enterprise audit log records');

Schedule::command('reports:run-scheduled-exports')->everyThirtyMinutes();
Schedule::command('reports:cleanup-exports')->dailyAt('02:00');
Schedule::command('erp:prune-audit-logs')->dailyAt('02:30');
