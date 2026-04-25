<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\LedgerPostingService;
use Illuminate\Support\Facades\Log;
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
        } catch (Throwable $e) {
            // Best-effort – do not block payment recording
            Log::error('PaymentObserver: failed to post journal entry', [
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
