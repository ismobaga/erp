<?php

namespace App\Filament\Resources\CompanySettings\Pages;

use App\Filament\Resources\CompanySettings\CompanySettingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateCompanySetting extends CreateRecord
{
    protected static string $resource = CompanySettingResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Initialize Company Settings';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Save Changes');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Discard Changes');
    }
}
