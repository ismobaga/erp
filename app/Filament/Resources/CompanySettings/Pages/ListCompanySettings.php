<?php

namespace App\Filament\Resources\CompanySettings\Pages;

use App\Filament\Resources\CompanySettings\CompanySettingResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCompanySettings extends ListRecords
{
    protected static string $resource = CompanySettingResource::class;

    public function mount(): void
    {
        parent::mount();

        $company = currentCompany();

        if ($company) {
            $this->redirect($this->getResource()::getUrl('edit', ['record' => $company]));
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Paramètres société';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
