<?php

namespace App\Models;

use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
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
    'is_flagged',
    'flagged_at',
    'flagged_reason',
    'flagged_by',
])]
class Payment extends Model
{
    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'allow_overpayment' => 'boolean',
            'is_flagged' => 'boolean',
            'flagged_at' => 'datetime',
        ];
    }

    public function flag(string $reason, int $userId): void
    {
        $this->forceFill([
            'is_flagged' => true,
            'flagged_at' => now(),
            'flagged_reason' => $reason,
            'flagged_by' => $userId,
        ])->saveQuietly();

        app(AuditTrailService::class)->log('payment_flagged', $this, [
            'reference' => $this->reference ?: 'N/A',
            'reason' => $reason,
            'flagged_by' => $userId,
        ]);
    }

    public function unflag(int $userId): void
    {
        $this->forceFill([
            'is_flagged' => false,
            'flagged_at' => null,
            'flagged_reason' => null,
            'flagged_by' => null,
        ])->saveQuietly();

        app(AuditTrailService::class)->log('payment_unflagged', $this, [
            'reference' => $this->reference ?: 'N/A',
            'unflagged_by' => $userId,
        ]);
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            $payment->payment_method = static::normalizePaymentMethod($payment->payment_method);

            if ((float) $payment->amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be positive.']);
            }

            $referenceRequiredMethods = array_map(
                static fn(string $method): string => static::normalizePaymentMethod($method),
                config('erp.billing.payment_reference_required_methods', [])
            );

            if (in_array((string) $payment->payment_method, $referenceRequiredMethods, true) && blank($payment->reference)) {
                throw ValidationException::withMessages([
                    'reference' => 'A payment reference is required for traceable payment methods.',
                ]);
            }

            FinancialPeriod::ensureDateIsOpen($payment->payment_date, 'payment');

            if ($payment->invoice_id === null) {
                return;
            }

            /** @var Invoice|null $invoice */
            $invoice = Invoice::query()->whereKey($payment->invoice_id)->lockForUpdate()->first();

            if ($invoice === null) {
                return;
            }

            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Cancelled invoices cannot accept new payments.',
                ]);
            }

            if (
                $payment->payment_date !== null
                && $invoice->issue_date !== null
                && now()->parse((string) $payment->payment_date)->lt(now()->parse((string) $invoice->issue_date))
            ) {
                throw ValidationException::withMessages([
                    'payment_date' => 'Payment date cannot be earlier than the invoice issue date.',
                ]);
            }

            if ($payment->allow_overpayment) {
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

    protected static function normalizePaymentMethod(mixed $method): string
    {
        $normalized = Str::of((string) $method)
            ->lower()
            ->trim()
            ->replace(['-', ' '], '_')
            ->value();

        return match ($normalized) {
            'bank_transfer', 'wire_transfer' => 'bank_transfer',
            'cash' => 'cash',
            'check', 'cheque' => 'check',
            'mobile_money', 'mobilemoney' => 'mobile_money',
            default => $normalized,
        };
    }
}
