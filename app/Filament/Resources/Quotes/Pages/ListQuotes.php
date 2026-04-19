<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Registre des devis';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouveau devis'),
        ];
    }
}
