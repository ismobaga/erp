<?php

namespace App\Filament\Resources\Ledger\Pages;

use App\Filament\Resources\Ledger\LedgerAccountResource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListLedgerAccounts extends ListRecords
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->withSum([
                'journalLines as debit_sum' => fn(Builder $q) => $q->whereHas(
                    'entry',
                    fn(Builder $e) => $e->where('status', 'posted')
                ),
            ], 'debit')
            ->withSum([
                'journalLines as credit_sum' => fn(Builder $q) => $q->whereHas(
                    'entry',
                    fn(Builder $e) => $e->where('status', 'posted')
                ),
            ], 'credit');
    }
}
