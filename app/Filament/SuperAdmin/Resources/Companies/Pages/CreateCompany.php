<?php

namespace App\Filament\SuperAdmin\Resources\Companies\Pages;

use App\Filament\SuperAdmin\Resources\Companies\CompanyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Créer une société';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Enregistrer la société');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Annuler');
    }
}
