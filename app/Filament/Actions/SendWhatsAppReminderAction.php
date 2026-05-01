<?php

namespace App\Filament\Actions;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\AuditTrailService;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Filament header action that sends WhatsApp payment-reminder messages for
 * all invoices due tomorrow that still carry an outstanding balance.
 *
 * Requires:
 *   - Client::$phone to be set in E.164 / WhatsApp JID format
 *     (e.g. "6289685028129@s.whatsapp.net" or a bare number like "628968502812")
 *   - WHATSAPP_API_URL, WHATSAPP_DEVICE_ID (and optionally credentials) in .env
 */
class SendWhatsAppReminderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sendWhatsAppReminders';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('WhatsApp rappels')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('success')
            ->visible(fn(): bool => auth()->user()?->can('invoices.update') ?? false)
            ->action(function (): void {
                $sent = $this->dispatchReminders();

                Notification::make()
                    ->title($sent > 0
                        ? 'WhatsApp : rappels envoyés pour ' . $sent . ' facture(s) à échéance demain.'
                        : 'Aucune facture à échéance demain nécessitant un rappel WhatsApp.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Query invoices due tomorrow, send a WhatsApp reminder for each one
     * whose client has a phone number, and write an audit trail entry.
     *
     * Returns the number of reminders successfully sent.
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

        $whatsapp = app(WhatsAppService::class);
        $company  = CompanySetting::query()->first();
        $companyName = $company?->company_name ?? config('app.name', 'ERP');
        $sent = 0;

        foreach ($invoices as $invoice) {
            $client = $invoice->client;

            if (! $client || blank($client->phone)) {
                continue;
            }

            $phone   = $this->normalizePhone($client->phone);
            $amount  = 'FCFA ' . number_format((float) $invoice->balance_due, 0, '.', ' ');
            $dueDate = $invoice->due_date?->format('d/m/Y') ?? '—';

            $message = implode("\n", [
                "Bonjour {$client->contact_name},",
                '',
                "Nous vous rappelons que la facture *{$invoice->invoice_number}* d'un montant de *{$amount}* arrive à échéance demain ({$dueDate}).",
                '',
                'Merci de procéder au règlement dans les meilleurs délais.',
                '',
                "Cordialement,\n{$companyName}",
            ]);

            try {
                $whatsapp->sendMessage($phone, $message);
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

            app(AuditTrailService::class)->log('whatsapp_reminder_sent', $invoice, [
                'reference'   => $invoice->invoice_number,
                'client_phone'=> $client->phone,
                'balance_due' => (float) $invoice->balance_due,
                'due_date'    => optional($invoice->due_date)->format('Y-m-d'),
                'sent_by'     => auth()->id(),
            ]);

            $sent++;
        }

        return $sent;
    }

    /**
     * Ensure the phone number is formatted as a WhatsApp JID.
     * If the value already contains "@", it is returned as-is.
     * Otherwise append "@s.whatsapp.net".
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9@.]/', '', $phone) ?? $phone;

        if (str_contains($phone, '@')) {
            return $phone;
        }

        return $phone . '@s.whatsapp.net';
    }
}
