<?php

namespace App\Filament\SuperAdmin\Resources\Companies\Pages;

use App\Filament\SuperAdmin\Resources\Companies\CompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Gestion des sociétés';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle société'),
        ];
    }
}
