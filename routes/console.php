<?php

use App\Console\Commands\RunAutomatedDunning;
use App\Models\ActivityLog;
use App\Services\OperationalResilienceService;
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

Artisan::command('erp:backup-run', function (OperationalResilienceService $service) {
    $backup = $service->createBackup();

    $this->info('Backup created at ' . ($backup['path'] ?? 'unknown path') . '.');
})->purpose('Create an operational resilience backup archive');

Artisan::command('erp:restore-backup {path?} {--force : Confirm destructive restore}', function (OperationalResilienceService $service, ?string $path = null) {
    $forced = (bool) $this->option('force');

    if (!$forced && $this->input->isInteractive()) {
        $forced = (bool) $this->confirm('This will delete current data and restore from backup. Continue?', false);
    }

    $result = $service->restoreBackup($path, null, $forced);

    $this->info('Backup restored from ' . ($result['path'] ?? 'unknown path') . '.');
})->purpose('Restore the latest or specified resilience backup archive');

Artisan::command('erp:monitor-health', function (OperationalResilienceService $service) {
    $summary = $service->evaluateHealth();

    $this->info('Health check complete. Failed jobs: ' . $summary['failed_jobs'] . '; Open alerts: ' . $summary['open_alerts'] . '.');
})->purpose('Evaluate operational resilience thresholds and raise alerts');

Artisan::command('invoices:send-due-reminders', function () {
    $targetDate = now()->addDay()->toDateString();

    $invoices = \App\Models\Invoice::query()
        ->with('client')
        ->whereDate('due_date', $targetDate)
        ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
        ->where('balance_due', '>', 0)
        ->get();

    $sent = 0;
    $skipped = 0;

    foreach ($invoices as $invoice) {
        $client = $invoice->client;

        if (!$client || blank($client->email)) {
            $skipped++;
            continue;
        }

        \Illuminate\Support\Facades\Mail::to($client->email)
            ->queue(new \App\Mail\InvoiceReminderMail($invoice));

        app(\App\Services\AuditTrailService::class)->log('invoice_reminder_sent', $invoice, [
            'reference' => $invoice->invoice_number,
            'client_email' => $client->email,
            'balance_due' => (float) $invoice->balance_due,
            'due_date' => $targetDate,
            'sent_by' => 'scheduler',
        ]);

        $sent++;
    }

    $this->info($sent . ' reminder(s) queued for invoices due tomorrow. ' . $skipped . ' skipped (no client email).');
})->purpose('Queue payment reminder emails for invoices due tomorrow');

Schedule::command('erp:backup-run')->dailyAt('01:00');
Schedule::command('invoices:send-due-reminders')->dailyAt('08:00');
Schedule::command('invoices:generate-recurring')->dailyAt('06:00');
Schedule::command('reports:run-scheduled-exports')->everyThirtyMinutes();
Schedule::command('erp:monitor-health')->everyFifteenMinutes();
Schedule::command('reports:cleanup-exports')->dailyAt('02:00');
Schedule::command('erp:prune-audit-logs')->dailyAt('02:30');
Schedule::command('erp:run-dunning')->dailyAt('08:00');
