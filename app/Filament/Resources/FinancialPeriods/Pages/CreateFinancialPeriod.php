<?php

namespace App\Filament\Resources\FinancialPeriods\Pages;

use App\Filament\Resources\FinancialPeriods\FinancialPeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialPeriod extends CreateRecord
{
    protected static string $resource = FinancialPeriodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = $data['code'] ?: FinancialPeriodResource::generatePeriodCode($data['starts_on'] ?? null);

        return $data;
    }
}
