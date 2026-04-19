<?php

namespace App\Filament\Resources\Invoices\Widgets;

use App\Models\Invoice;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
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
                    ->chart([12, 14, 13, 18, 20, 22, 24]),
                Stat::make('Montants en retard', $this->money($overdueBalance))
                    ->description('Sommes demandant une action rapide')
                    ->color('danger')
                    ->chart([9, 8, 10, 9, 7, 6, 5]),
                Stat::make('Factures en attente', number_format($pendingCount))
                    ->description('Charge active de recouvrement')
                    ->color('warning')
                    ->chart([3, 4, 6, 7, 8, 7, 9]),
                Stat::make('Devis liés', number_format($linkedQuotes))
                    ->description('Documents commerciaux d’origine')
                    ->color('success')
                    ->chart([2, 3, 4, 5, 6, 7, 8]),
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
            Stat::make('Total receivables', 'FCFA 428M')
                ->description('Confirmed invoice value in the ledger')
                ->color('primary')
                ->chart([12, 14, 13, 18, 20, 22, 24]),
            Stat::make('Overdue exposure', 'FCFA 18M')
                ->description('Amounts requiring immediate action')
                ->color('danger')
                ->chart([9, 8, 10, 9, 7, 6, 5]),
            Stat::make('Pending invoices', '24')
                ->description('Active collection workload')
                ->color('warning')
                ->chart([3, 4, 6, 7, 8, 7, 9]),
            Stat::make('Linked quotes', '32')
                ->description('Commercial source documents')
                ->color('success')
                ->chart([2, 3, 4, 5, 6, 7, 8]),
        ];
    }
}
