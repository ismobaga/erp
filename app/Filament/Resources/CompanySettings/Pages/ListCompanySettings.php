<?php

namespace App\Filament\Resources\CompanySettings\Pages;

use App\Filament\Resources\CompanySettings\CompanySettingResource;
use App\Models\CompanySetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCompanySettings extends ListRecords
{
    protected static string $resource = CompanySettingResource::class;

    public function mount(): void
    {
        parent::mount();

        $record = CompanySetting::query()->first();

        if ($record) {
            $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));

            return;
        }

        $this->redirect($this->getResource()::getUrl('create'));
    }

    public function getTitle(): string|Htmlable
    {
        return 'Paramètres société';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Initialiser les paramètres'),
        ];
    }
}
