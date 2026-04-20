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

            FinancialPeriod::ensureDateIsOpen($payment->payment_date, 'payment');

            if ($payment->invoice_id === null) {
                return;
            }

            /** @var Invoice|null $invoice */
            $invoice = Invoice::query()->whereKey($payment->invoice_id)->lockForUpdate()->first();

            if ($invoice === null || $payment->allow_overpayment) {
                return;
            }

            $otherPayments = (float) $invoice->payments()
                ->when($payment->exists, fn($query) => $query->whereKeyNot($payment->getKey()))
                ->sum('amount');

            if ($otherPayments + (float) $payment->amount > (float) $invoice->total) {
                throw ValidationException::withMessages([
                    'amount' => 'Total paid cannot exceed invoice total unless overpayment is explicitly allowed.',
                ]);
            }
        });

        static::deleting(function (Payment $payment): void {
            FinancialPeriod::ensureDateIsOpen($payment->payment_date, 'payment');
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

    public function reconcileAgainstOpenInvoice(): bool
    {
        if ($this->invoice_id !== null) {
            $this->invoice?->refreshFinancials();

            return true;
        }

        if ($this->client_id === null) {
            return false;
        }

        $candidate = Invoice::query()
            ->where('client_id', $this->client_id)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('balance_due', '>', 0)
            ->when(!$this->allow_overpayment, fn($query) => $query->where('balance_due', '>=', (float) $this->amount))
            ->orderByRaw('case when due_date is null then 1 else 0 end')
            ->orderBy('due_date')
            ->orderBy('issue_date')
            ->first();

        if (!$candidate) {
            return false;
        }

        $this->invoice()->associate($candidate);
        $this->save();

        return true;
    }

    public function reconciliationState(): string
    {
        if ($this->invoice_id === null) {
            return blank($this->reference) ? 'flagged' : 'pending';
        }

        return blank($this->reference) ? 'flagged' : 'completed';
    }
}
