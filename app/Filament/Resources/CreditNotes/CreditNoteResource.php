<?php

namespace App\Filament\Resources\CreditNotes;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\CreditNotes\Pages\CreateCreditNote;
use App\Filament\Resources\CreditNotes\Pages\EditCreditNote;
use App\Filament\Resources\CreditNotes\Pages\ListCreditNotes;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\CreditNote;
use App\Models\FinancialPeriod;
use App\Models\Invoice;
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

class CreditNoteResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'invoices';

    protected static ?string $model = CreditNote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Avoirs';

    protected static ?string $recordTitleAttribute = 'credit_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Identité de l\'avoir')
                            ->description('Émettez un avoir lié à une facture existante avec traçabilité comptable.')
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('credit_number')
                                    ->label('Numéro de l\'avoir')
                                    ->default(fn(): string => static::generateCreditNumber())
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->helperText('Numéro généré automatiquement et conservé après création.'),
                                Select::make('invoice_id')
                                    ->label('Facture liée')
                                    ->relationship('invoice', 'invoice_number')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required(),
                                DatePicker::make('issue_date')
                                    ->label('Date d\'émission')
                                    ->default(now())
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        $set('credit_number', static::generateCreditNumber($state));
                                    })
                                    ->required(),
                                Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'issued' => 'Émis',
                                        'pending_approval' => 'En attente d\'approbation',
                                        'approved' => 'Approuvé',
                                        'void' => 'Annulé',
                                    ])
                                    ->default('issued')
                                    ->native(false)
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Montant')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->minValue(0.01)
                                    ->required(),
                                Placeholder::make('issue_note')
                                    ->label('Contrôle')
                                    ->content('Le montant de l\'avoir ne peut pas dépasser le total de la facture.'),
                            ]),
                        Section::make('Motif')
                            ->description('Documentez clairement la raison de l\'avoir pour audit.')
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Textarea::make('reason')
                                    ->label('Motif')
                                    ->rows(8)
                                    ->required()
                                    ->placeholder('Exemple: rectification de facture, geste commercial, erreur de facturation...'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('credit_number')
                    ->label('Avoir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Facture liée')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->formatStateUsing(fn($state): string => 'FCFA ' . number_format((float) $state, 2, '.', ' '))
                    ->sortable(),
                TextColumn::make('period_lock_status')
                    ->label('Période')
                    ->state(fn(CreditNote $record): string => static::lockStatusLabel($record))
                    ->badge()
                    ->color(fn(CreditNote $record): string => static::lockStatusColor($record)),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'issued' => 'Émis',
                        'pending_approval' => 'En attente',
                        'approved' => 'Approuvé',
                        'void' => 'Annulé',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending_approval' => 'warning',
                        'void' => 'gray',
                        default => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'issued' => 'Émis',
                        'pending_approval' => 'En attente d\'approbation',
                        'approved' => 'Approuvé',
                        'void' => 'Annulé',
                    ]),
            ])
            ->recordActions([
                Action::make('exportPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn(CreditNote $record): string => route('credit-notes.pdf', ['creditNote' => $record, 'download' => 1])),
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
        return $record instanceof CreditNote && FinancialPeriod::isDateLocked($record->issue_date);
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
            'index' => ListCreditNotes::route('/'),
            'create' => CreateCreditNote::route('/create'),
            'edit' => EditCreditNote::route('/{record}/edit'),
        ];
    }

    public static function generateCreditNumber(mixed $issueDate = null): string
    {
        $date = $issueDate ? Carbon::parse($issueDate) : now();
        $prefix = 'AV-' . $date->format('Ymd') . '-';

        $max = CreditNote::query()
            ->where('credit_number', 'like', $prefix . '%')
            ->pluck('credit_number')
            ->reduce(function (int $carry, ?string $creditNumber) use ($prefix): int {
                if (!filled($creditNumber) || !str_starts_with($creditNumber, $prefix)) {
                    return $carry;
                }

                $suffix = substr($creditNumber, strlen($prefix));

                if (!ctype_digit($suffix)) {
                    return $carry;
                }

                return max($carry, (int) $suffix);
            }, 0);

        return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
