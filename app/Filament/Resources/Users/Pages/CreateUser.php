<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Add Staff Member';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Save Staff Record');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Discard Draft');
    }
}
