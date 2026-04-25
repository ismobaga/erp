<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'code',
    'name',
    'type',
    'category',
    'description',
    'normal_balance',
    'is_active',
    'parent_id',
])]
class LedgerAccount extends Model
{
    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(LedgerAccount::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function debitBalance(): float
    {
        return (float) $this->journalLines()
            ->whereHas('entry', fn(Builder $q) => $q->where('status', 'posted'))
            ->sum('debit');
    }

    public function creditBalance(): float
    {
        return (float) $this->journalLines()
            ->whereHas('entry', fn(Builder $q) => $q->where('status', 'posted'))
            ->sum('credit');
    }

    public function netBalance(): float
    {
        $debit = $this->debitBalance();
        $credit = $this->creditBalance();

        return $this->normal_balance === 'debit'
            ? $debit - $credit
            : $credit - $debit;
    }

    public function typeLabel(): string
    {
        return (string) __('erp.ledger.account_types.' . $this->type, [], null) ?: ucfirst((string) $this->type);
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
