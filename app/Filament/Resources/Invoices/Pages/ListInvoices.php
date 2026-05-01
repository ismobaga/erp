<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Actions\SendInvoiceRemindersAction;
use App\Filament\Actions\SendWhatsAppReminderAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Widgets\InvoiceLedgerStats;
use Filament\Actions\CreateAction;
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
            // Reminder dispatch is encapsulated in its own action class so
            // it can be tested and reused without the ListInvoices page.
            SendInvoiceRemindersAction::make(),
            SendWhatsAppReminderAction::make(),
            CreateAction::make()->label('Nouvelle facture'),
        ];
    }
}
