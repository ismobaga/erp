<?php

namespace App\Filament\Widgets;

use App\Services\OperationalResilienceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationalResilienceOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Résilience opérationnelle';

    protected ?string $description = 'Sauvegardes, jobs échoués, alertes et activité d’audit.';

    protected function getStats(): array
    {
        $summary = app(OperationalResilienceService::class)->dashboardSummary();

        return [
            Stat::make('Sauvegarde la plus récente', $summary['latest_backup_label'])
                ->description((string) $summary['latest_backup_note']),
            Stat::make('Jobs échoués', (string) $summary['failed_jobs'])
                ->description('File en attente: ' . $summary['queued_jobs']),
            Stat::make('Alertes système', (string) $summary['open_alerts'])
                ->description('Seuils de surveillance actifs'),
            Stat::make('Audit 24h', (string) $summary['audit_events_24h'])
                ->description('Événements administratifs récents'),
        ];
    }
}
