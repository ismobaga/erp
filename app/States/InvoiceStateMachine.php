<?php

namespace App\States;

/**
 * Defines all valid Invoice statuses and the allowed state transitions.
 *
 * Allowed transitions:
 *   draft          → sent
 *   sent           → partially_paid | paid | overdue
 *   partially_paid → paid | overdue
 *   overdue        → paid
 *   *              → cancelled  (any non-cancelled status may be cancelled)
 *
 * The state machine is intentionally kept as a plain PHP class so that it
 * can be tested without Eloquent, and can be composed into the Invoice model
 * without an external dependency.
 */
final class InvoiceStateMachine
{
    /** All valid status strings. */
    public const STATUSES = [
        'draft',
        'sent',
        'partially_paid',
        'paid',
        'overdue',
        'cancelled',
    ];

    /**
     * Map of status → list of statuses it may transition to.
     *
     * 'draft' is allowed to transition directly to 'partially_paid', 'paid',
     * and 'overdue' (in addition to the canonical 'sent') because payments
     * can be recorded against a draft invoice before it is formally sent.
     * This preserves the implicit behaviour of refreshFinancials() while
     * making the transitions explicit and auditable.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'draft'          => ['sent', 'partially_paid', 'paid', 'overdue', 'cancelled'],
        'sent'           => ['partially_paid', 'paid', 'overdue', 'cancelled'],
        'partially_paid' => ['paid', 'overdue', 'cancelled'],
        'overdue'        => ['paid', 'cancelled'],
        'paid'           => ['cancelled'],
        'cancelled'      => [],
    ];

    /**
     * Return true if transitioning from $from to $to is a valid step in the
     * state machine.  No exception is thrown; the caller decides what to do.
     */
    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            // Staying in the same status is always allowed (idempotent).
            return true;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Return the next status that refreshFinancials() should assign based
     * on the current financial state, respecting only allowed transitions.
     *
     * When a transition is not permitted (e.g. going from 'draft' directly
     * to 'overdue' without first being 'sent'), the method keeps the current
     * status instead of making an illegal jump.
     *
     * @param  string       $currentStatus  The invoice's present status.
     * @param  float        $total          The invoice total (>0).
     * @param  float        $paidTotal      Aggregate of all payments.
     * @param  bool         $isOverdue      Whether the due date + grace has passed.
     */
    public static function computeNext(
        string $currentStatus,
        float $total,
        float $paidTotal,
        bool $isOverdue,
    ): string {
        // Cancelled is terminal – nothing changes it via financial recomputation.
        if ($currentStatus === 'cancelled') {
            return 'cancelled';
        }

        $balanceDue = $total - $paidTotal;
        $isSettled  = $balanceDue <= 0.00001;

        // Determine the "ideal" next status purely from the financials.
        if ($isSettled && $total > 0) {
            $desired = 'paid';
        } elseif ($paidTotal > 0.0 && $balanceDue > 0.0) {
            $desired = 'partially_paid';
        } elseif ($balanceDue > 0.0 && $isOverdue) {
            $desired = 'overdue';
        } else {
            $desired = 'sent';
        }

        // Apply transition guard: if the move is allowed, use the desired
        // status; otherwise keep the current one to avoid illegal jumps.
        return self::canTransition($currentStatus, $desired) ? $desired : $currentStatus;
    }

    /**
     * Return the list of statuses that $from may transition to.
     *
     * @return list<string>
     */
    public static function allowedFrom(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }
}
