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
        return 'Registre des factures';
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
                ->label('Envoyer les rappels')
                ->visible(fn(): bool => auth()->user()?->can('invoices.update') ?? false)
                ->action(function (): void {
                    $total = Invoice::query()->whereIn('status', ['sent', 'overdue', 'partially_paid'])->count();

                    Notification::make()
                        ->title($total > 0 ? 'Lot de rappels préparé pour ' . $total . ' facture(s).' : 'Aucune facture n’attend actuellement de rappel.')
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Nouvelle facture'),
        ];
    }
}
