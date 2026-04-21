<?php

namespace App\Filament\Resources\Ledger\Pages;

use App\Filament\Resources\Ledger\JournalEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;
}
