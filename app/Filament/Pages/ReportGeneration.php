<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Services\ReportExportService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ReportGeneration extends Page
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Rapports';

    protected static ?string $title = 'Exportation Analytique Financière';

    protected static ?string $slug = 'report-generation';

    protected string $view = 'filament.pages.report-generation';

    public string $startDate = '';

    public string $endDate = '';

    public string $exportFormat = 'pdf';

    public array $selectedModules = [
        'revenue' => true,
        'expenses' => false,
        'payments' => true,
        'taxes' => false,
    ];

    public bool $includeCharts = true;

    public bool $autoScheduleEnabled = false;

    public string $scheduleFrequency = 'Hebdomadaire';

    public string $nextExecutionAt = '';

    public string $scheduleEmail = '';

    public bool $reportReady = false;

    public array $reportSummary = [];

    public array $previewRows = [];

    public array $scheduledPlans = [];

    public string $generatedReportName = '';

    public string $generatedReportPath = '';

    public string $generatedDownloadUrl = '';

    public string $generatedReportTimestamp = '';

    public function mount(): void
    {
        $this->usePreset('quarter');
        $this->nextExecutionAt = now()->addDay()->setTime(8, 0)->format('Y-m-d\TH:i');
        $this->scheduleEmail = auth()->user()?->email ?? '';
        $this->scheduledPlans = $this->loadScheduledPlans();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runScheduledExports')
                ->label('Exécuter les exports planifiés')
                ->action(function (): void {
                    $processed = app(ReportExportService::class)->runDueScheduledExports();
                    $this->scheduledPlans = $this->loadScheduledPlans();

                    $notification = Notification::make()->title(
                        $processed > 0
                        ? $processed . ' export(s) planifié(s) généré(s).'
                        : 'Aucun export planifié à exécuter.'
                    );

                    ($processed > 0 ? $notification->success() : $notification->warning())->send();
                }),
        ];
    }

    public function usePreset(string $preset): void
    {
        [$start, $end] = match ($preset) {
            'year' => [now()->startOfYear(), now()->endOfDay()],
            default => [now()->subMonths(3)->startOfDay(), now()->endOfDay()],
        };

        $this->startDate = $start->toDateString();
        $this->endDate = $end->toDateString();
    }

    public function generateReport(): void
    {
        $validated = $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'exportFormat' => ['required', 'in:pdf,csv'],
            'selectedModules.revenue' => ['boolean'],
            'selectedModules.expenses' => ['boolean'],
            'selectedModules.payments' => ['boolean'],
            'selectedModules.taxes' => ['boolean'],
            'includeCharts' => ['boolean'],
            'autoScheduleEnabled' => ['boolean'],
            'scheduleFrequency' => ['nullable', 'in:Quotidienne,Hebdomadaire,Mensuelle'],
            'nextExecutionAt' => ['nullable', 'date'],
            'scheduleEmail' => ['nullable', 'email'],
        ]);

        if ($this->autoScheduleEnabled) {
            $this->validate([
                'scheduleFrequency' => ['required', 'in:Quotidienne,Hebdomadaire,Mensuelle'],
                'nextExecutionAt' => ['required', 'date'],
                'scheduleEmail' => ['required', 'email'],
            ]);
        }

        $start = Carbon::parse($validated['startDate'])->startOfDay();
        $end = Carbon::parse($validated['endDate'])->endOfDay();

        $report = app(ReportExportService::class)->generate(
            $start,
            $end,
            $this->selectedModules,
            $this->exportFormat,
            $this->includeCharts,
            auth()->id(),
        );

        $this->reportSummary = $report['summary'];
        $this->previewRows = $report['previewRows'];
        $this->generatedReportPath = $report['path'];
        $this->generatedReportName = $report['name'];
        $this->generatedDownloadUrl = $report['downloadUrl'];
        $this->generatedReportTimestamp = $report['generatedAt'];
        $this->reportReady = true;

        if ($this->autoScheduleEnabled) {
            app(ReportExportService::class)->persistScheduledPlan(
                app(ReportExportService::class)->buildScheduledPlan([
                    'startDate' => $validated['startDate'],
                    'endDate' => $validated['endDate'],
                    'exportFormat' => $validated['exportFormat'],
                    'scheduleFrequency' => $this->scheduleFrequency,
                    'nextExecutionAt' => $this->nextExecutionAt,
                    'scheduleEmail' => $this->scheduleEmail,
                    'selectedModules' => $this->selectedModules,
                    'includeCharts' => $this->includeCharts,
                ], auth()->id())
            );

            $this->scheduledPlans = $this->loadScheduledPlans();
        }

        Notification::make()
            ->title('Rapport généré avec succès.')
            ->body(
                'Votre export ' . strtoupper($this->exportFormat) . ' est prêt au téléchargement sécurisé.'
                . ($this->autoScheduleEnabled ? ' La planification automatique est activée.' : '')
            )
            ->success()
            ->send();
    }

    protected function loadScheduledPlans(): array
    {
        return app(ReportExportService::class)->loadScheduledPlans(auth()->id(), $this->defaultScheduledPlans());
    }

    protected function defaultScheduledPlans(): array
    {
        return [
            [
                'description' => 'Rapport CA Trimestriel',
                'frequency' => 'Hebdomadaire',
                'nextExecution' => now()->addDays(5)->setTime(8, 0)->format('d M Y - H:i'),
                'status' => 'Actif',
                'statusClasses' => 'bg-green-100 text-green-800',
                'email' => 'direction@entreprise.com',
                'lastGenerated' => 'Jamais',
            ],
            [
                'description' => 'Audit Fiscal Hebdo',
                'frequency' => 'Quotidienne',
                'nextExecution' => now()->addDay()->setTime(17, 30)->format('d M Y - H:i'),
                'status' => 'En attente',
                'statusClasses' => 'bg-yellow-100 text-yellow-800',
                'email' => 'comptabilite@entreprise.com',
                'lastGenerated' => 'Jamais',
            ],
        ];
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' FCFA';
    }
}
