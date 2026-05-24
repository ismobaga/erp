<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\AuditTrailService;
use App\Services\LedgerPostingService;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoiceObserver
{
    public function __construct(
        private readonly LedgerPostingService $posting,
        private readonly AuditTrailService $audit,
    ) {}

    /**
     * Log the invoice number assignment when an invoice is first created.
     */
    public function created(Invoice $invoice): void
    {
        $this->audit->log('invoice_number_assigned', $invoice, [
            'invoice_number' => $invoice->invoice_number,
            'issue_date' => optional($invoice->issue_date)->toDateString(),
        ], $invoice->created_by);
    }

    /**
     * Auto-post a journal entry when an invoice transitions to 'sent'.
     * Silently skip if ledger accounts are not yet configured.
     */
    public function updated(Invoice $invoice): void
    {
        if (!$invoice->wasChanged('status')) {
            return;
        }

        if ($invoice->status !== 'sent') {
            return;
        }

        try {
            $this->posting->postInvoice($invoice, $invoice->updated_by ?? $invoice->created_by);
        } catch (Throwable $e) {
            // Posting is best-effort; do not block invoice operations if ledger
            // accounts are missing or the period is not configured yet.
            Log::error('InvoiceObserver: failed to post journal entry', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
