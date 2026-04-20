<?php

namespace App\Filament\Widgets;

use App\Models\FinancialPeriod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AccountingPeriodsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Périodes comptables';

    protected ?string $description = 'Suivi des périodes ouvertes, clôturées et de la fenêtre active.';

    protected function getStats(): array
    {
        try {
            if (!Schema::hasTable('financial_periods')) {
                return $this->placeholderStats();
            }

            $openCount = FinancialPeriod::query()->open()->count();
            $closedCount = FinancialPeriod::query()->closed()->count();
            $currentPeriod = FinancialPeriod::query()->current(now())->first();
            $currentLabel = $currentPeriod?->name ?? 'Aucune période active';
            $currentStatus = $currentPeriod?->isClosed() ? 'Clôturée' : 'Ouverte';

            return [
                Stat::make('Périodes ouvertes', number_format($openCount))
                    ->description('Fenêtres encore modifiables')
                    ->color('success')
                    ->chart([1, 1, 2, 2, max(1, $openCount)]),
                Stat::make('Périodes clôturées', number_format($closedCount))
                    ->description('Historique sécurisé et verrouillé')
                    ->color('danger')
                    ->chart([0, 1, 1, 2, max(1, $closedCount)]),
                Stat::make('Période active', $currentLabel)
                    ->description('Statut : ' . $currentStatus)
                    ->color($currentPeriod?->isClosed() ? 'warning' : 'primary')
                    ->chart([2, 3, 3, 4, 4]),
                Stat::make('Référence du jour', Carbon::now()->format('d/m/Y'))
                    ->description('Point de contrôle comptable')
                    ->color('info')
                    ->chart([1, 2, 2, 3, 3]),
            ];
        } catch (Throwable) {
            return $this->placeholderStats();
        }
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make('Périodes ouvertes', '0')
                ->description('Fenêtres encore modifiables')
                ->color('success')
                ->chart([1, 1, 1, 1, 1]),
            Stat::make('Périodes clôturées', '0')
                ->description('Historique sécurisé et verrouillé')
                ->color('danger')
                ->chart([0, 0, 0, 0, 0]),
            Stat::make('Période active', 'Aucune période active')
                ->description('Statut : À configurer')
                ->color('warning')
                ->chart([1, 1, 1, 1, 1]),
            Stat::make('Référence du jour', Carbon::now()->format('d/m/Y'))
                ->description('Point de contrôle comptable')
                ->color('info')
                ->chart([1, 2, 2, 3, 3]),
        ];
    }
}
