<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RecurringInvoice;
use App\Services\AuditTrailService;
use App\Services\InvoiceNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring
                            {--dry-run : Preview what would be generated without saving}
                            {--date= : Target date (Y-m-d), defaults to today}';

    protected $description = 'Generate invoices from active recurring templates that are due today or earlier.';

    public function handle(InvoiceNumberService $numberService, AuditTrailService $audit): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))
            : today();

        $isDry = (bool) $this->option('dry-run');

        if ($isDry) {
            $this->info('[DRY RUN] No invoices will be saved.');
        }

        $templates = RecurringInvoice::query()
            ->with('client')
            ->where('is_active', true)
            ->whereDate('next_due_date', '<=', $targetDate)
            ->get();

        if ($templates->isEmpty()) {
            $this->info('No recurring invoices are due on '.$targetDate->toDateString().'.');

            return self::SUCCESS;
        }

        $generated = 0;

        foreach ($templates as $template) {
            /** @var RecurringInvoice $template */
            if (! $isDry) {
                DB::transaction(function () use ($template, $audit): void {
                    $issueDate = $template->next_due_date;
                    $dueDate = $issueDate->copy()->addDays((int) $template->net_days);

                    $invoice = Invoice::create([
                        'client_id' => $template->client_id,
                        'issue_date' => $issueDate,
                        'due_date' => $dueDate,
                        'status' => 'draft',
                        'notes' => $template->notes,
                    ]);

                    if (! empty($template->items)) {
                        foreach ((array) $template->items as $item) {
                            InvoiceItem::create([
                                'invoice_id' => $invoice->id,
                                'description' => $item['description'] ?? ($template->description ?? 'Prestation récurrente'),
                                'quantity' => $item['quantity'] ?? 1,
                                'unit_price' => $item['unit_price'] ?? $template->amount,
                                'line_total' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? $template->amount),
                            ]);
                        }
                    } else {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'description' => $template->description ?? 'Prestation récurrente',
                            'quantity' => 1,
                            'unit_price' => $template->amount,
                            'line_total' => $template->amount,
                        ]);
                    }

                    $invoice->recalculateTotals();

                    $audit->log('recurring_invoice_generated', $invoice, [
                        'recurring_invoice_id' => $template->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_id' => $template->client_id,
                    ]);

                    $template->advanceNextDueDate();
                });
            } else {
                $this->line(sprintf(
                    '  [DRY] Would generate invoice for client #%d (%s) — amount: FCFA %s — next: %s',
                    $template->client_id,
                    $template->client?->company_name ?: $template->client?->contact_name ?: 'N/A',
                    number_format((float) $template->amount, 0, '.', ' '),
                    $template->next_due_date->toDateString(),
                ));
            }

            $generated++;
        }

        $this->info(
            $isDry
                ? $generated.' recurring invoice(s) would be generated.'
                : $generated.' recurring invoice(s) generated successfully.'
        );

        return self::SUCCESS;
    }
}
