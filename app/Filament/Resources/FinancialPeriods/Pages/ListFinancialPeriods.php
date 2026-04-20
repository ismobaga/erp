<?php

namespace App\Filament\Resources\FinancialPeriods\Pages;

use App\Filament\Resources\FinancialPeriods\FinancialPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListFinancialPeriods extends ListRecords
{
    protected static string $resource = FinancialPeriodResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Périodes comptables';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle période'),
        ];
    }
}
