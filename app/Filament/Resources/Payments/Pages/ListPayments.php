<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Widgets\FinanceTerminalOverview;
use App\Filament\Resources\Payments\Widgets\PaymentTrackingStats;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditTrailService;
use App\Services\ReportExportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Paiements';
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
            CreateAction::make()->label('Enregistrer un paiement'),
            ActionGroup::make([
                Action::make('batchProcess')
                    ->label('Synchroniser les factures')
                    ->visible(fn (): bool => auth()->user()?->can('payments.update') ?? false)
                    ->action(function (): void {
                        // Sync invoice financials for every payment that has a linked invoice
                        // but whose invoice status may be stale (e.g., after manual DB edits or imports).
                        $synced = 0;

                        if (Schema::hasTable('payments') && Schema::hasTable('invoices')) {
                            $invoiceIds = Payment::query()
                                ->whereNotNull('invoice_id')
                                ->pluck('invoice_id')
                                ->unique();

                            Invoice::query()
                                ->whereKey($invoiceIds)
                                ->whereNotIn('status', ['paid', 'cancelled'])
                                ->chunkById(100, function (Collection $chunk) use (&$synced): void {
                                    foreach ($chunk as $invoice) {
                                        $invoice->refreshFinancials();
                                        $synced++;
                                    }
                                });
                        }

                        app(AuditTrailService::class)->log('payments_batch_processed', null, [
                            'invoices_synced' => $synced,
                            'processed_by' => auth()->id(),
                        ]);

                        $notification = Notification::make()
                            ->title($synced > 0
                                ? $synced.' facture(s) synchronisée(s) avec succès.'
                                : 'Aucune facture en attente de synchronisation.');

                        ($synced > 0 ? $notification->success() : $notification->info())->send();
                    }),
                Action::make('smartLink')
                    ->label('Rapprochement automatique')
                    ->visible(fn (): bool => auth()->user()?->can('payments.update') ?? false)
                    ->action(function (): void {
                        $matched = 0;

                        Payment::query()
                            ->whereNull('invoice_id')
                            ->chunkById(200, function (Collection $chunk) use (&$matched): void {
                                foreach ($chunk as $payment) {
                                    if ($payment->reconcileAgainstOpenInvoice()) {
                                        $matched++;
                                    }
                                }
                            });

                        $notification = Notification::make()
                            ->title($matched > 0 ? $matched.' paiement(s) rapproché(s).' : 'Aucun paiement non affecté n’était éligible au rapprochement automatique.');

                        if ($matched > 0) {
                            $notification->success();
                        } else {
                            $notification->warning();
                        }

                        $notification->send();
                    }),
                Action::make('exportLedger')
                    ->label('Exporter le registre')
                    ->visible(fn (): bool => auth()->user()?->canAny(['payments.view', 'reports.view']) ?? false)
                    ->action(function (): void {
                        $userId = auth()->id();

                        $report = app(ReportExportService::class)->generate(
                            now()->startOfYear()->startOfDay(),
                            now()->endOfDay(),
                            [
                                'revenue' => false,
                                'expenses' => false,
                                'payments' => true,
                                'taxes' => false,
                                'audit' => false,
                            ],
                            'csv',
                            false,
                            $userId,
                        );

                        app(AuditTrailService::class)->log('payment_ledger_exported', null, [
                            'path' => $report['path'],
                            'generated_at' => $report['generatedAt'],
                        ]);

                        $this->redirect($report['downloadUrl'], navigate: false);
                    }),
            ])->label('Actions avancées')->icon('heroicon-o-cog-6-tooth'),
        ];
    }
}
