<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
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
                                ->state(fn (Invoice $record): string =>
                                    $record->client?->company_name
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
                                    'paid'           => 'success',
                                    'partially_paid' => 'info',
                                    'overdue'        => 'danger',
                                    'cancelled', 'draft' => 'gray',
                                    default          => 'warning',
                                })
                                ->formatStateUsing(fn (string $state): string =>
                                    __('erp.resources.invoice.statuses.' . $state)),
                            TextEntry::make('paid_total')
                                ->label('Déjà payé')
                                ->formatStateUsing(fn ($state): string =>
                                    InvoiceResource::formatMoney((float) $state)),
                            TextEntry::make('balance_due')
                                ->label('Solde restant')
                                ->formatStateUsing(fn ($state): string =>
                                    InvoiceResource::formatMoney((float) $state))
                                ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success'),
                        ]),

                    // ── Financial summary ─────────────────────────────────────
                    Section::make('Résumé financier')
                        ->extraAttributes(['class' => 'ledger-summary-card'])
                        ->columnSpan(['lg' => 5])
                        ->schema([
                            TextEntry::make('subtotal')
                                ->label('Sous-total')
                                ->formatStateUsing(fn ($state): string =>
                                    InvoiceResource::formatMoney((float) $state)),
                            TextEntry::make('discount_total')
                                ->label('Remise')
                                ->formatStateUsing(fn ($state): string =>
                                    InvoiceResource::formatMoney((float) $state)),
                            TextEntry::make('tax_total')
                                ->label('Taxes')
                                ->formatStateUsing(fn ($state): string =>
                                    InvoiceResource::formatMoney((float) $state)),
                            TextEntry::make('total')
                                ->label('Total à recevoir')
                                ->formatStateUsing(fn ($state): string =>
                                    InvoiceResource::formatMoney((float) $state))
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
