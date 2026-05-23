<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyPaymentAction
{
    public function execute(Payment $payment): bool
    {
        if (DB::transactionLevel() > 0) {
            return $this->apply($payment);
        }

        return DB::transaction(fn (): bool => $this->apply($payment));
    }

    protected function apply(Payment $payment): bool
    {
        $lockedInvoice = null;

        if ($payment->invoice_id !== null) {
            $lockedInvoice = Invoice::withoutCompanyScope()
                ->whereKey($payment->invoice_id)
                ->lockForUpdate()
                ->first();

            if ($lockedInvoice === null) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'The selected invoice does not exist.',
                ]);
            }
        }

        $saved = $payment->save();

        if ($saved && $lockedInvoice !== null) {
            $lockedInvoice->refreshFinancials();
        }

        return $saved;
    }
}
