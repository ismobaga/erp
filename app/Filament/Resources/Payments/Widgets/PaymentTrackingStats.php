<?php

namespace App\Filament\Resources\Payments\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PaymentTrackingStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'État du terminal financier';

    protected ?string $description = 'Vue synthétique des paiements, rapprochements et points de contrôle.';

    protected function getStats(): array
    {
        try {
            if (!Schema::hasTable('payments')) {
                return $this->placeholderStats();
            }

            $totalReceived = (float) Payment::query()->sum('amount');
            $bankTransfers = (float) Payment::query()->whereIn('payment_method', ['bank_transfer', 'bank transfer'])->sum('amount');
            $pendingReconciliation = Payment::query()->whereNull('invoice_id')->count();
            $flagged = Payment::query()->where(fn($query) => $query->whereNull('reference')->orWhere('reference', ''))->count();

            return [
                Stat::make('Total encaissé', $this->money($totalReceived))
                    ->description('Entrées de trésorerie enregistrées')
                    ->color('primary')
                    ->chart($this->sumTrend('payments', 'payment_date', 'amount')),
                Stat::make('Rapprochements en attente', number_format($pendingReconciliation))
                    ->description('Transactions à affecter à une facture')
                    ->color('warning')
                    ->chart($this->countTrend('payments', 'payment_date', fn($query) => $query->whereNull('invoice_id'))),
                Stat::make('Virements bancaires', $this->money($bankTransfers))
                    ->description('Règlements reçus par virement')
                    ->color('success')
                    ->chart($this->sumTrend('payments', 'payment_date', 'amount', fn($query) => $query->whereIn('payment_method', ['bank_transfer', 'bank transfer']))),
                Stat::make('Éléments signalés', number_format($flagged))
                    ->description('Entrées demandant une vérification')
                    ->color('danger')
                    ->chart($this->countTrend('payments', 'payment_date', fn($query) => $query->where(fn($nested) => $nested->whereNull('reference')->orWhere('reference', '')))),
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
            Stat::make('Total encaissé', 'FCFA 0')->description('Aucun paiement enregistré')->color('primary')->chart(array_fill(0, 7, 0)),
            Stat::make('Rapprochements en attente', '0')->description('Aucune transaction en attente')->color('warning')->chart(array_fill(0, 7, 0)),
            Stat::make('Virements bancaires', 'FCFA 0')->description('Aucun virement reçu')->color('success')->chart(array_fill(0, 7, 0)),
            Stat::make('Éléments signalés', '0')->description('Aucune anomalie détectée')->color('danger')->chart(array_fill(0, 7, 0)),
        ];
    }
}
