<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Services\OperationalResilienceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class OperationalResilience extends Page
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Résilience';

    protected static ?string $title = 'Résilience opérationnelle';

    protected static ?string $slug = 'operational-resilience';

    protected string $view = 'filament.pages.operational-resilience';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runBackup')
                ->label('Lancer une sauvegarde')
                ->action(function (): void {
                    $backup = app(OperationalResilienceService::class)->createBackup(auth()->id());

                    Notification::make()
                        ->title('Sauvegarde créée')
                        ->body('Archive générée: ' . ($backup['path'] ?? 'backup'))
                        ->success()
                        ->send();
                }),
            Action::make('checkHealth')
                ->label('Contrôler la santé')
                ->action(function (): void {
                    $summary = app(OperationalResilienceService::class)->evaluateHealth(auth()->id());

                    Notification::make()
                        ->title('Contrôle exécuté')
                        ->body('Jobs échoués: ' . $summary['failed_jobs'] . ' · Alertes: ' . $summary['open_alerts'])
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $service = app(OperationalResilienceService::class);

        return [
            'summary' => $service->dashboardSummary(),
            'backups' => $service->backupFeed(),
            'alerts' => $service->alertFeed(),
            'audits' => $service->auditFeed(),
        ];
    }
}
