<?php

namespace App\Filament\Resources\ContactRequests\Pages;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListContactRequests extends ListRecords
{
    protected static string $resource = ContactRequestResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Demandes de contact';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
