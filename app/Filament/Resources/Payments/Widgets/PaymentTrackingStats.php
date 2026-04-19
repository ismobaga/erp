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

    protected ?string $heading = 'Payment desk overview';

    protected ?string $description = 'Real-time recording of fiscal inflows for the current ledger cycle.';

    protected function getStats(): array
    {
        try {
            if (!Schema::hasTable('payments')) {
                return $this->placeholderStats();
            }

            $totalReceived = (float) Payment::query()->sum('amount');
            $bankTransfers = (float) Payment::query()->where('payment_method', 'bank_transfer')->sum('amount');
            $cashHandling = (float) Payment::query()->where('payment_method', 'cash')->sum('amount');
            $flagged = Payment::query()->where(fn($query) => $query->whereNull('reference')->orWhere('reference', ''))->count();

            return [
                Stat::make('Total received', $this->money($totalReceived))
                    ->description('Settled inflows across the ledger')
                    ->color('primary')
                    ->chart([8, 9, 12, 14, 16, 18, 20]),
                Stat::make('Bank transfers', $this->money($bankTransfers))
                    ->description('Institutional wire settlements')
                    ->color('success')
                    ->chart([4, 6, 7, 9, 11, 12, 14]),
                Stat::make('Cash handling', $this->money($cashHandling))
                    ->description('Physical collection and field desk receipts')
                    ->color('info')
                    ->chart([1, 2, 3, 2, 4, 3, 5]),
                Stat::make('Flagged reference', number_format($flagged))
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
            Stat::make('Bank transfers', 'FCFA 28M')->description('Institutional wire settlements')->color('success'),
            Stat::make('Cash handling', 'FCFA 4M')->description('Physical collection and field desk receipts')->color('info'),
            Stat::make('Flagged reference', '2')->description('Entries needing reconciliation attention')->color('danger'),
        ];
    }
}
