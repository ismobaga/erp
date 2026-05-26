<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ArchitecturalStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    protected ?string $description = null;

    protected function getHeading(): ?string
    {
        return __('erp.dashboard.operational_overview');
    }

    protected function getDescription(): ?string
    {
        return __('erp.dashboard.business_snapshot_desc');
    }

    protected function getStats(): array
    {
        try {
            if (! $this->hasCoreTables()) {
                return $this->placeholderStats();
            }

            $moneyIn = (float) Payment::query()->sum('amount');
            $moneyOut = (float) Expense::query()->sum('amount');
            $outstandingPayments = (float) Invoice::query()
                ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
                ->sum('balance_due');
            $profitability = $moneyIn - $moneyOut;

            $moneyInTrend = $this->sumTrend('payments', 'payment_date', 'amount');
            $moneyOutTrend = $this->sumTrend('expenses', 'expense_date', 'amount');
            $outstandingTrend = $this->sumTrend(
                'invoices',
                'issue_date',
                'balance_due',
                fn ($query) => $query->whereIn('status', ['sent', 'overdue', 'partially_paid']),
            );
            $profitabilityTrend = collect($moneyInTrend)
                ->zip($moneyOutTrend)
                ->map(fn ($pair): int => (int) $pair[0] - (int) $pair[1])
                ->values()
                ->all();

            return [
                Stat::make(__('erp.dashboard.money_in'), $this->money($moneyIn))
                    ->description(__('erp.dashboard.money_in_note'))
                    ->color('success')
                    ->chart($moneyInTrend),
                Stat::make(__('erp.dashboard.money_out'), $this->money($moneyOut))
                    ->description(__('erp.dashboard.money_out_note'))
                    ->color('warning')
                    ->chart($moneyOutTrend),
                Stat::make(__('erp.dashboard.outstanding_payments'), $this->money($outstandingPayments))
                    ->description(__('erp.dashboard.outstanding_payments_note'))
                    ->color('danger')
                    ->chart($outstandingTrend),
                Stat::make(__('erp.dashboard.profitability'), $this->money($profitability))
                    ->description(__('erp.dashboard.profitability_note'))
                    ->color('info')
                    ->chart($profitabilityTrend),
            ];
        } catch (Throwable $e) {
            report($e);

            if (app()->environment(['local', 'testing'])) {
                throw $e;
            }

            return $this->placeholderStats();
        }
    }

    protected function hasCoreTables(): bool
    {
        foreach (['invoices', 'payments', 'expenses'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    protected function money(float $amount): string
    {
        return 'FCFA '.number_format($amount, 0, '.', ' ');
    }

    protected function countTrend(string $table, string $dateColumn, ?callable $scope = null): array
    {
        if (! Schema::hasTable($table)) {
            return array_fill(0, 7, 0);
        }

        $company = currentCompany();

        return collect(range(6, 0))
            ->map(function (int $offset) use ($table, $dateColumn, $scope, $company): int {
                $start = now()->copy()->subMonths($offset)->startOfMonth();
                $end = now()->copy()->subMonths($offset)->endOfMonth();
                $query = DB::table($table)->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()]);

                if ($company && Schema::hasColumn($table, 'company_id')) {
                    $query->where('company_id', $company->id);
                }

                if ($scope) {
                    $scope($query);
                }

                return (int) $query->count();
            })
            ->all();
    }

    protected function sumTrend(string $table, string $dateColumn, string $amountColumn, ?callable $scope = null): array
    {
        if (! Schema::hasTable($table)) {
            return array_fill(0, 7, 0);
        }

        $company = currentCompany();

        return collect(range(6, 0))
            ->map(function (int $offset) use ($table, $dateColumn, $amountColumn, $scope, $company): int {
                $start = now()->copy()->subMonths($offset)->startOfMonth();
                $end = now()->copy()->subMonths($offset)->endOfMonth();

                $query = DB::table($table)
                    ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()]);

                if ($company && Schema::hasColumn($table, 'company_id')) {
                    $query->where('company_id', $company->id);
                }

                if ($scope) {
                    $scope($query);
                }

                // Keep chart values compact for readability in the small sparkline area
                return (int) round(((float) $query->sum($amountColumn)) / 1000);
            })
            ->all();
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make(__('erp.dashboard.money_in'), 'FCFA 0')
                ->description(__('erp.dashboard.no_payments'))
                ->color('success')
                ->chart(array_fill(0, 7, 0)),
            Stat::make(__('erp.dashboard.money_out'), 'FCFA 0')
                ->description(__('erp.dashboard.no_expenses'))
                ->color('warning')
                ->chart(array_fill(0, 7, 0)),
            Stat::make(__('erp.dashboard.outstanding_payments'), 'FCFA 0')
                ->description(__('erp.dashboard.no_invoices'))
                ->color('danger')
                ->chart(array_fill(0, 7, 0)),
            Stat::make(__('erp.dashboard.profitability'), 'FCFA 0')
                ->description(__('erp.dashboard.no_profit_data'))
                ->color('info')
                ->chart(array_fill(0, 7, 0)),
        ];
    }
}
