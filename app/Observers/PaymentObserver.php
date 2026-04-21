<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\LedgerPostingService;
use Throwable;

class PaymentObserver
{
    public function __construct(
        private readonly LedgerPostingService $posting,
    ) {}

    /**
     * Auto-post a journal entry when a payment is created.
     */
    public function created(Payment $payment): void
    {
        try {
            $this->posting->postPayment($payment, $payment->recorded_by);
        } catch (Throwable) {
            // Best-effort – do not block payment recording
        }
    }
}
