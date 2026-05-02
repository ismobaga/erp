<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'quote_id',
    'service_id',
    'description',
    'quantity',
    'unit_price',
    'line_total',
])]
class QuoteItem extends Model
{
    use HasCompanyScope;
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuoteItem $item): void {
            if ((float) $item->quantity <= 0) {
                throw ValidationException::withMessages(['quantity' => 'La quantité doit être supérieure à zéro.']);
            }
            // Use BCMath to avoid float precision errors when multiplying
            // quantity × unit_price for high-value or fractional amounts.
            $item->line_total = Money::of((string) $item->quantity)
                ->multiply((string) $item->unit_price)
                ->toString();
        });

        static::saved(function (QuoteItem $item): void {
            $item->quote?->recalculateTotals();
        });

        static::deleted(function (QuoteItem $item): void {
            $item->quote?->recalculateTotals();
        });
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
