<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Quote $record */
        $record = $this->getRecord();

        return $record->quote_number;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('convertToInvoice')
                ->label('Convertir en facture')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->visible(fn(): bool =>
                    /** @var Quote $record */
                    in_array($this->getRecord()->status, ['draft', 'sent', 'accepted', 'expired'], true)
                    && !$this->getRecord()->invoice()->exists()
                    && (auth()->user()?->can('invoices.create') ?? false))
                ->requiresConfirmation()
                ->modalHeading('Convertir le devis en facture')
                ->modalDescription('Une nouvelle facture sera créée à partir de ce devis. Le devis sera marqué comme accepté.')
                ->action(function (): void {
                    /** @var Quote $record */
                    $record = $this->getRecord();
                    $invoice = $record->convertToInvoice(auth()->id());

                    Notification::make()
                        ->title('Facture créée')
                        ->body('La facture ' . $invoice->invoice_number . ' a été créée à partir du devis ' . $record->quote_number . '.')
                        ->success()
                        ->send();

                    $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
                }),
            Action::make('exportPdf')
                ->label('Télécharger PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(fn(): string => route('quotes.pdf', ['quote' => $this->getRecord(), 'download' => 1])),
            EditAction::make()->label('Modifier'),
            DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['lg' => 12])
                ->schema([
                    // ── Header ────────────────────────────────────────────────
                    Section::make('Informations du devis')
                        ->description('Détails du document commercial.')
                        ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                        ->columnSpan(['lg' => 8])
                        ->columns(['lg' => 2])
                        ->schema([
                            TextEntry::make('quote_number')
                                ->label('Numéro du devis')
                                ->copyable(),
                            TextEntry::make('client.company_name')
                                ->label('Client')
                                ->state(fn(Quote $record): string =>
                                    $record->client?->company_name
                                    ?: $record->client?->contact_name
                                    ?: '—'),
                            TextEntry::make('issue_date')
                                ->label('Date d\'émission')
                                ->date('d/m/Y'),
                            TextEntry::make('valid_until')
                                ->label('Valide jusqu\'au')
                                ->date('d/m/Y')
                                ->placeholder('—'),
                        ]),

                    // ── Status ────────────────────────────────────────────────
                    Section::make('Cycle de vie du devis')
                        ->description('État du devis et contexte de validation.')
                        ->extraAttributes(['class' => 'ledger-summary-card'])
                        ->columnSpan(['lg' => 4])
                        ->schema([
                            TextEntry::make('status')
                                ->label('Statut')
                                ->badge()
                                ->formatStateUsing(fn(string $state): string => match ($state) {
                                    'draft' => 'Brouillon',
                                    'sent' => 'Envoyé au client',
                                    'accepted' => 'Accepté',
                                    'expired' => 'Expiré',
                                    default => $state,
                                }),
                            TextEntry::make('invoice.invoice_number')
                                ->label('Facture associée')
                                ->placeholder('Pas encore converti'),
                        ]),

                    // ── Financial summary ─────────────────────────────────────
                    Section::make('Résumé financier')
                        ->extraAttributes(['class' => 'ledger-summary-card'])
                        ->columnSpan(['lg' => 5])
                        ->schema([
                            TextEntry::make('subtotal')
                                ->label('Sous-total')
                                ->formatStateUsing(fn($state): string =>
                                    QuoteResource::formatMoney((float) $state)),
                            TextEntry::make('discount_total')
                                ->label('Remise')
                                ->formatStateUsing(fn($state): string =>
                                    QuoteResource::formatMoney((float) $state)),
                            TextEntry::make('tax_total')
                                ->label('Taxes')
                                ->formatStateUsing(fn($state): string =>
                                    QuoteResource::formatMoney((float) $state)),
                            TextEntry::make('total')
                                ->label('Total général')
                                ->formatStateUsing(fn($state): string =>
                                    QuoteResource::formatMoney((float) $state))
                                ->size(TextSize::Large),
                        ]),

                    // ── Notes ─────────────────────────────────────────────────
                    Section::make('Notes et conditions')
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
