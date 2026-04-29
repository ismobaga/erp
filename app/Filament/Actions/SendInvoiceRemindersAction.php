<?php

namespace App\Filament\Actions;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Services\AuditTrailService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

/**
 * Filament header action that queues payment-reminder e-mails for all
 * invoices due tomorrow that still carry an outstanding balance.
 *
 * Extracted from the inline closure in ListInvoices::getHeaderActions()
 * so that the logic can be tested independently and reused if needed.
 */
class SendInvoiceRemindersAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sendReminders';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Envoyer les rappels')
            ->visible(fn (): bool => auth()->user()?->can('invoices.update') ?? false)
            ->action(function (): void {
                $sent = $this->dispatchReminders();

                Notification::make()
                    ->title($sent > 0
                        ? 'Rappels envoyés pour ' . $sent . ' facture(s) à échéance demain.'
                        : 'Aucune facture à échéance demain nécessitant un rappel.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Query invoices due tomorrow, queue a reminder e-mail for each one
     * that has a client e-mail address, and write an audit trail entry.
     *
     * Returns the number of reminders successfully queued.
     */
    protected function dispatchReminders(): int
    {
        $targetDate = now()->addDay()->toDateString();

        $invoices = Invoice::query()
            ->with('client')
            ->where('due_date', $targetDate)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->where('balance_due', '>', 0)
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $client = $invoice->client;

            if (!$client || blank($client->email)) {
                continue;
            }

            Mail::to($client->email)->queue(new InvoiceReminderMail($invoice));

            app(AuditTrailService::class)->log('invoice_reminder_sent', $invoice, [
                'reference'    => $invoice->invoice_number,
                'client_email' => $client->email,
                'balance_due'  => (float) $invoice->balance_due,
                'due_date'     => optional($invoice->due_date)->format('Y-m-d'),
                'sent_by'      => auth()->id(),
            ]);

            $sent++;
        }

        return $sent;
    }
}
