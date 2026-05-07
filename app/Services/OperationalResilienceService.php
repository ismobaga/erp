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
    private const JSON_DECODE_DEPTH = 512;

    private const MIN_THRESHOLD_PERCENT = 1;

    private const MAX_THRESHOLD_PERCENT = 100;

    private const EVENT_DEDUPLICATION_MINUTES = 30;

    public function createBackup(?int $userId = null): array
    {
        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $directory = trim((string) config('erp.resilience.backups.directory', 'backups'), '/');
        $path = $directory.'/erp-backup-'.now()->format('Ymd-His').'.json';

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
            if (! Schema::hasTable($table)) {
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
        if (! $force) {
            throw new RuntimeException('Backup restore is destructive. Re-run with force enabled.');
        }

        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $path ??= $this->latestBackupPath();

        if (blank($path) || ! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('No backup archive is available to restore.');
        }

        $payload = json_decode(Crypt::decryptString(Storage::disk($disk)->get($path)), true, self::JSON_DECODE_DEPTH, JSON_THROW_ON_ERROR);
        $data = (array) ($payload['data'] ?? []);
        $knownTables = $this->backupTables();

        // Never run a destructive restore unless the archive shape is valid.
        $hasValidPayload = is_array($payload)
            && is_array($data)
            && count(array_intersect(array_keys($data), $knownTables)) > 0;

        if (! $hasValidPayload) {
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
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    $rows = $data[$table] ?? [];

                    if (! empty($rows)) {
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

    /**
     * Return details of failed jobs for monitoring.
     */
    public function failedJobFeed(int $limit = 10): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->take($limit)
            ->get()
            ->map(function (object $row): array {
                $payload = json_decode((string) ($row->payload ?? '{}'), true);
                $jobClass = data_get($payload, 'displayName', data_get($payload, 'job', 'Unknown'));

                return [
                    'uuid' => (string) ($row->uuid ?? ''),
                    'job' => is_string($jobClass) ? class_basename($jobClass) : 'Unknown',
                    'queue' => (string) ($row->queue ?? 'default'),
                    'exception' => mb_substr((string) ($row->exception ?? ''), 0, 256),
                    'failed_at' => $this->formatFailedAt($row->failed_at ?? null),
                ];
            })
            ->all();
    }

    /**
     * Retry a specific failed job by UUID and log the action.
     * Returns true when the job was found and re-queued, false otherwise.
     */
    public function retryFailedJob(string $uuid, ?int $userId = null): bool
    {
        if (! Schema::hasTable('failed_jobs')) {
            return false;
        }

        $row = DB::table('failed_jobs')->where('uuid', $uuid)->first();

        if ($row === null) {
            return false;
        }

        try {
            $payload = json_decode((string) ($row->payload ?? '{}'), true, self::JSON_DECODE_DEPTH, JSON_THROW_ON_ERROR);
            $connection = (string) ($row->connection ?? 'database');
            $queue = (string) ($row->queue ?? 'default');

            // Laravel stores all queued jobs in the 'jobs' table regardless of queue name.
            // The queue name is a column value, not a separate table.
            DB::connection($connection)->table('jobs')->insert([
                'queue' => $queue,
                'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->getTimestamp(),
                'created_at' => now()->getTimestamp(),
            ]);

            DB::table('failed_jobs')->where('uuid', $uuid)->delete();

            app(AuditTrailService::class)->log('failed_job_retried', null, [
                'uuid' => $uuid,
                'job' => data_get($payload, 'displayName', 'unknown'),
                'queue' => $queue,
            ], $userId);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Purge all failed jobs and log the action.
     */
    public function purgeFailedJobs(?int $userId = null): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        $count = (int) DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->delete();

        app(AuditTrailService::class)->log('failed_jobs_purged', null, [
            'count' => $count,
        ], $userId);

        return $count;
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

    public function verifyLatestBackup(?int $userId = null): array
    {
        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $path = $this->latestBackupPath();

        if (blank($path)) {
            $this->logSecurityEventOnce('backup_verification_failed', [
                'reason' => 'missing_backup_archive',
                'disk' => $disk,
            ], $userId);

            return [
                'status' => 'warning',
                'verified' => false,
                'reason' => 'missing_backup_archive',
                'path' => null,
            ];
        }

        try {
            $raw = Storage::disk($disk)->get($path);
            $decoded = json_decode(Crypt::decryptString($raw), true, self::JSON_DECODE_DEPTH, JSON_THROW_ON_ERROR);
            $hasMetadata = is_array($decoded) && is_array(data_get($decoded, 'metadata'));
            $hasData = is_array($decoded) && is_array(data_get($decoded, 'data'));

            if (! $hasMetadata || ! $hasData) {
                throw new RuntimeException('Invalid backup structure.');
            }

            app(AuditTrailService::class)->log('system_backup_verified', null, [
                'disk' => $disk,
                'path' => $path,
                'verified' => true,
            ], $userId);

            return [
                'status' => 'ok',
                'verified' => true,
                'reason' => null,
                'path' => $path,
            ];
        } catch (\Throwable) {
            $this->logSecurityEventOnce('backup_verification_failed', [
                'reason' => 'backup_archive_corrupted',
                'disk' => $disk,
                'path' => $path,
            ], $userId);

            return [
                'status' => 'degraded',
                'verified' => false,
                'reason' => 'backup_archive_corrupted',
                'path' => $path,
            ];
        }
    }

    public function storageHealth(?int $userId = null): array
    {
        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $directory = trim((string) config('erp.resilience.backups.directory', 'backups'), '/');
        $usageThreshold = max(
            self::MIN_THRESHOLD_PERCENT,
            min(self::MAX_THRESHOLD_PERCENT, (int) config('erp.resilience.monitoring.storage_usage_alert_threshold', 90))
        );

        $usedBytes = collect(Storage::disk($disk)->allFiles($directory))
            ->sum(fn (string $path): int => (int) Storage::disk($disk)->size($path));

        $totalBytes = null;
        $freeBytes = null;
        $usagePercent = null;

        try {
            $absolutePath = Storage::disk($disk)->path($directory);

            if (is_string($absolutePath) && $absolutePath !== '' && file_exists($absolutePath)) {
                $totalBytes = @disk_total_space($absolutePath) ?: null;
                $freeBytes = @disk_free_space($absolutePath) ?: null;

                if ($totalBytes !== null) {
                    $usagePercent = round((($totalBytes - ($freeBytes ?? 0)) / $totalBytes) * 100, 2);
                }
            }
        } catch (\Throwable) {
            // Some adapters (S3, scoped disks) do not expose local paths.
        }

        if ($usagePercent !== null && $usagePercent >= $usageThreshold) {
            $this->logSecurityEventOnce('storage_capacity_risk', [
                'disk' => $disk,
                'usage_percent' => $usagePercent,
                'threshold_percent' => $usageThreshold,
            ], $userId);
        }

        return [
            'status' => $usagePercent !== null && $usagePercent >= $usageThreshold ? 'warning' : 'ok',
            'disk' => $disk,
            'directory' => $directory,
            'backup_used_bytes' => $usedBytes,
            'total_bytes' => $totalBytes,
            'free_bytes' => $freeBytes,
            'usage_percent' => $usagePercent,
            'usage_alert_threshold' => $usageThreshold,
        ];
    }

    public function systemDiagnostics(): array
    {
        return [
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => (string) config('app.timezone', 'UTC'),
            'database_connection' => (string) config('database.default', 'unknown'),
            'queue_connection' => (string) config('queue.default', 'sync'),
            'cache_store' => (string) config('cache.default', 'file'),
        ];
    }

    public function healthCheckStatus(?int $userId = null): array
    {
        $summary = $this->evaluateHealth($userId);
        $backupVerification = $this->verifyLatestBackup($userId);
        $storage = $this->storageHealth($userId);
        $hasQueuePressure = (int) $summary['failed_jobs'] > 0;
        $isBackupHealthy = (bool) ($backupVerification['verified'] ?? false);

        return [
            'status' => ! $hasQueuePressure && $isBackupHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'queue' => [
                    'status' => $hasQueuePressure ? 'degraded' : 'ok',
                    'failed_jobs' => (int) $summary['failed_jobs'],
                    'queued_jobs' => (int) $summary['queued_jobs'],
                ],
                'backup_verification' => $backupVerification,
                'storage' => $storage,
                'alerts' => [
                    'open_alerts' => (int) $summary['open_alerts'],
                ],
                'disaster_recovery' => [
                    'backup_command' => 'erp:backup-run',
                    'restore_command' => 'erp:restore-backup --force',
                    'monitor_command' => 'erp:monitor-health',
                ],
            ],
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
            ->map(fn (ActivityLog $log): array => [
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
            ->map(fn (ActivityLog $log): array => [
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
            ->filter(fn (string $path): bool => str_ends_with($path, '.json'))
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
        $windowStart = now()->subMinutes(self::EVENT_DEDUPLICATION_MINUTES);

        $exists = ActivityLog::query()
            ->where('action', 'system_alert_raised')
            ->where('created_at', '>=', $windowStart)
            ->where('meta_json->type', $type)
            ->exists();

        if ($exists) {
            return;
        }

        app(AuditTrailService::class)->log('system_alert_raised', null, array_merge(['type' => $type], $meta), $userId);
    }

    protected function logSecurityEventOnce(string $type, array $meta, ?int $userId = null): void
    {
        $windowStart = now()->subMinutes(self::EVENT_DEDUPLICATION_MINUTES);

        $exists = ActivityLog::query()
            ->where('action', 'security_event_logged')
            ->where('created_at', '>=', $windowStart)
            ->where('meta_json->type', $type)
            ->exists();

        if ($exists) {
            return;
        }

        app(AuditTrailService::class)->log('security_event_logged', null, array_merge(['type' => $type], $meta), $userId);
    }

    /**
     * Format a `failed_at` value (raw timestamp integer or datetime string) into a
     * human-readable relative string. Returns 'unknown' when the value is absent.
     */
    protected function formatFailedAt(mixed $failedAt): string
    {
        if ($failedAt === null) {
            return 'unknown';
        }

        $timestamp = is_numeric($failedAt)
            ? (int) $failedAt
            : strtotime((string) $failedAt);

        if ($timestamp === false) {
            return 'unknown';
        }

        return now()->createFromTimestamp($timestamp)->diffForHumans();
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
