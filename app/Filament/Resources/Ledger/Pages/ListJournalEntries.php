<?php

namespace App\Filament\Resources\Ledger\Pages;

use App\Filament\Resources\Ledger\JournalEntryResource;
use App\Filament\Resources\Ledger\Widgets\JournalEntryStats;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('erp.ledger.journal_entries');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            JournalEntryStats::class,
        ];
    }
}
