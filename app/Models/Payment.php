<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'invoice_id',
    'client_id',
    'payment_date',
    'amount',
    'payment_method',
    'reference',
    'notes',
    'allow_overpayment',
    'recorded_by',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'allow_overpayment' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            if ((float) $payment->amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be positive.']);
            }

            if ($payment->invoice_id === null) {
                return;
            }

            $invoice = $payment->invoice()->lockForUpdate()->first();
            if ($invoice === null || $payment->allow_overpayment) {
                return;
            }

            $otherPayments = (float) $invoice->payments()
                ->when($payment->exists, fn ($query) => $query->whereKeyNot($payment->getKey()))
                ->sum('amount');

            if ($otherPayments + (float) $payment->amount > (float) $invoice->total) {
                throw ValidationException::withMessages([
                    'amount' => 'Total paid cannot exceed invoice total unless overpayment is explicitly allowed.',
                ]);
            }
        });

        static::saved(function (Payment $payment): void {
            $payment->invoice?->refreshFinancials();
        });

        static::deleted(function (Payment $payment): void {
            $payment->invoice?->refreshFinancials();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
