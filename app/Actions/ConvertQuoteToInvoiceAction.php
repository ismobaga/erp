<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\Quote;
use App\Services\AuditTrailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertQuoteToInvoiceAction
{
    public function __construct(
        private readonly AuditTrailService $audit,
    ) {}

    /**
     * Convert a quote to an invoice inside a serialisable transaction.
     *
     * The quote row is locked for update so that two concurrent requests
     * cannot both pass the existence check and create duplicate invoices.
     *
     * @throws ValidationException If the quote cannot be found.
     */
    public function execute(Quote $quote, ?int $createdBy = null): Invoice
    {
        return DB::transaction(function () use ($quote, $createdBy): Invoice {
            // Lock the quote row so that two concurrent requests cannot both
            // pass the existence check and create duplicate invoices.
            // Eager-load items here so the subsequent loop does not trigger
            // an additional query per conversion.
            $fresh = Quote::withoutCompanyScope()
                ->with('items')
                ->whereKey($quote->getKey())
                ->lockForUpdate()
                ->first();

            if (! $fresh) {
                throw ValidationException::withMessages([
                    'quote' => 'Quote not found.',
                ]);
            }

            if ($fresh->invoice()->exists()) {
                return $fresh->invoice;
            }

            $invoice = Invoice::create([
                'client_id' => $fresh->client_id,
                'quote_id' => $fresh->getKey(),
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays((int) config('erp.billing.invoice_default_due_days', 30))->toDateString(),
                'status' => 'draft',
                'discount_total' => $fresh->discount_total,
                'tax_total' => $fresh->tax_total,
                'notes' => $fresh->notes,
                'created_by' => $createdBy ?? auth()->id(),
                'updated_by' => $createdBy ?? auth()->id(),
            ]);

            foreach ($fresh->items as $item) {
                $invoice->items()->create([
                    'service_id' => $item->service_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]);
            }

            $fresh->forceFill(['status' => 'accepted'])->saveQuietly();

            $this->audit->log('quote_converted_to_invoice', $invoice, [
                'quote_id' => $fresh->getKey(),
                'quote_number' => $fresh->quote_number,
                'invoice_number' => $invoice->invoice_number,
                'converted_by' => $createdBy ?? auth()->id(),
            ]);

            return $invoice;
        });
    }
}
