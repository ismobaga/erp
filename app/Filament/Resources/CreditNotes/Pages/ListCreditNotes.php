<?php

namespace App\Filament\Resources\CreditNotes\Pages;

use App\Filament\Resources\CreditNotes\CreditNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCreditNotes extends ListRecords
{
    protected static string $resource = CreditNoteResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Registre des avoirs';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvel avoir'),
        ];
    }
}
