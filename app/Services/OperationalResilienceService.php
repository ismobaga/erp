<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class OperationalResilienceService
{
    public function createBackup(?int $userId = null): array
    {
        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $directory = trim((string) config('erp.resilience.backups.directory', 'backups'), '/');
        $path = $directory . '/erp-backup-' . now()->format('Ymd-His') . '.json';

        app(AuditTrailService::class)->log('system_backup_created', null, [
            'disk' => $disk,
            'path' => $path,
        ], $userId);

        $metadata = [
            'generated_at' => now()->toIso8601String(),
            'disk' => $disk,
            'path' => $path,
            'application' => config('app.name'),
            'environment' => app()->environment(),
            'tables' => [],
            'record_count' => 0,
        ];

        // Build the payload incrementally to avoid loading the entire
        // database into a single PHP array (OOM risk on large datasets).
        $tableData = [];

        foreach ($this->backupTables() as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $tableRows = [];
            DB::table($table)->orderBy('id')->chunk(500, function ($chunk) use (&$tableRows): void {
                foreach ($chunk as $row) {
                    $tableRows[] = (array) $row;
                }
            });

            $tableData[$table] = $tableRows;
            $metadata['tables'][] = $table;
            $metadata['record_count'] += count($tableRows);
        }

        $payload = [
            'metadata' => $metadata,
            'data' => $tableData,
        ];

        Storage::disk($disk)->makeDirectory($directory);
        Storage::disk($disk)->put($path, Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_SLASHES)));

        $this->pruneOldBackups();

        return Arr::only($payload['metadata'], ['disk', 'path', 'record_count']);
    }

    public function restoreBackup(?string $path = null, ?int $userId = null, bool $force = false): array
    {
        if (!$force) {
            throw new RuntimeException('Backup restore is destructive. Re-run with force enabled.');
        }

        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $path ??= $this->latestBackupPath();

        if (blank($path) || !Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('No backup archive is available to restore.');
        }

        $payload = json_decode(Crypt::decryptString(Storage::disk($disk)->get($path)), true, 512, JSON_THROW_ON_ERROR);
        $data = (array) ($payload['data'] ?? []);
        $knownTables = $this->backupTables();

        // Never run a destructive restore unless the archive shape is valid.
        $hasValidPayload = is_array($payload)
            && is_array($data)
            && count(array_intersect(array_keys($data), $knownTables)) > 0;

        if (!$hasValidPayload) {
            throw new RuntimeException('Backup archive is invalid or incomplete.');
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($data, $knownTables): void {
                foreach (array_reverse($knownTables) as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                foreach ($knownTables as $table) {
                    if (!Schema::hasTable($table)) {
                        continue;
                    }

                    $rows = $data[$table] ?? [];

                    if (!empty($rows)) {
                        // Chunk inserts to stay within MySQL's max_allowed_packet.
                        collect($rows)->chunk(500)->each(
                            fn ($chunk) => DB::table($table)->insert($chunk->all())
                        );
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        app(AuditTrailService::class)->log('system_backup_restored', null, [
            'disk' => $disk,
            'path' => $path,
            'tables_restored' => count(array_keys($data)),
        ], $userId);

        return [
            'disk' => $disk,
            'path' => $path,
            'tables_restored' => count(array_keys($data)),
        ];
    }

    public function evaluateHealth(?int $userId = null): array
    {
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $queuedJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $openAlerts = ActivityLog::query()->where('action', 'system_alert_raised')->where('created_at', '>=', now()->subDay())->count();
        $latestBackup = $this->latestBackupSummary();

        $failedThreshold = max(1, (int) config('erp.resilience.monitoring.failed_jobs_alert_threshold', 5));
        $staleAfterHours = max(1, (int) config('erp.resilience.monitoring.backups_stale_after_hours', 24));

        if ($failedJobs >= $failedThreshold) {
            $this->logAlertOnce('failed_jobs_threshold', [
                'failed_jobs' => $failedJobs,
                'threshold' => $failedThreshold,
            ], $userId);
        }

        if (($latestBackup['age_hours'] ?? null) === null || (float) $latestBackup['age_hours'] > $staleAfterHours) {
            $this->logAlertOnce('backup_stale', [
                'backup_path' => $latestBackup['path'] ?? null,
                'age_hours' => $latestBackup['age_hours'] ?? null,
                'threshold_hours' => $staleAfterHours,
            ], $userId);
        }

        return [
            'failed_jobs' => $failedJobs,
            'queued_jobs' => $queuedJobs,
            'open_alerts' => $openAlerts,
            'latest_backup' => $latestBackup,
            'audit_events_24h' => ActivityLog::query()->where('created_at', '>=', now()->subDay())->count(),
        ];
    }

    public function dashboardSummary(): array
    {
        $summary = $this->evaluateHealth();
        $latestBackup = $summary['latest_backup'];

        return [
            'latest_backup_label' => $latestBackup['label'] ?? 'No backup yet',
            'latest_backup_note' => $latestBackup['path'] ?? 'Run the backup command to create the first archive.',
            'failed_jobs' => (int) $summary['failed_jobs'],
            'queued_jobs' => (int) $summary['queued_jobs'],
            'open_alerts' => (int) $summary['open_alerts'],
            'audit_events_24h' => (int) $summary['audit_events_24h'],
        ];
    }

    public function backupDownloadUrl(string $path): string
    {
        return URL::temporarySignedRoute(
            'backups.download',
            now()->addMinutes(30),
            ['backup' => encrypt($path)]
        );
    }

    public function backupFeed(): array
    {
        $disk = (string) config('erp.resilience.backups.disk', 'local');

        return ActivityLog::query()
            ->whereIn('action', ['system_backup_created', 'system_backup_restored'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function (ActivityLog $log) use ($disk): array {
                $path = (string) data_get($log->meta_json, 'path', '');
                $fileExists = $path !== '' && Storage::disk($disk)->exists($path);

                return [
                    'action' => $log->action,
                    'label' => ucfirst(str_replace('_', ' ', $log->action)),
                    'path' => $path ?: 'n/a',
                    'time' => $log->created_at?->diffForHumans() ?? 'recently',
                    'downloadUrl' => $fileExists ? $this->backupDownloadUrl($path) : null,
                ];
            })
            ->all();
    }

    public function alertFeed(): array
    {
        return ActivityLog::query()
            ->where('action', 'system_alert_raised')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn(ActivityLog $log): array => [
                'label' => ucfirst((string) str_replace('_', ' ', data_get($log->meta_json, 'type', 'system_alert'))),
                'details' => json_encode($log->meta_json, JSON_UNESCAPED_SLASHES),
                'time' => $log->created_at?->diffForHumans() ?? 'recently',
            ])
            ->all();
    }

    public function auditFeed(): array
    {
        return ActivityLog::query()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn(ActivityLog $log): array => [
                'label' => ucfirst(str_replace('_', ' ', $log->action ?: 'audit event')),
                'subject' => class_basename((string) $log->subject_type) ?: 'System',
                'time' => $log->created_at?->diffForHumans() ?? 'recently',
            ])
            ->all();
    }

    public function latestBackupSummaryPublic(): array
    {
        return $this->latestBackupSummary();
    }

    protected function latestBackupPath(): ?string
    {
        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $directory = trim((string) config('erp.resilience.backups.directory', 'backups'), '/');

        $candidates = collect(Storage::disk($disk)->allFiles($directory))
            ->filter(fn(string $path): bool => str_ends_with($path, '.json'))
            ->sortDesc()
            ->values();

        return $candidates->first();
    }

    protected function latestBackupSummary(): array
    {
        $path = $this->latestBackupPath();

        if (blank($path)) {
            return [
                'label' => 'No backup yet',
                'path' => null,
                'age_hours' => null,
            ];
        }

        $timestamp = Storage::disk((string) config('erp.resilience.backups.disk', 'local'))->lastModified($path);
        $ageHours = round(now()->diffInSeconds(now()->createFromTimestamp($timestamp)) / 3600, 2);

        return [
            'label' => 'Backup available',
            'path' => $path,
            'age_hours' => $ageHours,
        ];
    }

    protected function pruneOldBackups(): void
    {
        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $directory = trim((string) config('erp.resilience.backups.directory', 'backups'), '/');
        $retentionDays = max(1, (int) config('erp.resilience.backups.retention_days', 14));

        foreach (Storage::disk($disk)->allFiles($directory) as $path) {
            if (Storage::disk($disk)->lastModified($path) < now()->subDays($retentionDays)->getTimestamp()) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    protected function logAlertOnce(string $type, array $meta, ?int $userId = null): void
    {
        $exists = ActivityLog::query()
            ->where('action', 'system_alert_raised')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->get()
            ->contains(fn(ActivityLog $log): bool => data_get($log->meta_json, 'type') === $type);

        if ($exists) {
            return;
        }

        app(AuditTrailService::class)->log('system_alert_raised', null, array_merge(['type' => $type], $meta), $userId);
    }

    protected function backupTables(): array
    {
        // Order matters: parent tables before child tables so that the restore
        // path (which reverses this list before deleting rows) can satisfy FK
        // constraints. Self-referencing FKs (ledger_accounts.parent_id,
        // journal_entries.reversal_of) are defined with nullOnDelete(), so they
        // do not block deletion order.
        return [
            'companies',
            'clients',
            'services',
            'financial_periods',
            'sequences',
            'ledger_accounts',
            'quotes',
            'quote_items',
            'invoices',
            'invoice_items',
            'payments',
            'credit_notes',
            'expenses',
            'projects',
            'notes',
            'attachments',
            'recurring_invoices',
            'journal_entries',
            'journal_entry_lines',
            'dunning_logs',
            'report_schedules',
            'whatsapp_message_logs',
            'activity_logs',
        ];
    }
}
