<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Services\AnalyticsService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class Analytics extends Page
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Vue globale';

    protected static ?string $title = 'Tableau de bord analytique';

    protected static ?string $slug = 'analytics';

    protected string $view = 'filament.pages.analytics';

    public string $period = 'mtd';

    public function mount(): void
    {
        $this->period = $this->normalizePeriod((string) request()->query('period', $this->period));
    }

    public function updatedPeriod(string $value): void
    {
        $this->period = $this->normalizePeriod($value);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshMetrics')
                ->label(__('erp.actions.refresh_metrics'))
                ->action(function (): void {
                    $context = $this->resolvePeriodContext();
                    Cache::forget($this->cacheKey($context));
                    $this->dispatch('$refresh');

                    Notification::make()
                        ->title(__('erp.reports.metrics_refreshed'))
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $context = $this->resolvePeriodContext();
        $cacheKey = $this->cacheKey($context);

        try {
            return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($context): array {
                $kpis = app(AnalyticsService::class)->kpis($context['start'], $context['end']);

                return [
                    'period' => $this->period,
                    'periodOptions' => $this->periodOptions(),
                    'periodLabel' => $context['label'],
                    'finance' => $kpis['finance'],
                    'clients' => $kpis['clients'],
                    'projects' => $kpis['projects'],
                    'crm' => $kpis['crm'],
                    'hr' => $kpis['hr'],
                ];
            });
        } catch (Throwable) {
            return [
                'period' => $this->period,
                'periodOptions' => $this->periodOptions(),
                'periodLabel' => $context['label'],
                'finance' => [],
                'clients' => [],
                'projects' => [],
                'crm' => ['available' => false],
                'hr' => ['available' => false],
            ];
        }
    }

    protected function periodOptions(): array
    {
        return [
            'mtd' => 'Mois en cours',
            '30d' => '30 derniers jours',
            'qtd' => 'Trimestre en cours',
            'ytd' => 'Année en cours',
        ];
    }

    protected function normalizePeriod(string $value): string
    {
        return array_key_exists($value, $this->periodOptions()) ? $value : 'mtd';
    }

    protected function resolvePeriodContext(): array
    {
        $end = now()->endOfDay();

        switch ($this->period) {
            case '30d':
                $start = now()->subDays(29)->startOfDay();
                $label = '30 derniers jours';
                break;
            case 'qtd':
                $start = now()->startOfQuarter()->startOfDay();
                $label = 'Trimestre en cours';
                break;
            case 'ytd':
                $start = now()->startOfYear()->startOfDay();
                $label = 'Année en cours';
                break;
            case 'mtd':
            default:
                $start = now()->startOfMonth()->startOfDay();
                $label = 'Mois en cours';
                break;
        }

        return [
            'start' => Carbon::instance($start),
            'end' => Carbon::instance($end),
            'label' => $label,
        ];
    }

    private function cacheKey(array $context): string
    {
        return sprintf(
            'analytics:%s:%s:%s:%s',
            currentCompany()?->id ?? 'global',
            $this->period,
            $context['start']->toDateString(),
            $context['end']->toDateString(),
        );
    }
}
