<?php

namespace App\Models;

use App\Services\AuditTrailService;
use App\Services\InvoiceNumberService;
use App\Services\TaxProfileResolver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice): void {
            FinancialPeriod::ensureDateIsOpen($invoice->issue_date, 'invoice');

            if ($invoice->exists && $invoice->isDirty('invoice_number')) {
                throw ValidationException::withMessages([
                    'invoice_number' => 'Invoice numbers are immutable once assigned.',
                ]);
            }
        });

        static::creating(function (Invoice $invoice): void {
            if (blank($invoice->invoice_number)) {
                $invoice->invoice_number = app(InvoiceNumberService::class)->generate($invoice->issue_date);
            }
        });

        static::created(function (Invoice $invoice): void {
            app(AuditTrailService::class)->log('invoice_number_assigned', $invoice, [
                'invoice_number' => $invoice->invoice_number,
                'issue_date' => optional($invoice->issue_date)->toDateString(),
            ], $invoice->created_by);
        });

        static::deleting(function (Invoice $invoice): void {
            FinancialPeriod::ensureDateIsOpen($invoice->issue_date, 'invoice');
        });
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

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $taxComputation = app(TaxProfileResolver::class)->calculateForClient($subtotal, $this->client);
        $taxTotal = $taxComputation['matched'] ? (float) $taxComputation['tax_total'] : (float) $this->tax_total;
        $baseTotal = $taxComputation['matched'] && (($taxComputation['profile']['mode'] ?? 'exclusive') === 'inclusive')
            ? (float) $taxComputation['total']
            : $subtotal + $taxTotal;
        $total = max(0, $baseTotal - (float) $this->discount_total);

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ])->saveQuietly();

        $this->refreshFinancials();
    }

    public function refreshCreditBalance(): void
    {
        $creditedTotal = (float) $this->creditNotes()->sum('amount');
        $updatedDiscount = $creditedTotal;
        $currentSubtotal = max((float) $this->subtotal, (float) $this->total + (float) $this->discount_total - (float) $this->tax_total);
        $taxComputation = app(TaxProfileResolver::class)->calculateForClient($currentSubtotal, $this->client);
        $taxTotal = $taxComputation['matched'] ? (float) $taxComputation['tax_total'] : (float) $this->tax_total;
        $baseTotal = $taxComputation['matched'] && (($taxComputation['profile']['mode'] ?? 'exclusive') === 'inclusive')
            ? (float) $taxComputation['total']
            : $currentSubtotal + $taxTotal;

        $this->forceFill([
            'discount_total' => $updatedDiscount,
            'tax_total' => $taxTotal,
            'total' => max(0, $baseTotal - $updatedDiscount),
        ])->saveQuietly();

        $this->refreshFinancials();
    }

    public function refreshFinancials(): void
    {
        $paidTotal = (float) $this->payments()->sum('amount');
        $balanceDue = max(0, (float) $this->total - $paidTotal);
        $isSettled = $balanceDue <= 0.00001;
        $overdueGraceDays = max(0, (int) config('erp.billing.overdue_grace_days', 0));
        $overdueAt = $this->due_date !== null
            ? now()->parse($this->due_date)->endOfDay()->addDays($overdueGraceDays)
            : null;

        $status = 'sent';
        if ($isSettled && (float) $this->total > 0) {
            $status = 'paid';
        } elseif ($paidTotal > 0.0 && $balanceDue > 0.0) {
            $status = 'partially_paid';
        } elseif ($balanceDue > 0.0 && $overdueAt !== null && now()->greaterThan($overdueAt)) {
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
