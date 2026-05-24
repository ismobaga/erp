<?php

namespace App\Actions;

use App\Actions\Concerns\SanitizesNotificationText;
use App\Models\Invoice;
use App\Services\Whatsapp\WhatsappSendService;
use Filament\Notifications\Notification;

class SendInvoiceWhatsappReminderAction
{
    use SanitizesNotificationText;

    public function __construct(
        private readonly WhatsappSendService $whatsappSendService,
    ) {}

    public function execute(Invoice $invoice): void
    {
        $log = $this->whatsappSendService->sendPaymentReminder($invoice);

        if ($log->status === 'sent') {
            Notification::make()->title('Rappel envoyé via WhatsApp')->success()->send();

            return;
        }

        Notification::make()
            ->title('Échec de l\'envoi WhatsApp')
            ->body($this->sanitizeNotificationText(
                $log->error_message,
                'Une erreur est survenue pendant l’envoi du message.',
            ))
            ->danger()
            ->send();
    }
}
