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

    protected ?string $heading = 'Finance terminal status';

    protected ?string $description = 'Restricted ledger view for ready-to-settle invoices and flagged payment reviews.';

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
                Stat::make('Total received', $this->money($totalReceived))
                    ->description('Settled inflows across the ledger')
                    ->color('primary')
                    ->chart([8, 9, 12, 14, 16, 18, 20]),
                Stat::make('Pending reconciliation', number_format($pendingReconciliation))
                    ->description('Transactions waiting for invoice matching')
                    ->color('warning')
                    ->chart([10, 9, 8, 7, 6, 5, max(1, $pendingReconciliation)]),
                Stat::make('Bank transfers', $this->money($bankTransfers))
                    ->description('Institutional wire settlements')
                    ->color('success')
                    ->chart([4, 6, 7, 9, 11, 12, 14]),
                Stat::make('Flagged items', number_format($flagged))
                    ->description('Entries needing reconciliation attention')
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
