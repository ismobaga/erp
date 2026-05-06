<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Créer un client';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $companyId = app()->bound('currentCompany')
            ? app('currentCompany')->id
            : (session('current_company_id') ?: auth()->user()?->companies()->value('companies.id'));

        if (blank($companyId)) {
            throw ValidationException::withMessages([
                'company_id' => 'Aucune entreprise active n\'a ete trouvee. Selectionnez une entreprise avant de creer un client.',
            ]);
        }

        $data['company_id'] = (int) $companyId;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Enregistrer le client');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Annuler');
    }
}
