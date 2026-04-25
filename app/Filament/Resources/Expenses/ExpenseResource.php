<?php

namespace App\Filament\Resources\Expenses;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\Expense;
use App\Models\FinancialPeriod;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
                                    ->label(__('erp.common.category'))
                                    ->options(trans('erp.resources.expense.categories'))
                                    ->native(false)
                                    ->default('operations')
                                    ->required(),
                                DatePicker::make('expense_date')
                                    ->label('Date')
                                    ->default(now())
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        $set('reference', static::generateExpenseReference($state));
                                    })
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
                                    ->label('Référence')
                                    ->placeholder('EXP-20260424-0001')
                                    ->default(fn(): string => static::generateExpenseReference())
                                    ->helperText('Référence générée automatiquement, modifiable si nécessaire.'),
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
                                    ->label(__('erp.common.validation'))
                                    ->options(trans('erp.resources.expense.approval_statuses'))
                                    ->default('pending')
                                    ->native(false)
                                    ->required(),
                                Placeholder::make('approval_hint')
                                    ->label(__('erp.common.notes'))
                                    ->content(__('erp.resources.expense.approval_hint')),
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
                    ->label(__('erp.common.expense'))
                    ->description(fn(Expense $record): string => $record->vendor ?: __('erp.resources.expense.vendor_missing'))
                    ->searchable(['title', 'vendor', 'reference'])
                    ->wrap(),
                TextColumn::make('category')
                    ->label(__('erp.common.category'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => __('erp.resources.expense.categories.' . $state)),
                TextColumn::make('amount')
                    ->label(__('erp.common.amount'))
                    ->formatStateUsing(fn($state): string => 'FCFA ' . number_format((float) $state, 2, '.', ' '))
                    ->sortable(),
                TextColumn::make('period_lock_status')
                    ->label('Période')
                    ->state(fn(Expense $record): string => static::lockStatusLabel($record))
                    ->badge()
                    ->color(fn(Expense $record): string => static::lockStatusColor($record)),
                TextColumn::make('expense_date')
                    ->label(__('erp.common.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('approval_status')
                    ->label(__('erp.common.validation'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => __('erp.resources.expense.approval_statuses.' . $state)),
                TextColumn::make('updated_at')
                    ->label(__('erp.common.updated_at'))
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label(__('erp.common.validation'))
                    ->options(trans('erp.resources.expense.approval_statuses')),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('erp.actions.approve'))
                    ->color('success')
                    ->visible(fn(Expense $record): bool => $record->approval_status !== 'approved' && (auth()->user()?->can('expenses.update') ?? false))
                    ->action(function (Expense $record): void {
                        $record->approve(auth()->user(), 'Validation effectuée depuis le terminal de gestion.');

                        Notification::make()
                            ->title(__('erp.resources.expense.approved_notification'))
                            ->success()
                            ->send();
                    }),
                Action::make('review')
                    ->label(__('erp.actions.review'))
                    ->color('warning')
                    ->visible(fn(Expense $record): bool => $record->approval_status === 'pending' && (auth()->user()?->can('expenses.update') ?? false))
                    ->action(function (Expense $record): void {
                        $record->markForReview(auth()->user(), 'Contrôle complémentaire demandé.');

                        Notification::make()
                            ->title(__('erp.resources.expense.review_notification'))
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

    public static function canEdit(Model $record): bool
    {
        return static::canAccessPermission('update')
            && (!static::isRecordLocked($record) || FinancialPeriod::currentUserCanOverrideLock());
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccessPermission('delete')
            && (!static::isRecordLocked($record) || FinancialPeriod::currentUserCanOverrideLock());
    }

    protected static function isRecordLocked(Model $record): bool
    {
        return $record instanceof Expense && FinancialPeriod::isDateLocked($record->expense_date);
    }

    protected static function lockStatusLabel(Model $record): string
    {
        if (!static::isRecordLocked($record)) {
            return 'Ouverte';
        }

        return FinancialPeriod::currentUserCanOverrideLock() ? 'Dérogation' : 'Clôturée';
    }

    protected static function lockStatusColor(Model $record): string
    {
        if (!static::isRecordLocked($record)) {
            return 'success';
        }

        return FinancialPeriod::currentUserCanOverrideLock() ? 'warning' : 'danger';
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
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }

    public static function generateExpenseReference(mixed $expenseDate = null): string
    {
        $date = $expenseDate ? Carbon::parse($expenseDate) : now();
        $prefix = 'EXP-' . $date->format('Ymd') . '-';

        $max = Expense::query()
            ->whereNotNull('reference')
            ->where('reference', 'like', $prefix . '%')
            ->pluck('reference')
            ->reduce(function (int $carry, ?string $reference) use ($prefix): int {
                if (!filled($reference) || !str_starts_with($reference, $prefix)) {
                    return $carry;
                }

                $suffix = substr($reference, strlen($prefix));

                if (!ctype_digit($suffix)) {
                    return $carry;
                }

                return max($carry, (int) $suffix);
            }, 0);

        return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
