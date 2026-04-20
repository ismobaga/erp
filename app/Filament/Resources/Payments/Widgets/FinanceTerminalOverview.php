<?php

namespace App\Filament\Resources\Payments\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FinanceTerminalOverview extends Widget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.payments.widgets.finance-terminal-overview';

    protected function getViewData(): array
    {
        try {
            if (!Schema::hasTable('invoices') || !Schema::hasTable('payments')) {
                return $this->placeholderData();
            }

            $readyToPay = (float) Invoice::query()
                ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
                ->sum('balance_due');

            $approvedInvoices = Invoice::query()
                ->whereIn('status', ['sent', 'partially_paid', 'paid'])
                ->count();

            $flagged = Payment::query()
                ->where(fn($query) => $query->whereNull('invoice_id')->orWhereNull('reference')->orWhere('reference', ''))
                ->count();

            return [
                'readyToPay' => $this->money($readyToPay),
                'approvedInvoices' => number_format($approvedInvoices),
                'flaggedReviews' => number_format($flagged),
            ];
        } catch (Throwable) {
            return $this->placeholderData();
        }
    }

    protected function placeholderData(): array
    {
        return [
            'readyToPay' => 'FCFA 0.00',
            'approvedInvoices' => '0',
            'flaggedReviews' => '0',
        ];
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 2, '.', ' ');
    }
}
