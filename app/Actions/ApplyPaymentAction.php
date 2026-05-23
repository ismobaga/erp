<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

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
        if ($payment->invoice_id !== null) {
            Invoice::withoutCompanyScope()
                ->whereKey($payment->invoice_id)
                ->lockForUpdate()
                ->first();
        }

        $saved = $payment->save();

        $payment->invoice?->refreshFinancials();

        return $saved;
    }
}
