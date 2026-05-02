<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use App\Services\AuditTrailService;
use App\Services\InvoiceNumberService;
use App\Services\TaxProfileResolver;
use App\States\InvoiceStateMachine;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
    'credit_total',
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
    use HasCompanyScope;
    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'credit_total' => 'decimal:2',
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

    public function dunningLogs(): HasMany
    {
        return $this->hasMany(DunningLog::class);
    }

    public function recalculateTotals(): void
    {
        // Use BCMath (via Money) to avoid floating-point accumulation errors.
        $subtotal = Money::of((string) $this->items()->sum('line_total'));

        $taxComputation = app(TaxProfileResolver::class)->calculateForClient(
            $subtotal->toFloat(),
            $this->client,
        );

        $taxTotal = $taxComputation['matched']
            ? Money::of((string) $taxComputation['tax_total'])
            : Money::of((string) $this->tax_total);

        // For inclusive tax profiles the resolver already includes tax in the
        // returned total; for exclusive we add it on top of the subtotal.
        $isInclusive = $taxComputation['matched']
            && ($taxComputation['profile']['mode'] ?? 'exclusive') === 'inclusive';

        $baseTotal = $isInclusive
            ? Money::of((string) $taxComputation['total'])
            : $subtotal->add($taxTotal);

        $discount = Money::of((string) $this->discount_total);
        $total    = Money::zero()->max($baseTotal->subtract($discount));

        $this->forceFill([
            'subtotal'  => $subtotal->toString(),
            'tax_total' => $taxTotal->toString(),
            'total'     => $total->toString(),
        ])->saveQuietly();

        $this->refreshFinancials();
    }

    public function refreshCreditBalance(): void
    {
        $creditedTotal = Money::of(
            (string) $this->creditNotes()
                ->whereIn('status', ['issued', 'approved'])
                ->sum('amount'),
        );

        // Re-derive the raw subtotal by reversing the stored totals.
        $storedTotal    = Money::of((string) $this->total);
        $storedDiscount = Money::of((string) $this->discount_total);
        $storedTax      = Money::of((string) $this->tax_total);
        $storedSubtotal = Money::of((string) $this->subtotal);

        $currentSubtotal = $storedSubtotal->isZero()
            ? Money::zero()->max($storedTotal->add($storedDiscount)->subtract($storedTax))
            : $storedSubtotal;

        $taxComputation = app(TaxProfileResolver::class)->calculateForClient(
            $currentSubtotal->toFloat(),
            $this->client,
        );

        $taxTotal = $taxComputation['matched']
            ? Money::of((string) $taxComputation['tax_total'])
            : $storedTax;

        $isInclusive = $taxComputation['matched']
            && ($taxComputation['profile']['mode'] ?? 'exclusive') === 'inclusive';

        $baseTotal = $isInclusive
            ? Money::of((string) $taxComputation['total'])
            : $currentSubtotal->add($taxTotal);

        $newTotal = Money::zero()->max($baseTotal->subtract($storedDiscount)->subtract($creditedTotal));

        $this->forceFill([
            'credit_total'   => $creditedTotal->toString(),
            'tax_total'      => $taxTotal->toString(),
            'total'          => $newTotal->toString(),
        ])->saveQuietly();

        $this->refreshFinancials();
    }

    public function refreshFinancials(): void
    {
        // Use BCMath Money to avoid float precision drift when summing payments.
        $paidMoney  = Money::of((string) $this->payments()->sum('amount'));
        $totalMoney = Money::of((string) $this->total);
        $balanceMoney = Money::zero()->max($totalMoney->subtract($paidMoney));

        $paidTotal  = $paidMoney->toFloat();
        $balanceDue = $balanceMoney->toFloat();

        // Statuses that must never be silently overridden by financial recomputation.
        if ($this->status === 'cancelled') {
            $this->forceFill([
                'paid_total'  => $paidMoney->toString(),
                'balance_due' => $balanceMoney->toString(),
            ])->saveQuietly();

            return;
        }

        // A draft invoice has not yet been issued. Without any payments it
        // should remain in draft so that simply adding line items does not
        // silently flip it to overdue/sent. Once a payment exists the invoice
        // is effectively open, so we allow status to advance normally.
        if ($this->status === 'draft' && $paidMoney->isZero()) {
            $this->forceFill([
                'paid_total'  => $paidMoney->toString(),
                'balance_due' => $balanceMoney->toString(),
            ])->saveQuietly();

            return;
        }

        $overdueGraceDays = max(0, (int) config('erp.billing.overdue_grace_days', 0));
        $overdueAt = $this->due_date !== null
            ? now()->parse($this->due_date)->endOfDay()->addDays($overdueGraceDays)
            : null;

        $isOverdue = $overdueAt !== null && now()->greaterThan($overdueAt);

        // Delegate status computation to the state machine, which knows the
        // allowed transitions and will prevent illegal status jumps.
        $newStatus = InvoiceStateMachine::computeNext(
            currentStatus: (string) $this->status,
            total:         $totalMoney->toFloat(),
            paidTotal:     $paidTotal,
            isOverdue:     $isOverdue,
        );

        $this->forceFill([
            'paid_total'  => $paidMoney->toString(),
            'balance_due' => $balanceMoney->toString(),
            'status'      => $newStatus,
        ])->saveQuietly();
    }
}
