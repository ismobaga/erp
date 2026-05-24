<?php

namespace App\Actions;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Services\AuditTrailService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminderAction
{
    public function execute(Invoice $invoice): void
    {
        $client = $invoice->client;

        if ($client === null || blank($client->email)) {
            Notification::make()
                ->title('Aucun e-mail client')
                ->body('Ce client n\'a pas d\'adresse e-mail enregistrée. Vérifiez sa fiche.')
                ->warning()
                ->send();

            return;
        }

        Mail::to($client->email)->queue(new InvoiceReminderMail($invoice));

        app(AuditTrailService::class)->log('invoice_reminder_sent', $invoice, [
            'reference' => $invoice->invoice_number,
            'client_email' => $client->email,
            'balance_due' => (float) $invoice->balance_due,
            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
            'sent_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Rappel de paiement envoyé')
            ->body('Un rappel a été envoyé à ' . $client->email . ' pour la facture ' . $invoice->invoice_number . '.')
            ->success()
            ->send();
    }
}
