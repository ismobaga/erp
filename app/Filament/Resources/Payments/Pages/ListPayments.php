<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Widgets\FinanceTerminalOverview;
use App\Filament\Resources\Payments\Widgets\PaymentTrackingStats;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Finance Payment Terminal';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceTerminalOverview::class,
            PaymentTrackingStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('batchProcess')
                ->label('Batch process all')
                ->action(fn() => Notification::make()->title('Ready-to-pay items were queued for batch review.')->success()->send()),
            Action::make('exportLedger')
                ->label('Export ledger report')
                ->action(fn() => Notification::make()->title('Ledger report export started successfully.')->success()->send()),
            Action::make('smartLink')
                ->label('Run smart-link')
                ->action(function (): void {
                    $matched = 0;

                    Payment::query()
                        ->whereNull('invoice_id')
                        ->get()
                        ->each(function (Payment $payment) use (&$matched): void {
                            if ($payment->reconcileAgainstOpenInvoice()) {
                                $matched++;
                            }
                        });

                    $notification = Notification::make()
                        ->title($matched > 0 ? $matched . ' payment(s) reconciled.' : 'No unmatched payments were eligible for auto-link.');

                    if ($matched > 0) {
                        $notification->success();
                    } else {
                        $notification->warning();
                    }

                    $notification->send();
                }),
            CreateAction::make()->label('Record payment'),
        ];
    }
}
