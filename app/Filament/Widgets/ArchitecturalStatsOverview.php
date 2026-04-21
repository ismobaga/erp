<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
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

    protected function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return __('erp.dashboard.operational_overview');
    }

    protected function getDescription(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return __('erp.dashboard.operational_overview_desc');
    }

    protected function getStats(): array
    {
        try {
            if (!$this->hasCoreTables()) {
                return $this->placeholderStats();
            }

            $clients = Client::query()->count();
            $activeClients = Client::query()->whereIn('status', ['active', 'customer'])->count();
            $openInvoices = Invoice::query()->whereIn('status', ['sent', 'overdue', 'partially_paid'])->count();
            $settledRevenue = (float) Payment::query()->sum('amount');
            $activeProjects = Project::query()->whereIn('status', ['active', 'in_progress'])->count();

            return [
                Stat::make(__('erp.dashboard.client_portfolio'), number_format($clients))
                    ->description(__('erp.dashboard.active_relations', ['count' => number_format($activeClients)]))
                    ->color('primary')
                    ->chart($this->countTrend('clients', 'created_at')),
                Stat::make(__('erp.dashboard.open_invoices'), number_format($openInvoices))
                    ->description(__('erp.dashboard.open_invoices_note'))
                    ->color('warning')
                    ->chart($this->countTrend('invoices', 'issue_date', fn($query) => $query->whereIn('status', ['sent', 'overdue', 'partially_paid']))),
                Stat::make(__('erp.dashboard.collected_revenue'), $this->money($settledRevenue))
                    ->description(__('erp.dashboard.collected_revenue_note'))
                    ->color('success')
                    ->chart($this->sumTrend('payments', 'payment_date', 'amount')),
                Stat::make(__('erp.dashboard.active_projects'), number_format($activeProjects))
                    ->description(__('erp.dashboard.active_projects_note'))
                    ->color('info')
                    ->chart($this->countTrend('projects', 'created_at', fn($query) => $query->whereIn('status', ['active', 'in_progress']))),
            ];
        } catch (Throwable) {
            return $this->placeholderStats();
        }
    }

    protected function hasCoreTables(): bool
    {
        foreach (['clients', 'invoices', 'payments', 'projects'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 0, '.', ' ');
    }

    protected function countTrend(string $table, string $dateColumn, ?callable $scope = null): array
    {
        if (!Schema::hasTable($table)) {
            return array_fill(0, 7, 0);
        }

        return collect(range(6, 0))
            ->map(function (int $offset) use ($table, $dateColumn, $scope): int {
                $start = now()->copy()->subMonths($offset)->startOfMonth();
                $end = now()->copy()->subMonths($offset)->endOfMonth();
                $query = DB::table($table)->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()]);

                if ($scope) {
                    $scope($query);
                }

                return (int) $query->count();
            })
            ->all();
    }

    protected function sumTrend(string $table, string $dateColumn, string $amountColumn): array
    {
        if (!Schema::hasTable($table)) {
            return array_fill(0, 7, 0);
        }

        return collect(range(6, 0))
            ->map(function (int $offset) use ($table, $dateColumn, $amountColumn): int {
                $start = now()->copy()->subMonths($offset)->startOfMonth();
                $end = now()->copy()->subMonths($offset)->endOfMonth();

                return (int) round(((float) DB::table($table)
                    ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()])
                    ->sum($amountColumn)) / 1000);
            })
            ->all();
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make(__('erp.dashboard.client_portfolio'), '0')
                ->description(__('erp.dashboard.no_clients'))
                ->color('primary')
                ->chart(array_fill(0, 7, 0)),
            Stat::make(__('erp.dashboard.open_invoices'), '0')
                ->description(__('erp.dashboard.no_invoices'))
                ->color('warning')
                ->chart(array_fill(0, 7, 0)),
            Stat::make(__('erp.dashboard.collected_revenue'), 'FCFA 0')
                ->description(__('erp.dashboard.no_payments'))
                ->color('success')
                ->chart(array_fill(0, 7, 0)),
            Stat::make(__('erp.dashboard.active_projects'), '0')
                ->description(__('erp.dashboard.no_projects'))
                ->color('info')
                ->chart(array_fill(0, 7, 0)),
        ];
    }
}
