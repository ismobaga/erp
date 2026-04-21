<?php

namespace App\Filament\Widgets;

use App\Services\OperationalResilienceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationalResilienceOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected function getHeading(): ?string
    {
        return __('erp.dashboard.resilience');
    }

    protected function getDescription(): ?string
    {
        return __('erp.dashboard.resilience_desc');
    }

    protected function getStats(): array
    {
        $summary = app(OperationalResilienceService::class)->dashboardSummary();

        return [
            Stat::make(__('erp.dashboard.latest_backup'), $summary['latest_backup_label'])
                ->description((string) $summary['latest_backup_note']),
            Stat::make('Jobs échoués', (string) $summary['failed_jobs'])
                ->description(__('erp.dashboard.queue_pending', ['count' => $summary['queued_jobs']])),
            Stat::make(__('erp.dashboard.system_alerts'), (string) $summary['open_alerts'])
                ->description(__('erp.dashboard.alert_thresholds')),
            Stat::make(__('erp.dashboard.audit_24h'), (string) $summary['audit_events_24h'])
                ->description(__('erp.dashboard.audit_events_recent')),
        ];
    }
}
