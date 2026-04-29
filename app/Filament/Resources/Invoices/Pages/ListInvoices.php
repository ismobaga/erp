<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Widgets\InvoiceLedgerStats;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Registre des factures';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceLedgerStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendReminders')
                ->label('Envoyer les rappels')
                ->visible(fn(): bool => auth()->user()?->can('invoices.update') ?? false)
                ->action(function (): void {
                    $targetDate = now()->addDay()->toDateString();
                    // Query already returns Invoice instances; no re-fetch needed.
                    $invoices = Invoice::query()
                        ->with('client')
                        ->where('due_date', $targetDate)
                        ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
                        ->where('balance_due', '>', 0)
                        ->get();

                    $sent = 0;
                    foreach ($invoices as $invoice) {
                        if (!$invoice)
                            continue;
                        $client = $invoice->client;
                        if (!$client || blank($client->email)) {
                            continue;
                        }
                        \Mail::to($client->email)->queue(new \App\Mail\InvoiceReminderMail($invoice));
                        app(\App\Services\AuditTrailService::class)->log('invoice_reminder_sent', $invoice, [
                            'reference' => $invoice->invoice_number,
                            'client_email' => $client->email,
                            'balance_due' => (float) $invoice->balance_due,
                            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                            'sent_by' => auth()->id(),
                        ]);
                        $sent++;
                    }

                    Notification::make()
                        ->title($sent > 0
                            ? 'Rappels envoyés pour ' . $sent . ' facture(s) à échéance demain.'
                            : 'Aucune facture à échéance demain nécessitant un rappel.')
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Nouvelle facture'),
        ];
    }
}
