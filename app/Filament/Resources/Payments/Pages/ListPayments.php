<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Widgets\FinanceTerminalOverview;
use App\Filament\Resources\Payments\Widgets\PaymentTrackingStats;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Terminal de paiements';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceTerminalOverview::class,
            PaymentTrackingStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('batchProcess')
                ->label('Traiter le lot')
                ->action(fn() => Notification::make()->title('Les éléments prêts ont été envoyés pour révision groupée.')->success()->send()),
            Action::make('exportLedger')
                ->label('Exporter le registre')
                ->action(fn() => Notification::make()->title('L’export du registre a démarré avec succès.')->success()->send()),
            Action::make('smartLink')
                ->label('Lancer le rapprochement')
                ->action(function (): void {
                    $matched = 0;

                    Payment::query()
                        ->whereNull('invoice_id')
                        ->get()
                        ->each(function (Payment $payment) use (&$matched): void {
                            if ($payment->reconcileAgainstOpenInvoice()) {
                                $matched++;
                            }
                        });

                    $notification = Notification::make()
                        ->title($matched > 0 ? $matched . ' paiement(s) rapproché(s).' : 'Aucun paiement non affecté n’était éligible au rapprochement automatique.');

                    if ($matched > 0) {
                        $notification->success();
                    } else {
                        $notification->warning();
                    }

                    $notification->send();
                }),
            CreateAction::make()->label('Enregistrer un paiement'),
        ];
    }
}
