<?php

namespace App\Services;

use App\Events\InvoiceIssued;
use App\Models\Invoice;
use App\States\InvoiceStateMachine;
use App\ValueObjects\Money;

class InvoiceService
{
    public function __construct(
        private readonly TaxProfileResolver $taxProfileResolver,
    ) {}

    /**
     * Recompute line-item totals (subtotal, tax, total) from the invoice's
     * current items and persist them, then refresh the financial state.
     */
    public function recalculateTotals(Invoice $invoice): void
    {
        // Use BCMath (via Money) to avoid floating-point accumulation errors.
        $subtotal = Money::of((string) $invoice->items()->sum('line_total'));

        $taxComputation = $this->taxProfileResolver->calculateForClient(
            $subtotal->toFloat(),
            $invoice->client,
        );

        $taxTotal = $taxComputation['matched']
            ? Money::of((string) $taxComputation['tax_total'])
            : Money::of((string) $invoice->tax_total);

        // For inclusive tax profiles the resolver already includes tax in the
        // returned total; for exclusive we add it on top of the subtotal.
        $isInclusive = $taxComputation['matched']
            && ($taxComputation['profile']['mode'] ?? 'exclusive') === 'inclusive';

        $baseTotal = $isInclusive
            ? Money::of((string) $taxComputation['total'])
            : $subtotal->add($taxTotal);

        $discount = Money::of((string) $invoice->discount_total);
        $total = Money::zero()->max($baseTotal->subtract($discount));

        $invoice->forceFill([
            'subtotal' => $subtotal->toString(),
            'tax_total' => $taxTotal->toString(),
            'total' => $total->toString(),
        ])->saveQuietly();

        $this->refreshFinancials($invoice);
    }

    /**
     * Recompute credit note reductions and update the invoice total, then
     * refresh the financial state.
     */
    public function refreshCreditBalance(Invoice $invoice): void
    {
        $creditedTotal = Money::of(
            (string) $invoice->creditNotes()
                ->whereIn('status', ['issued', 'approved'])
                ->sum('amount'),
        );

        // Re-derive the raw subtotal from the stored totals.
        $storedTotal = Money::of((string) $invoice->total);
        $storedDiscount = Money::of((string) $invoice->discount_total);
        $storedTax = Money::of((string) $invoice->tax_total);
        $storedSubtotal = Money::of((string) $invoice->subtotal);

        $currentSubtotal = $storedSubtotal->isZero()
            ? Money::zero()->max($storedTotal->add($storedDiscount)->subtract($storedTax))
            : $storedSubtotal;

        $taxComputation = $this->taxProfileResolver->calculateForClient(
            $currentSubtotal->toFloat(),
            $invoice->client,
        );

        $taxTotal = $taxComputation['matched']
            ? Money::of((string) $taxComputation['tax_total'])
            : $storedTax;

        $isInclusive = $taxComputation['matched']
            && ($taxComputation['profile']['mode'] ?? 'exclusive') === 'inclusive';

        $baseTotal = $isInclusive
            ? Money::of((string) $taxComputation['total'])
            : $currentSubtotal->add($taxTotal);

        // Apply commercial discount first, then credit notes.
        $afterDiscount = Money::zero()->max($baseTotal->subtract($storedDiscount));
        $newTotal = Money::zero()->max($afterDiscount->subtract($creditedTotal));

        // credit_total tracks only credit note reductions; discount_total is preserved.
        $invoice->forceFill([
            'credit_total' => $creditedTotal->toString(),
            'tax_total' => $taxTotal->toString(),
            'total' => $newTotal->toString(),
        ])->saveQuietly();

        $this->refreshFinancials($invoice);
    }

    /**
     * Recompute paid_total, balance_due and status from the invoice's current
     * payments and persist them quietly (without re-triggering model events).
     */
    public function refreshFinancials(Invoice $invoice): void
    {
        // Use BCMath Money to avoid float precision drift when summing payments.
        $paidMoney = Money::of((string) $invoice->payments()->sum('amount'));
        $totalMoney = Money::of((string) $invoice->total);
        $balanceMoney = Money::zero()->max($totalMoney->subtract($paidMoney));

        $paidTotal = $paidMoney->toFloat();
        $balanceDue = $balanceMoney->toFloat();

        // Statuses that must never be silently overridden by financial recomputation.
        if ($invoice->status === 'cancelled') {
            $invoice->forceFill([
                'paid_total' => $paidMoney->toString(),
                'balance_due' => $balanceMoney->toString(),
            ])->saveQuietly();

            return;
        }

        // A draft invoice has not yet been issued. Without any payments it
        // should remain in draft so that simply adding line items does not
        // silently flip it to overdue/sent. Once a payment exists the invoice
        // is effectively open, so we allow status to advance normally.
        if ($invoice->status === 'draft' && $paidMoney->isZero()) {
            $invoice->forceFill([
                'paid_total' => $paidMoney->toString(),
                'balance_due' => $balanceMoney->toString(),
            ])->saveQuietly();

            return;
        }

        $overdueGraceDays = max(0, (int) config('erp.billing.overdue_grace_days', 0));
        $overdueAt = $invoice->due_date !== null
            ? now()->parse($invoice->due_date)->endOfDay()->addDays($overdueGraceDays)
            : null;

        $isOverdue = $overdueAt !== null && now()->greaterThan($overdueAt);

        // Delegate status computation to the state machine, which knows the
        // allowed transitions and will prevent illegal status jumps.
        $newStatus = InvoiceStateMachine::computeNext(
            currentStatus: (string) $invoice->status,
            total: $totalMoney->toFloat(),
            paidTotal: $paidTotal,
            isOverdue: $isOverdue,
        );

        $invoice->forceFill([
            'paid_total' => $paidMoney->toString(),
            'balance_due' => $balanceMoney->toString(),
            'status' => $newStatus,
        ])->saveQuietly();
    }

    /**
     * Transition an invoice to the 'sent' (issued) status and dispatch the
     * InvoiceIssued event so that side effects (ledger posting, audit trail)
     * are handled by their respective listeners.
     */
    public function issue(Invoice $invoice, ?int $userId = null): void
    {
        if (! InvoiceStateMachine::canTransition((string) $invoice->status, 'sent')) {
            return;
        }

        $invoice->forceFill(['status' => 'sent'])->save();

        InvoiceIssued::dispatch($invoice, $userId);
    }
}
