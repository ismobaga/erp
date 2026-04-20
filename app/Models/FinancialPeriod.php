<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'name',
    'code',
    'starts_on',
    'ends_on',
    'status',
    'closed_at',
    'closed_by',
    'reopened_at',
    'reopened_by',
    'notes',
])]
class FinancialPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function containsDate(CarbonInterface|string $date): bool
    {
        $subjectDate = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return $subjectDate->betweenIncluded(
            $this->starts_on instanceof CarbonInterface ? $this->starts_on : Carbon::parse($this->starts_on),
            $this->ends_on instanceof CarbonInterface ? $this->ends_on : Carbon::parse($this->ends_on),
        );
    }

    public function close(?int $userId = null, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $userId,
            'notes' => $notes ?? $this->notes,
        ])->save();
    }

    public function reopen(?int $userId = null, ?string $notes = null): void
    {
        $this->forceFill([
            'status' => 'open',
            'reopened_at' => now(),
            'reopened_by' => $userId,
            'notes' => $notes ?? $this->notes,
        ])->save();
    }

    public static function findClosedFor(CarbonInterface|string|null $date): ?self
    {
        if (blank($date)) {
            return null;
        }

        return static::query()
            ->closed()
            ->current($date)
            ->first();
    }

    public static function ensureDateIsOpen(CarbonInterface|string|null $date, string $recordLabel = 'record'): void
    {
        $period = static::findClosedFor($date);

        if ($period === null) {
            return;
        }

        throw ValidationException::withMessages([
            'financial_period' => [
                sprintf(
                    'This %s belongs to a closed accounting period (%s) and can no longer be modified.',
                    $recordLabel,
                    $period->name,
                ),
            ],
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeCurrent(Builder $query, CarbonInterface|string|null $date = null): Builder
    {
        $subjectDate = $date instanceof CarbonInterface
            ? $date->toDateString()
            : Carbon::parse($date ?? now())->toDateString();

        return $query
            ->whereDate('starts_on', '<=', $subjectDate)
            ->whereDate('ends_on', '>=', $subjectDate);
    }
}
