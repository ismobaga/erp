<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quote_number',
    'client_id',
    'issue_date',
    'valid_until',
    'status',
    'subtotal',
    'discount_total',
    'tax_total',
    'total',
    'notes',
    'created_by',
    'updated_by',
])]
class Quote extends Model
{
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $total = max(0, $subtotal - (float) $this->discount_total + (float) $this->tax_total);

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => $total,
        ])->saveQuietly();
    }

    public function canBeAccepted(?CarbonInterface $at = null): bool
    {
        if ($this->status !== 'expired') {
            return true;
        }

        if ($this->valid_until === null) {
            return false;
        }

        return $this->valid_until->isFuture();
    }
}
