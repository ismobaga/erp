<?php

namespace App\Filament\Resources\Invoices\Widgets;

use App\Models\Invoice;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InvoiceLedgerStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Vue financière';

    protected ?string $description = 'Indicateurs en direct pour le suivi des factures et des créances.';

    protected function getStats(): array
    {
        try {
            if (!Schema::hasTable('invoices') || !Schema::hasTable('quotes')) {
                return $this->placeholderStats();
            }

            $receivables = (float) Invoice::query()->sum('total');
            $overdueBalance = (float) Invoice::query()->where('status', 'overdue')->sum('balance_due');
            $pendingCount = Invoice::query()->whereIn('status', ['sent', 'overdue', 'partially_paid'])->count();
            $linkedQuotes = Quote::query()->count();

            return [
                Stat::make('Créances totales', $this->money($receivables))
                    ->description('Valeur confirmée des factures')
                    ->color('primary')
                    ->chart($this->sumTrend('invoices', 'issue_date', 'total')),
                Stat::make('Montants en retard', $this->money($overdueBalance))
                    ->description('Sommes demandant une action rapide')
                    ->color('danger')
                    ->chart($this->sumTrend('invoices', 'issue_date', 'balance_due', fn($query) => $query->where('status', 'overdue'))),
                Stat::make('Factures en attente', number_format($pendingCount))
                    ->description('Charge active de recouvrement')
                    ->color('warning')
                    ->chart($this->countTrend('invoices', 'issue_date', fn($query) => $query->whereIn('status', ['sent', 'overdue', 'partially_paid']))),
                Stat::make('Devis liés', number_format($linkedQuotes))
                    ->description('Documents commerciaux d’origine')
                    ->color('success')
                    ->chart($this->countTrend('quotes', 'issue_date')),
            ];
        } catch (Throwable) {
            return $this->placeholderStats();
        }
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

    protected function sumTrend(string $table, string $dateColumn, string $amountColumn, ?callable $scope = null): array
    {
        if (!Schema::hasTable($table)) {
            return array_fill(0, 7, 0);
        }

        return collect(range(6, 0))
            ->map(function (int $offset) use ($table, $dateColumn, $amountColumn, $scope): int {
                $start = now()->copy()->subMonths($offset)->startOfMonth();
                $end = now()->copy()->subMonths($offset)->endOfMonth();
                $query = DB::table($table)->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()]);

                if ($scope) {
                    $scope($query);
                }

                return (int) round(((float) $query->sum($amountColumn)) / 1000);
            })
            ->all();
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make('Créances totales', 'FCFA 0')
                ->description('Aucune facture disponible')
                ->color('primary')
                ->chart(array_fill(0, 7, 0)),
            Stat::make('Montants en retard', 'FCFA 0')
                ->description('Aucun retard enregistré')
                ->color('danger')
                ->chart(array_fill(0, 7, 0)),
            Stat::make('Factures en attente', '0')
                ->description('Aucune charge de recouvrement')
                ->color('warning')
                ->chart(array_fill(0, 7, 0)),
            Stat::make('Devis liés', '0')
                ->description('Aucun devis d’origine à afficher')
                ->color('success')
                ->chart(array_fill(0, 7, 0)),
        ];
    }
}
