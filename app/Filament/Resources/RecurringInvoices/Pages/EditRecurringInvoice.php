<?php

namespace App\Filament\Resources\RecurringInvoices\Pages;

use App\Filament\Resources\RecurringInvoices\RecurringInvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditRecurringInvoice extends EditRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Modifier le modèle récurrent';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Enregistrer les modifications');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Annuler');
    }
}
