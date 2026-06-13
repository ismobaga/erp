<?php

namespace App\Filament\SuperAdmin\Resources\Companies\Pages;

use App\Filament\SuperAdmin\Resources\Companies\CompanyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Modifier la société';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Supprimer'),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Enregistrer les modifications');
    }
}
