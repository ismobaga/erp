<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Services\OperationalResilienceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Redirect;

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
            Action::make('purgeFailedJobs')
                ->label('Purger les jobs échoués')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Purger tous les jobs échoués')
                ->modalDescription('Cette action supprime définitivement tous les jobs échoués. Confirmez pour continuer.')
                ->visible(fn(): bool => auth()->user()?->can('reports.delete') ?? false)
                ->action(function (): void {
                    $count = app(OperationalResilienceService::class)->purgeFailedJobs(auth()->id());

                    Notification::make()
                        ->title('Jobs purgés')
                        ->body($count . ' job(s) échoué(s) supprimé(s).')
                        ->success()
                        ->send();
                }),
            Action::make('downloadLatestBackup')
                ->label('Télécharger la sauvegarde')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn(): bool => auth()->user()?->can('reports.delete') ?? false)
                ->action(function (): mixed {
                    $service = app(OperationalResilienceService::class);
                    $summary = $service->latestBackupSummaryPublic();

                    if (blank($summary['path'])) {
                        Notification::make()
                            ->title('Aucune sauvegarde disponible.')
                            ->warning()
                            ->send();

                        return null;
                    }

                    return Redirect::away($service->backupDownloadUrl($summary['path']));
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
            'failedJobs' => $service->failedJobFeed(),
        ];
    }

    public function retryJob(string $uuid): void
    {
        $retried = app(OperationalResilienceService::class)->retryFailedJob($uuid, auth()->id());

        if ($retried) {
            Notification::make()
                ->title('Job remis en file')
                ->body('Le job a été remis en file d\'attente.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Échec de la remise en file')
                ->body('Impossible de remettre ce job en file. Il a peut-être déjà été traité.')
                ->warning()
                ->send();
        }
    }
}
