<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_number',
    'client_id',
    'quote_id',
    'issue_date',
    'due_date',
    'status',
    'subtotal',
    'discount_total',
    'tax_total',
    'total',
    'paid_total',
    'balance_due',
    'notes',
    'created_by',
    'updated_by',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $total = max(0, $subtotal - (float) $this->discount_total + (float) $this->tax_total);

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => $total,
        ])->saveQuietly();

        $this->refreshFinancials();
    }

    public function refreshFinancials(): void
    {
        $paidTotal = (float) $this->payments()->sum('amount');
        $balanceDue = max(0, (float) $this->total - $paidTotal);
        $isSettled = $balanceDue <= 0.00001;

        $status = 'sent';
        if ($isSettled && (float) $this->total > 0) {
            $status = 'paid';
        } elseif ($paidTotal > 0.0 && $balanceDue > 0.0) {
            $status = 'partially_paid';
        } elseif ($balanceDue > 0.0 && $this->due_date !== null && now()->greaterThan($this->due_date)) {
            $status = 'overdue';
        }

        if ($this->status === 'cancelled') {
            $status = 'cancelled';
        }

        $this->forceFill([
            'paid_total' => $paidTotal,
            'balance_due' => $balanceDue,
            'status' => $status,
        ])->saveQuietly();
    }
}
