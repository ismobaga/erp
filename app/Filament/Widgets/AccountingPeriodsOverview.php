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

    protected ?string $heading = null;

    protected ?string $description = null;

    protected function getHeading(): ?string
    {
        return __('erp.dashboard.accounting_periods');
    }

    protected function getDescription(): ?string
    {
        return __('erp.dashboard.accounting_periods_desc');
    }

    protected function getStats(): array
    {
        try {
            if (! Schema::hasTable('financial_periods')) {
                return $this->placeholderStats();
            }

            $openCount = FinancialPeriod::query()->open()->count();
            $closedCount = FinancialPeriod::query()->closed()->count();
            $currentPeriod = FinancialPeriod::query()->current(now())->first();
            $currentLabel = $currentPeriod?->name ?? __('erp.dashboard.no_active_period');
            $currentStatus = $currentPeriod?->isClosed() ? __('erp.ledger.statuses.voided') : __('erp.common.active');

            return [
                Stat::make(__('erp.dashboard.open_periods'), number_format($openCount))
                    ->description(__('erp.dashboard.open_periods_note'))
                    ->color('success')
                    ->chart(array_fill(0, 5, max(1, $openCount))),
                Stat::make(__('erp.dashboard.closed_periods'), number_format($closedCount))
                    ->description(__('erp.dashboard.closed_periods_note'))
                    ->color('danger')
                    ->chart(array_fill(0, 5, max(0, $closedCount))),
                Stat::make(__('erp.dashboard.active_period'), $currentLabel)
                    ->description(__('erp.dashboard.period_status', ['status' => $currentStatus]))
                    ->color($currentPeriod?->isClosed() ? 'warning' : 'primary'),
                Stat::make(__('erp.dashboard.date_reference'), Carbon::now()->format('d/m/Y'))
                    ->description(__('erp.dashboard.accounting_checkpoint'))
                    ->color('info'),
            ];
        } catch (Throwable $e) {
            report($e);

            if (app()->environment(['local', 'testing'])) {
                throw $e;
            }

            return $this->placeholderStats();
        }
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make(__('erp.dashboard.open_periods'), '0')
                ->description(__('erp.dashboard.open_periods_note'))
                ->color('success'),
            Stat::make(__('erp.dashboard.closed_periods'), '0')
                ->description(__('erp.dashboard.closed_periods_note'))
                ->color('danger'),
            Stat::make(__('erp.dashboard.active_period'), __('erp.dashboard.no_active_period'))
                ->description(__('erp.dashboard.period_to_configure'))
                ->color('warning'),
            Stat::make(__('erp.dashboard.date_reference'), Carbon::now()->format('d/m/Y'))
                ->description(__('erp.dashboard.accounting_checkpoint'))
                ->color('info'),
        ];
    }
}
