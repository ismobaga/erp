<?php

namespace App\Models;

use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'invoice_id',
    'credit_number',
    'issue_date',
    'amount',
    'reason',
    'status',
    'created_by',
    'updated_by',
])]
class CreditNote extends Model
{
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CreditNote $creditNote): void {
            FinancialPeriod::ensureDateIsOpen($creditNote->issue_date, 'credit note');

            if ((float) $creditNote->amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Credit note amount must be positive.',
                ]);
            }

            $invoice = $creditNote->invoice;

            if (!$invoice) {
                return;
            }

            $otherCredits = (float) $invoice->creditNotes()
                ->when($creditNote->exists, fn($query) => $query->whereKeyNot($creditNote->getKey()))
                ->sum('amount');

            $invoiceCap = max(
                (float) $invoice->total,
                (float) $invoice->subtotal + (float) $invoice->tax_total,
                (float) $invoice->balance_due,
            );

            if ($otherCredits + (float) $creditNote->amount > $invoiceCap) {
                throw ValidationException::withMessages([
                    'amount' => 'Credit notes cannot exceed the original invoice total.',
                ]);
            }
        });

        static::created(function (CreditNote $creditNote): void {
            $creditNote->invoice?->refreshCreditBalance();

            app(AuditTrailService::class)->log('credit_note_issued', $creditNote, [
                'invoice_number' => $creditNote->invoice?->invoice_number,
                'credit_number' => $creditNote->credit_number,
                'amount' => (float) $creditNote->amount,
                'reason' => $creditNote->reason,
            ], $creditNote->created_by);
        });

        static::deleted(function (CreditNote $creditNote): void {
            $creditNote->invoice?->refreshCreditBalance();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
