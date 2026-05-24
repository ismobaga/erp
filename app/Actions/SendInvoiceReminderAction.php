<?php

namespace App\Actions;

use App\Actions\Concerns\SanitizesNotificationText;
use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Services\AuditTrailService;
use DateTimeInterface;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminderAction
{
    use SanitizesNotificationText;

    public function __construct(
        private readonly AuditTrailService $auditTrailService,
    ) {}

    public function execute(Invoice $invoice): void
    {
        $client = $invoice->client;

        if ($client === null || blank($client->email)) {
            Notification::make()
                ->title('Aucun e-mail client')
                ->body("Ce client n’a pas d’adresse e-mail enregistrée. Vérifiez sa fiche.")
                ->warning()
                ->send();

            return;
        }

        Mail::to($client->email)->queue(new InvoiceReminderMail($invoice));

        $this->auditTrailService->log('invoice_reminder_sent', $invoice, [
            'reference' => $invoice->invoice_number,
            'client_email' => $client->email,
            'balance_due' => (float) $invoice->balance_due,
            'due_date' => $this->formatDueDate($invoice->due_date),
            'sent_by' => auth()->id(),
        ]);

        $clientEmail = $this->sanitizeNotificationText($client->email);
        $invoiceNumber = $this->sanitizeNotificationText($invoice->invoice_number);

        Notification::make()
            ->title('Rappel de paiement envoyé')
            ->body(sprintf('Un rappel a été envoyé à %s pour la facture %s.', $clientEmail, $invoiceNumber))
            ->success()
            ->send();
    }

    private function formatDueDate(mixed $dueDate): ?string
    {
        if ($dueDate instanceof DateTimeInterface) {
            return $dueDate->format('Y-m-d');
        }

        if (!filled($dueDate)) {
            return null;
        }

        return Carbon::parse((string) $dueDate)->format('Y-m-d');
    }
}
