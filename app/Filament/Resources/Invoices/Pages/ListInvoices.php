<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Widgets\InvoiceLedgerStats;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Invoice Ledger';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceLedgerStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendReminders')
                ->label('Send reminders')
                ->action(function (): void {
                    $total = Invoice::query()->whereIn('status', ['sent', 'overdue', 'partially_paid'])->count();

                    Notification::make()
                        ->title($total > 0 ? 'Reminder batch prepared for ' . $total . ' invoice(s).' : 'There are no invoices waiting for reminders.')
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('New invoice'),
        ];
    }
}
