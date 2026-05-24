<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Services\Whatsapp\WhatsappSendService;
use Filament\Notifications\Notification;

class SendInvoiceWhatsappAction
{
    public function __construct(
        private readonly WhatsappSendService $whatsappSendService,
    ) {}

    public function execute(Invoice $invoice): void
    {
        $log = $this->whatsappSendService->sendInvoice($invoice);

        if ($log->status === 'sent') {
            Notification::make()->title('Facture envoyée via WhatsApp')->success()->send();

            return;
        }

        Notification::make()->title('Échec de l\'envoi WhatsApp')->body($log->error_message)->danger()->send();
    }
}
