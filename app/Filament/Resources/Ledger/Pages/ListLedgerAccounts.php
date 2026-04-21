<?php

namespace App\Filament\Resources\Ledger\Pages;

use App\Filament\Resources\Ledger\LedgerAccountResource;
use Filament\Resources\Pages\ListRecords;

class ListLedgerAccounts extends ListRecords
{
    protected static string $resource = LedgerAccountResource::class;
}
