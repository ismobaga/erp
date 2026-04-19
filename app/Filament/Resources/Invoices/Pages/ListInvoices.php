<?php

namespace App\Filament\Resources\Invoices\Pages;

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
            CreateAction::make()->label('New invoice'),
        ];
    }
}
