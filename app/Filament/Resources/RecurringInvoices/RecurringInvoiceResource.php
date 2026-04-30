<?php

namespace App\Filament\Resources\RecurringInvoices;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\RecurringInvoices\Pages\CreateRecurringInvoice;
use App\Filament\Resources\RecurringInvoices\Pages\EditRecurringInvoice;
use App\Filament\Resources\RecurringInvoices\Pages\ListRecurringInvoices;
use App\Models\Client;
use App\Models\RecurringInvoice;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecurringInvoiceResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'invoices';

    protected static ?string $model = RecurringInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Factures récurrentes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['lg' => 12])
                ->schema([
                    Section::make('Paramètres de récurrence')
                        ->description('Configurez la périodicité et les conditions de génération automatique.')
                        ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                        ->columnSpan(['lg' => 8])
                        ->columns(['lg' => 2])
                        ->schema([
                            Select::make('client_id')
                                ->label('Client')
                                ->relationship('client', 'company_name')
                                ->getOptionLabelFromRecordUsing(fn (Client $record): string => $record->company_name ?: $record->contact_name ?: ('Client #'.$record->getKey()))
                                ->searchable(['company_name', 'contact_name', 'email'])
                                ->preload()
                                ->required(),
                            Select::make('frequency')
                                ->label('Fréquence')
                                ->options([
                                    'daily' => 'Quotidienne',
                                    'weekly' => 'Hebdomadaire',
                                    'monthly' => 'Mensuelle',
                                    'quarterly' => 'Trimestrielle',
                                    'yearly' => 'Annuelle',
                                ])
                                ->native(false)
                                ->required(),
                            DatePicker::make('start_date')
                                ->label('Date de début')
                                ->default(today())
                                ->required(),
                            DatePicker::make('next_due_date')
                                ->label('Prochaine génération')
                                ->default(today())
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('Date de fin')
                                ->helperText('Laissez vide pour une récurrence sans fin.'),
                            TextInput::make('net_days')
                                ->label('Délai de paiement (jours)')
                                ->numeric()
                                ->default(30)
                                ->minValue(0)
                                ->required(),
                            TextInput::make('amount')
                                ->label('Montant (FCFA)')
                                ->numeric()
                                ->prefix('FCFA')
                                ->default(0)
                                ->minValue(0)
                                ->required()
                                ->columnSpanFull(),
                            Textarea::make('description')
                                ->label('Description de la prestation')
                                ->placeholder('Ex : Loyer mensuel bureau, Abonnement maintenance...')
                                ->columnSpanFull(),
                        ]),
                    Section::make('Statut et notes')
                        ->extraAttributes(['class' => 'ledger-summary-card'])
                        ->columnSpan(['lg' => 4])
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Modèle actif')
                                ->default(true)
                                ->helperText('Seuls les modèles actifs sont traités par le planificateur.'),
                            Textarea::make('notes')
                                ->label('Notes internes')
                                ->rows(5)
                                ->placeholder('Contexte contractuel, conditions spéciales...'),
                        ]),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_name')
                    ->label('Client')
                    ->state(fn (RecurringInvoice $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: '—')
                    ->searchable(),
                TextColumn::make('frequency')
                    ->label('Fréquence')
                    ->badge()
                    ->formatStateUsing(fn (RecurringInvoice $record): string => $record->frequencyLabel()),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state): string => 'FCFA '.number_format((float) $state, 0, '.', ' '))
                    ->sortable(),
                TextColumn::make('next_due_date')
                    ->label('Prochaine génération')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->placeholder('Sans fin')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->trueLabel('Actifs uniquement')
                    ->falseLabel('Inactifs uniquement')
                    ->placeholder('Tous'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucune facture récurrente')
            ->emptyStateDescription('Créez un modèle récurrent pour loyers, abonnements ou prestations régulières.')
            ->emptyStateIcon(Heroicon::OutlinedArrowPath);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('client');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecurringInvoices::route('/'),
            'create' => CreateRecurringInvoice::route('/create'),
            'edit' => EditRecurringInvoice::route('/{record}/edit'),
        ];
    }
}
