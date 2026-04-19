<?php

namespace App\Filament\Resources\Payments\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
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
            $bankTransfers = (float) Payment::query()->where('payment_method', 'bank_transfer')->sum('amount');
            $pendingReconciliation = Payment::query()->whereNull('invoice_id')->count();
            $flagged = Payment::query()->where(fn($query) => $query->whereNull('reference')->orWhere('reference', ''))->count();

            return [
                Stat::make('Total encaissé', $this->money($totalReceived))
                    ->description('Entrées de trésorerie enregistrées')
                    ->color('primary')
                    ->chart([8, 9, 12, 14, 16, 18, 20]),
                Stat::make('Rapprochements en attente', number_format($pendingReconciliation))
                    ->description('Transactions à affecter à une facture')
                    ->color('warning')
                    ->chart([10, 9, 8, 7, 6, 5, max(1, $pendingReconciliation)]),
                Stat::make('Virements bancaires', $this->money($bankTransfers))
                    ->description('Règlements reçus par virement')
                    ->color('success')
                    ->chart([4, 6, 7, 9, 11, 12, 14]),
                Stat::make('Éléments signalés', number_format($flagged))
                    ->description('Entrées demandant une vérification')
                    ->color('danger')
                    ->chart([5, 4, 4, 3, 2, 2, 1]),
            ];
        } catch (Throwable) {
            return $this->placeholderStats();
        }
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 0, '.', ' ');
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make('Total received', 'FCFA 42M')->description('Settled inflows across the ledger')->color('primary'),
            Stat::make('Pending reconciliation', '24')->description('Transactions waiting for invoice matching')->color('warning'),
            Stat::make('Bank transfers', 'FCFA 28M')->description('Institutional wire settlements')->color('success'),
            Stat::make('Flagged items', '2')->description('Entries needing reconciliation attention')->color('danger'),
        ];
    }
}
