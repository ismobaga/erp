<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Ajouter un membre du personnel';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Enregistrer la fiche');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Annuler');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Admin-created users are trusted — mark email as verified immediately
        // so they can log in without clicking a verification link.
        if (filled($data['email'] ?? null)) {
            $data['email_verified_at'] ??= Carbon::now();
        }

        return $data;
    }
}
