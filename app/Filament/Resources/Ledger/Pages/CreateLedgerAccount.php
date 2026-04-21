<?php

namespace App\Filament\Resources\Ledger\Pages;

use App\Filament\Resources\Ledger\LedgerAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLedgerAccount extends CreateRecord
{
    protected static string $resource = LedgerAccountResource::class;
}
