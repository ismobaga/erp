<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Registre clients';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouveau client'),
        ];
    }
}
