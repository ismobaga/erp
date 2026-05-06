<?php

namespace App\Services;

use App\Models\DunningLog;
use App\Models\Invoice;
use App\Services\AuditTrailService;
use App\Services\Whatsapp\WhatsappSendService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DunningService
{
    /**
     * Dunning stage thresholds in days overdue.
     *
     * Stages escalate as the invoice ages:
     *   Stage 1 → 1–14 days overdue (gentle reminder)
     *   Stage 2 → 15–30 days overdue (second notice)
     *   Stage 3 → 31–60 days overdue (firm notice)
     *   final   → 61+ days overdue (formal demand)
     */
    private const STAGE_THRESHOLDS = [
        'final' => 61,
        '3'     => 31,
        '2'     => 15,
        '1'     => 1,
    ];

    private const MIN_DAYS_BETWEEN_SAME_STAGE = 7;

    /**
     * Determine the current dunning stage for an invoice based on how many
     * days overdue it is.
     */
    public function resolveStage(Invoice $invoice): ?string
    {
        if (!$invoice->due_date) {
            return null;
        }

        $daysOverdue = (int) Carbon::today()->diffInDays($invoice->due_date, false) * -1;

        if ($daysOverdue < 1) {
            return null;
        }

        foreach (self::STAGE_THRESHOLDS as $stage => $threshold) {
            if ($daysOverdue >= $threshold) {
                return (string) $stage;
            }
        }

        return null;
    }

    /**
     * Returns the overdue invoices that are eligible for an automatic dunning
     * step (i.e., have not yet been contacted at the current stage, or the
     * last contact for this stage was more than MIN_DAYS_BETWEEN_SAME_STAGE
     * days ago).
     */
    public function eligibleInvoices(): Collection
    {
        return Invoice::query()
            ->where('status', 'overdue')
            ->whereNotNull('due_date')
            ->with(['client', 'dunningLogs' => fn($q) => $q->latest()])
            ->get()
            ->filter(fn(Invoice $invoice): bool => $this->isEligible($invoice));
    }

    public function isEligible(Invoice $invoice): bool
    {
        $stage = $this->resolveStage($invoice);

        if ($stage === null) {
            return false;
        }

        // When the dunningLogs relation has already been eager-loaded (e.g. by
        // eligibleInvoices()), filter the in-memory collection to avoid an N+1
        // query per invoice.  Fall back to a targeted DB query otherwise.
        if ($invoice->relationLoaded('dunningLogs')) {
            $lastForStage = $invoice->dunningLogs
                ->where('stage', $stage)
                ->sortByDesc('sent_at')
                ->first();
        } else {
            $lastForStage = DunningLog::query()
                ->forInvoice($invoice->id)
                ->where('stage', $stage)
                ->latest('sent_at')
                ->first();
        }

        if (!$lastForStage) {
            return true;
        }

        $daysSinceLast = (int) Carbon::today()->diffInDays(
            Carbon::parse($lastForStage->sent_at),
            false,
        ) * -1;

        return $daysSinceLast >= self::MIN_DAYS_BETWEEN_SAME_STAGE;
    }

    /**
     * Log a dunning action. The actual notification delivery (email, SMS, etc.)
     * is the responsibility of the caller — this service only persists the record
     * and audits the action.
     */
    public function logReminder(
        Invoice $invoice,
        string $channel = 'email',
        ?int $sentBy = null,
        ?string $notes = null,
    ): DunningLog {
        $stage = $this->resolveStage($invoice) ?? '1';

        $log = DunningLog::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'stage' => $stage,
            'channel' => $channel,
            'sent_at' => now(),
            'notes' => $notes,
            'sent_by' => $sentBy,
        ]);

        app(AuditTrailService::class)->log('dunning_reminder_sent', $invoice, [
            'invoice_number' => $invoice->invoice_number,
            'stage' => $stage,
            'channel' => $channel,
            'days_overdue' => $this->daysOverdue($invoice),
        ], $sentBy);

        return $log;
    }

    /**
     * Process all eligible invoices and dispatch automated reminders.
     * When WhatsApp is enabled for the current company and the client has a
     * phone number, a WhatsApp dunning message is also sent.
     * Returns the count of reminders dispatched.
     */
    public function runAutomatedDunning(?int $systemUserId = null): int
    {
        $invoices = $this->eligibleInvoices();
        $count = 0;

        foreach ($invoices as $invoice) {
            $this->logReminder($invoice, 'email', $systemUserId);
            $this->tryWhatsappDunning($invoice, $systemUserId);
            $count++;
        }

        return $count;
    }

    /**
     * Attempt to send a WhatsApp dunning notification if WhatsApp is enabled
     * for the current company and the invoice's client has a phone number.
     */
    private function tryWhatsappDunning(Invoice $invoice, ?int $sentBy = null): void
    {
        $company = currentCompany();

        if (!$company || !$company->whatsapp_enabled || blank($company->whatsapp_device_id)) {
            return;
        }

        $client = $invoice->client;

        if ($client === null || blank($client->phone)) {
            return;
        }

        try {
            app(WhatsappSendService::class)->sendPaymentReminder($invoice);
            $this->logReminder($invoice, 'whatsapp', $sentBy);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp dunning notification failed.', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function daysOverdue(Invoice $invoice): int
    {
        if (!$invoice->due_date) {
            return 0;
        }

        $days = (int) Carbon::today()->diffInDays($invoice->due_date, false) * -1;

        return max(0, $days);
    }
}
