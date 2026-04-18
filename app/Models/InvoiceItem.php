<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'service_id',
    'description',
    'quantity',
    'unit_price',
    'line_total',
])]
class InvoiceItem extends Model
{
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
        static::saving(function (InvoiceItem $item): void {
            $item->line_total = (float) $item->quantity * (float) $item->unit_price;
        });

        static::saved(function (InvoiceItem $item): void {
            $item->invoice?->recalculateTotals();
        });

        static::deleted(function (InvoiceItem $item): void {
            $item->invoice?->recalculateTotals();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
