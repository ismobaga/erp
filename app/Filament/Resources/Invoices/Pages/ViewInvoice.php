<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Mail\InvoiceSentMail;
use App\Models\DunningLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditTrailService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Invoice $record */
        $record = $this->getRecord();

        return $record->invoice_number;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendEmail')
                ->label('Envoyer par email')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('info')
                ->visible(fn (): bool => auth()->user()?->can('invoices.update') ?? false)
                ->action(function (): void {
                    /** @var Invoice $record */
                    $record = $this->getRecord();
                    $client = $record->client;

                    if (! $client || blank($client->email)) {
                        Notification::make()
                            ->title('Aucun e-mail client')
                            ->body('Ce client n\'a pas d\'adresse e-mail enregistrée.')
                            ->warning()
                            ->send();

                        return;
                    }

                    \Mail::to($client->email)->queue(new InvoiceSentMail($record));

                    DunningLog::create([
                        'invoice_id' => $record->id,
                        'client_id' => $record->client_id,
                        'stage' => '1',
                        'channel' => 'email',
                        'sent_at' => now(),
                        'notes' => 'Facture envoyée par e-mail via le portail admin.',
                        'sent_by' => auth()->id(),
                    ]);

                    app(AuditTrailService::class)->log('invoice_sent_email', $record, [
                        'invoice_number' => $record->invoice_number,
                        'client_email' => $client->email,
                        'sent_by' => auth()->id(),
                    ], auth()->id());

                    Notification::make()
                        ->title('Facture envoyée')
                        ->body('La facture '.$record->invoice_number.' a été envoyée à '.$client->email.'.')
                        ->success()
                        ->send();
                }),
            Action::make('markPaidMobileMoney')
                ->label('Payé via Mobile Money')
                ->icon(Heroicon::OutlinedDevicePhoneMobile)
                ->color('success')
                ->visible(fn (): bool => in_array($this->getRecord()->status, ['sent', 'overdue', 'partially_paid'], true)
                    && (auth()->user()?->can('payments.create') ?? false)
                )
                ->form([
                    Select::make('operator')
                        ->label('Opérateur')
                        ->options([
                            'Orange Money' => 'Orange Money',
                            'Wave' => 'Wave',
                            'Moov Money' => 'Moov Money',
                            'MTN MoMo' => 'MTN MoMo',
                        ])
                        ->native(false)
                        ->required(),
                    TextInput::make('reference')
                        ->label('Numéro de transaction')
                        ->placeholder('Ex : OM-12345678')
                        ->required(),
                    TextInput::make('amount')
                        ->label('Montant reçu (FCFA)')
                        ->numeric()
                        ->minValue(0.01)
                        ->default(fn (): float => (float) $this->getRecord()->balance_due)
                        ->required(),
                ])
                ->modalHeading('Marquer payé via Mobile Money')
                ->action(function (array $data): void {
                    /** @var Invoice $record */
                    $record = $this->getRecord();

                    Payment::create([
                        'invoice_id' => $record->id,
                        'client_id' => $record->client_id,
                        'payment_date' => today(),
                        'amount' => $data['amount'],
                        'payment_method' => 'mobile_money',
                        'mobile_money_operator' => $data['operator'],
                        'reference' => $data['reference'],
                        'recorded_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Paiement enregistré')
                        ->body('Paiement de FCFA '.number_format((float) $data['amount'], 0, '.', ' ').' via '.$data['operator'].' enregistré.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'paid_total', 'balance_due']);
                }),
            Action::make('exportPdf')
                ->label('Télécharger PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(fn (): string => route('invoices.pdf', ['invoice' => $this->getRecord(), 'download' => 1])),
            EditAction::make()->label('Modifier'),
            DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['lg' => 12])
                ->schema([
                    // ── Identity ──────────────────────────────────────────────
                    Section::make('Identité de la facture')
                        ->description('Informations principales du document de facturation.')
                        ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                        ->columnSpan(['lg' => 8])
                        ->columns(['lg' => 2])
                        ->schema([
                            TextEntry::make('invoice_number')
                                ->label('Numéro de facture')
                                ->copyable(),
                            TextEntry::make('client.company_name')
                                ->label('Client')
                                ->state(fn (Invoice $record): string => $record->client?->company_name
                                    ?: $record->client?->contact_name
                                    ?: '—'),
                            TextEntry::make('issue_date')
                                ->label('Date d\'émission')
                                ->date('d/m/Y'),
                            TextEntry::make('due_date')
                                ->label('Date d\'échéance')
                                ->date('d/m/Y')
                                ->placeholder('—'),
                            TextEntry::make('quote.quote_number')
                                ->label('Devis lié')
                                ->placeholder('Facturation directe')
                                ->columnSpanFull(),
                        ]),

                    // ── Status ────────────────────────────────────────────────
                    Section::make('État comptable')
                        ->description('Suivi du recouvrement et niveau d\'urgence.')
                        ->extraAttributes(['class' => 'ledger-summary-card'])
                        ->columnSpan(['lg' => 4])
                        ->schema([
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'paid' => 'success',
                                    'partially_paid' => 'info',
                                    'overdue' => 'danger',
                                    'cancelled', 'draft' => 'gray',
                                    default => 'warning',
                                })
                                ->formatStateUsing(fn (string $state): string => __('erp.resources.invoice.statuses.'.$state)),
                            TextEntry::make('paid_total')
                                ->label('Déjà payé')
                                ->formatStateUsing(fn ($state): string => InvoiceResource::formatMoney((float) $state)),
                            TextEntry::make('balance_due')
                                ->label('Solde restant')
                                ->formatStateUsing(fn ($state): string => InvoiceResource::formatMoney((float) $state))
                                ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),
                        ]),

                    // ── Financial summary ─────────────────────────────────────
                    Section::make('Résumé financier')
                        ->extraAttributes(['class' => 'ledger-summary-card'])
                        ->columnSpan(['lg' => 5])
                        ->schema([
                            TextEntry::make('subtotal')
                                ->label('Sous-total')
                                ->formatStateUsing(fn ($state): string => InvoiceResource::formatMoney((float) $state)),
                            TextEntry::make('discount_total')
                                ->label('Remise')
                                ->formatStateUsing(fn ($state): string => InvoiceResource::formatMoney((float) $state)),
                            TextEntry::make('tax_total')
                                ->label('Taxes')
                                ->formatStateUsing(fn ($state): string => InvoiceResource::formatMoney((float) $state)),
                            TextEntry::make('total')
                                ->label('Total à recevoir')
                                ->formatStateUsing(fn ($state): string => InvoiceResource::formatMoney((float) $state))
                                ->size(TextEntry\TextEntrySize::Large),
                        ]),

                    // ── Notes ─────────────────────────────────────────────────
                    Section::make('Notes administratives')
                        ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                        ->columnSpan(['lg' => 7])
                        ->schema([
                            TextEntry::make('notes')
                                ->label('')
                                ->placeholder('Aucune note enregistrée.')
                                ->columnSpanFull(),
                        ]),
                ]),
        ])->columns(1);
    }
}
