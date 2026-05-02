<?php

namespace App\Filament\Resources\Quotes;

use App\Filament\Concerns\HasBillingFormConcerns;
use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Service;
use App\Services\AuditTrailService;
use App\Services\SequenceService;
use BackedEnum;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuoteResource extends Resource
{
    use HasPermissionAccess;
    use HasBillingFormConcerns;

    protected static string $permissionScope = 'quotes';

    protected static ?string $model = Quote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Devis';

    protected static ?string $recordTitleAttribute = 'quote_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Informations du devis')
                            ->description('Préparez un devis professionnel à envoyer au client.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('quote_number')
                                    ->label('Numéro du devis')
                                    ->default(fn(): string => static::generateQuoteNumber())
                                    ->readOnly()
                                    ->required(),
                                Select::make('client_id')
                                    ->label('Client')
                                    ->relationship('client', 'company_name')
                                    ->getOptionLabelFromRecordUsing(fn(Client $record): string => $record->company_name ?: $record->contact_name ?: ('Client #' . $record->getKey()))
                                    ->searchable(['company_name', 'contact_name', 'email'])
                                    ->preload()
                                    ->required(),
                                DatePicker::make('issue_date')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('valid_until')
                                    ->default(now()->addDays(15)),
                            ]),
                        Section::make('Cycle de vie du devis')
                            ->description('Suivez l’état du devis et le contexte de validation interne.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Brouillon',
                                        'sent' => 'Envoyé au client',
                                        'accepted' => 'Accepté',
                                        'expired' => 'Expiré',
                                    ])
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                Placeholder::make('lifecycle_hint')
                                    ->label('Note')
                                    ->content('Les brouillons restent internes jusqu’à leur validation et envoi.'),
                            ]),
                        Section::make('Lignes de prestation')
                            ->description('Construisez le périmètre commercial avec les services et leurs tarifs.')
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
                                            ->label('Total')
                                            ->content(fn(Get $get): string => static::formatMoney(((float) ($get('quantity') ?? 0)) * ((float) ($get('unit_price') ?? 0)))),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Conditions')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpan(['lg' => 7])
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Notes et conditions')
                                    ->rows(6)
                                    ->placeholder('Conditions de règlement, remarques techniques ou éléments contractuels...'),
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
                                Placeholder::make('total_preview')
                                    ->label('Total général')
                                    ->content(fn(Get $get): string => static::formatMoney(static::calculateTotals((array) ($get('items') ?? []), (float) ($get('discount_total') ?? 0), (float) ($get('tax_total') ?? 0))['total'])),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issue_date', 'desc')
            ->recordUrl(fn(Quote $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('quote_number')
                    ->searchable(),
                TextColumn::make('client_name')
                    ->label('Client')
                    ->state(fn(Quote $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: 'Compte client')
                    ->searchable(),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total')
                    ->formatStateUsing(fn($state): string => static::formatMoney((float) $state))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Mis à jour'),
            ])
            ->recordActions([
                Action::make('convertToInvoice')
                    ->label('Convertir en facture')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn(Quote $record): bool =>
                        in_array($record->status, ['draft', 'sent', 'accepted', 'expired'], true)
                        && !$record->invoice()->exists()
                        && (auth()->user()?->can('invoices.create') ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Convertir le devis en facture')
                    ->modalDescription('Une nouvelle facture sera créée à partir de ce devis. Le devis sera marqué comme accepté.')
                    ->action(function (Quote $record): void {
                        $invoice = $record->convertToInvoice(auth()->id());

                        Notification::make()
                            ->title('Facture créée')
                            ->body('La facture ' . $invoice->invoice_number . ' a été créée à partir du devis ' . $record->quote_number . '.')
                            ->success()
                            ->send();

                        redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                    }),
                Action::make('sendWhatsapp')
                    ->label('Envoyer via WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn(Quote $record): bool => filled($record->client?->phone) && (auth()->user()?->can('quotes.view') ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer le devis via WhatsApp')
                    ->modalDescription('Le devis sera envoyé en PDF via WhatsApp au numéro du client.')
                    ->action(function (Quote $record, \App\Services\Whatsapp\WhatsappSendService $service): void {
                        $log = $service->sendQuote($record);
                        if ($log->status === 'sent') {
                            Notification::make()->title('Devis envoyé via WhatsApp')->success()->send();
                        } else {
                            Notification::make()->title('Échec de l\'envoi WhatsApp')->body($log->error_message)->danger()->send();
                        }
                    }),
                Action::make('exportPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn(Quote $record): string => route('quotes.pdf', ['quote' => $record, 'download' => 1])),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['client']);
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'view' => ViewQuote::route('/{record}'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }

    public static function generateQuoteNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'QT-' . $year;
        $sep = '-';
        $seq = app(SequenceService::class)->next('quote', $year);

        return $prefix . $sep . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
