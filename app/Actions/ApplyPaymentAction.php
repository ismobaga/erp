<?php

namespace App\Actions;

use App\Events\PaymentRecorded;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyPaymentAction
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

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
            $this->invoiceService->refreshFinancials($lockedInvoice);
        }

        if ($saved) {
            PaymentRecorded::dispatch($payment);
        }

        return $saved;
    }
}
