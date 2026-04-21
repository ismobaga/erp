<?php

namespace App\Filament\Resources\Ledger\Pages;

use App\Filament\Resources\Ledger\JournalEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;
}
