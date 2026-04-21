<?php

namespace App\Filament\Resources\Ledger\Pages;

use App\Filament\Resources\Ledger\JournalEntryResource;
use Filament\Resources\Pages\EditRecord;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;
}
