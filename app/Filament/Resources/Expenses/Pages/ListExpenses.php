<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Dépenses & remboursements';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle dépense'),
        ];
    }
}
