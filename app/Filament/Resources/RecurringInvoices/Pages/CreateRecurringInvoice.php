<?php

namespace App\Filament\Resources\RecurringInvoices\Pages;

use App\Filament\Resources\RecurringInvoices\RecurringInvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateRecurringInvoice extends CreateRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Nouveau modèle de facturation récurrente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Enregistrer le modèle');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Annuler');
    }
}
