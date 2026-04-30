<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'journal_entry_id',
    'account_id',
    'description',
    'debit',
    'credit',
])]
class JournalEntryLine extends Model
{
    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (JournalEntryLine $line): void {
            $debit  = (float) $line->debit;
            $credit = (float) $line->credit;

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    'debit'  => 'Debit amount must be non-negative.',
                    'credit' => 'Credit amount must be non-negative.',
                ]);
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    'debit'  => 'A journal entry line cannot have both a debit and a credit amount.',
                    'credit' => 'A journal entry line cannot have both a debit and a credit amount.',
                ]);
            }
        });
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }
}
