<?php

namespace App\Filament\Resources\RecurringInvoices\Pages;

use App\Filament\Resources\RecurringInvoices\RecurringInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListRecurringInvoices extends ListRecords
{
    protected static string $resource = RecurringInvoiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Factures récurrentes';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouveau modèle récurrent'),
        ];
    }
}
