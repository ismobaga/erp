<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'entry_number',
    'entry_date',
    'description',
    'status',
    'source_type',
    'source_id',
    'financial_period_id',
    'created_by',
    'posted_by',
    'posted_at',
    'voided_by',
    'voided_at',
    'void_reason',
    'reversal_of',
])]
class JournalEntry extends Model
{
    use HasCompanyScope;
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function financialPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class);
    }

    /**
     * The original entry that this entry reverses (if this is a reversal).
     */
    public function originalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of');
    }

    /**
     * The reversal entry created for this entry (if one exists).
     */
    public function reversal(): HasOne
    {
        return $this->hasOne(JournalEntry::class, 'reversal_of');
    }

    public function isReversal(): bool
    {
        return $this->reversal_of !== null;
    }

    public function hasReversal(): bool
    {
        return $this->reversal()->exists();
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', 'posted');
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('status', 'voided');
    }

    public function isBalanced(): bool
    {
        $debitTotal = (float) $this->lines->sum('debit');
        $creditTotal = (float) $this->lines->sum('credit');

        return abs($debitTotal - $creditTotal) < 0.005;
    }

    public function totalDebit(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function totalCredit(): float
    {
        return (float) $this->lines->sum('credit');
    }

    public function post(?int $userId = null): void
    {
        $this->loadMissing('lines');

        if (!$this->isBalanced()) {
            throw ValidationException::withMessages([
                'lines' => __('erp.ledger.unbalanced_error'),
            ]);
        }

        FinancialPeriod::ensureDateIsOpen($this->entry_date, 'journal entry');

        $this->forceFill([
            'status' => 'posted',
            'posted_by' => $userId,
            'posted_at' => now(),
        ])->save();

        app(AuditTrailService::class)->log('journal_entry_posted', $this, [
            'entry_number' => $this->entry_number,
            'entry_date' => $this->entry_date?->toDateString(),
            'description' => $this->description,
        ], $userId);
    }

    public function void(?int $userId = null, ?string $reason = null): void
    {
        if ($this->status === 'voided') {
            return;
        }

        $this->forceFill([
            'status' => 'voided',
            'voided_by' => $userId,
            'voided_at' => now(),
            'void_reason' => $reason,
        ])->save();

        app(AuditTrailService::class)->log('journal_entry_voided', $this, [
            'entry_number' => $this->entry_number,
            'void_reason' => $reason,
        ], $userId);
    }

    public function statusLabel(): string
    {
        return (string) __('erp.ledger.statuses.' . $this->status, [], null) ?: ucfirst((string) $this->status);
    }
}
