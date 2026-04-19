<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'invoices';

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Identité de la facture')
                            ->description('Créez un document de facturation officiel lié au suivi comptable opérationnel.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->label('Numéro de facture')
                                    ->default(fn(): string => static::generateInvoiceNumber())
                                    ->readOnly()
                                    ->required(),
                                Select::make('client_id')
                                    ->label('Client')
                                    ->relationship('client', 'company_name')
                                    ->getOptionLabelFromRecordUsing(fn(Client $record): string => $record->company_name ?: $record->contact_name ?: ('Client #' . $record->getKey()))
                                    ->searchable(['company_name', 'contact_name', 'email'])
                                    ->preload()
                                    ->required(),
                                Select::make('quote_id')
                                    ->label('Devis lié')
                                    ->relationship('quote', 'quote_number')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $quote = Quote::find($state);

                                        if (!$quote) {
                                            return;
                                        }

                                        $set('client_id', $quote->client_id);
                                        $set('discount_total', $quote->discount_total);
                                        $set('tax_total', $quote->tax_total);
                                        $set('notes', $quote->notes);
                                    }),
                                DatePicker::make('issue_date')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('due_date')
                                    ->default(now()->addDays(30)),
                            ]),
                        Section::make('État comptable')
                            ->description('Suivez le recouvrement et le niveau d’urgence du paiement.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Brouillon',
                                        'sent' => 'Envoyée',
                                        'partially_paid' => 'Partiellement payée',
                                        'paid' => 'Payée',
                                        'overdue' => 'En retard',
                                        'cancelled' => 'Annulée',
                                    ])
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                Placeholder::make('collection_brief')
                                    ->label('Résumé du recouvrement')
                                    ->content('Suivez ici les créances, les relances clients et les retards de règlement.'),
                            ]),
                        Section::make('Lignes de facture')
                            ->description('Définissez les prestations facturables, quantités et prix unitaires.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-tertiary'])
                            ->columnSpanFull()
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->defaultItems(1)
                                    ->addActionLabel('Ajouter une ligne')
                                    ->schema([
                                        Select::make('service_id')
                                            ->label('Service')
                                            ->relationship('service', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                if (blank($state)) {
                                                    return;
                                                }

                                                $service = Service::find($state);

                                                if (!$service) {
                                                    return;
                                                }

                                                $set('description', $service->name);
                                                $set('unit_price', $service->default_price);
                                            }),
                                        TextInput::make('description')
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0)
                                            ->required()
                                            ->live(),
                                        TextInput::make('unit_price')
                                            ->numeric()
                                            ->prefix('FCFA')
                                            ->default(0)
                                            ->minValue(0)
                                            ->required()
                                            ->live(),
                                        Placeholder::make('line_total_preview')
                                            ->label('Total de la ligne')
                                            ->content(fn(Get $get): string => static::formatMoney(((float) ($get('quantity') ?? 0)) * ((float) ($get('unit_price') ?? 0)))),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Notes de facturation')
                            ->description('Ajoutez les instructions, remarques d’envoi et consignes de recouvrement.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpan(['lg' => 7])
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Notes administratives')
                                    ->rows(6)
                                    ->placeholder('Ajoutez les instructions de paiement, le contexte interne ou les références contractuelles...'),
                            ]),
                        Section::make('Résumé financier')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 5])
                            ->schema([
                                TextInput::make('discount_total')
                                    ->label('Remise totale')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->live(),
                                TextInput::make('tax_total')
                                    ->label('Taxes totales')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->live(),
                                Placeholder::make('subtotal_preview')
                                    ->label('Sous-total')
                                    ->content(fn(Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['subtotal'])),
                                Placeholder::make('grand_total_preview')
                                    ->label('Total à recevoir')
                                    ->content(fn(Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['total'])),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Facture')
                    ->description(fn(Invoice $record): string => $record->quote?->quote_number ? 'Liée au devis ' . $record->quote->quote_number : 'Facturation indépendante')
                    ->searchable(),
                TextColumn::make('client_name')
                    ->label('Client')
                    ->state(fn(Invoice $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: 'Compte client')
                    ->description(fn(Invoice $record): string => $record->client?->email ?: 'Aucun e-mail de facturation'),
                TextColumn::make('issue_date')
                    ->label('Émission / échéance')
                    ->state(fn(Invoice $record): string => optional($record->issue_date)->format('M d, Y') ?? 'Non émise')
                    ->description(fn(Invoice $record): string => $record->due_date ? 'Échéance ' . (optional($record->due_date)->format('M d, Y') ?? 'À définir') : 'Aucune échéance')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Montant total')
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->label('Reste dû')
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->description(fn(Invoice $record): string => (float) $record->paid_total > 0 ? 'Payé : ' . static::formatMoney((float) $record->paid_total) : 'Aucun paiement enregistré')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'partially_paid' => 'info',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        'draft' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => str_replace('_', ' ', ucfirst($state))),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyée',
                        'partially_paid' => 'Partiellement payée',
                        'paid' => 'Payée',
                        'overdue' => 'En retard',
                        'cancelled' => 'Annulée',
                    ]),
            ])
            ->recordActions([
                Action::make('sendReminder')
                    ->label('Envoyer un rappel')
                    ->visible(fn(Invoice $record): bool => in_array($record->status, ['sent', 'overdue', 'partially_paid'], true) && (auth()->user()?->can('invoices.update') ?? false))
                    ->action(fn(Invoice $record) => Notification::make()->title('Rappel préparé pour ' . $record->invoice_number . '.')->success()->send()),
                Action::make('exportPdf')
                    ->label('Exporter en PDF')
                    ->visible(fn(): bool => auth()->user()?->canAny(['invoices.view', 'reports.view']) ?? false)
                    ->action(fn(Invoice $record) => Notification::make()->title('Export PDF préparé pour ' . $record->invoice_number . '.')->success()->send()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function generateInvoiceNumber(): string
    {
        $next = (Invoice::max('id') ?? 0) + 1;

        return 'INV-' . now()->format('Y') . '-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    protected static function calculateTotals(array $items, float $discount, float $tax): array
    {
        $subtotal = collect($items)->sum(fn(array $item): float => ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)));

        return [
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discount + $tax),
        ];
    }

    protected static function formatMoney(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 2, '.', ' ');
    }
}
