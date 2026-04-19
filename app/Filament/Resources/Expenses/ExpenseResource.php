<?php

namespace App\Filament\Resources\Expenses;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Models\Expense;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'expenses';

    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Opérations';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Dépenses';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Détails de la dépense')
                            ->description('Enregistrez les frais, remboursements et sorties de trésorerie à suivre.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('title')
                                    ->label('Intitulé')
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('category')
                                    ->label('Catégorie')
                                    ->options([
                                        'travel' => 'Déplacement',
                                        'supplies' => 'Fournitures',
                                        'operations' => 'Exploitation',
                                        'payroll' => 'Personnel',
                                        'compliance' => 'Conformité',
                                        'other' => 'Autre',
                                    ])
                                    ->native(false)
                                    ->default('operations')
                                    ->required(),
                                DatePicker::make('expense_date')
                                    ->label('Date')
                                    ->default(now())
                                    ->required(),
                                TextInput::make('vendor')
                                    ->label('Fournisseur'),
                                Select::make('payment_method')
                                    ->label('Mode de paiement')
                                    ->options([
                                        'bank_transfer' => 'Virement bancaire',
                                        'cash' => 'Espèces',
                                        'check' => 'Chèque',
                                        'mobile_money' => 'Mobile money',
                                    ])
                                    ->native(false),
                                TextInput::make('reference')
                                    ->label('Référence'),
                                TextInput::make('amount')
                                    ->label('Montant')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->minValue(0.01)
                                    ->required(),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Validation')
                            ->description('Suivi managérial et état de traitement.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Select::make('approval_status')
                                    ->label('Statut de validation')
                                    ->options([
                                        'pending' => 'En attente',
                                        'approved' => 'Approuvée',
                                        'review' => 'À vérifier',
                                        'rejected' => 'Rejetée',
                                    ])
                                    ->default('pending')
                                    ->native(false)
                                    ->required(),
                                Placeholder::make('approval_hint')
                                    ->label('Note')
                                    ->content('Les frais approuvés sont tracés dans le journal d’activité.'),
                                Textarea::make('approval_notes')
                                    ->label('Commentaires de validation')
                                    ->rows(5),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Dépense')
                    ->description(fn(Expense $record): string => $record->vendor ?: 'Fournisseur non renseigné')
                    ->searchable(['title', 'vendor', 'reference'])
                    ->wrap(),
                TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn($state): string => 'FCFA ' . number_format((float) $state, 2, '.', ' '))
                    ->sortable(),
                TextColumn::make('expense_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('approval_status')
                    ->label('Validation')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'approved' => 'Approuvée',
                        'review' => 'À vérifier',
                        'rejected' => 'Rejetée',
                        default => 'En attente',
                    }),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'review' => 'À vérifier',
                        'rejected' => 'Rejetée',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
                    ->color('success')
                    ->visible(fn(Expense $record): bool => $record->approval_status !== 'approved' && (auth()->user()?->can('expenses.update') ?? false))
                    ->action(function (Expense $record): void {
                        $record->approve(auth()->user(), 'Validation effectuée depuis le terminal de gestion.');

                        Notification::make()
                            ->title('Dépense approuvée.')
                            ->success()
                            ->send();
                    }),
                Action::make('review')
                    ->label('Demander une revue')
                    ->color('warning')
                    ->visible(fn(Expense $record): bool => $record->approval_status === 'pending' && (auth()->user()?->can('expenses.update') ?? false))
                    ->action(function (Expense $record): void {
                        $record->markForReview(auth()->user(), 'Contrôle complémentaire demandé.');

                        Notification::make()
                            ->title('La dépense a été marquée pour vérification.')
                            ->warning()
                            ->send();
                    }),
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
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
