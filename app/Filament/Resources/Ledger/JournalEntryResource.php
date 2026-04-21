<?php

namespace App\Filament\Resources\Ledger;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Ledger\Pages\CreateJournalEntry;
use App\Filament\Resources\Ledger\Pages\EditJournalEntry;
use App\Filament\Resources\Ledger\Pages\ListJournalEntries;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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

class JournalEntryResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'ledger';

    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = null;

    protected static ?string $recordTitleAttribute = 'entry_number';

    public static function getNavigationLabel(): string
    {
        return __('erp.ledger.nav_label');
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Journal entries must be voided, not deleted
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make(__('erp.ledger.journal_entry'))
                            ->description(__('erp.ledger.entry_desc'))
                            ->columnSpan(['lg' => 12])
                            ->columns(['lg' => 3])
                            ->schema([
                                TextInput::make('entry_number')
                                    ->label(__('erp.ledger.entry_number'))
                                    ->readOnly()
                                    ->placeholder('Assigned automatically'),
                                DatePicker::make('entry_date')
                                    ->label(__('erp.common.date'))
                                    ->default(now())
                                    ->required(),
                                Select::make('status')
                                    ->label(__('erp.common.status'))
                                    ->options(trans('erp.ledger.statuses'))
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                Textarea::make('description')
                                    ->label(__('erp.common.description'))
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                        Section::make(__('erp.ledger.journal_lines'))
                            ->columnSpan(['lg' => 12])
                            ->schema([
                                Repeater::make('lines')
                                    ->relationship()
                                    ->label('')
                                    ->schema([
                                        Select::make('account_id')
                                            ->label(__('erp.ledger.account'))
                                            ->options(
                                                LedgerAccount::active()
                                                    ->orderBy('code')
                                                    ->get()
                                                    ->mapWithKeys(fn(LedgerAccount $a): array => [$a->id => $a->code . ' – ' . $a->name])
                                            )
                                            ->searchable()
                                            ->required()
                                            ->native(false),
                                        TextInput::make('description')
                                            ->label(__('erp.common.description'))
                                            ->nullable(),
                                        TextInput::make('debit')
                                            ->label(__('erp.ledger.debit'))
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0),
                                        TextInput::make('credit')
                                            ->label(__('erp.ledger.credit'))
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0),
                                    ])
                                    ->columns(4)
                                    ->minItems(2)
                                    ->addActionLabel(__('erp.ledger.journal_lines')),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entry_number')
                    ->label(__('erp.ledger.entry_number'))
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('entry_date')
                    ->label(__('erp.common.date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('erp.common.description'))
                    ->limit(60),
                TextColumn::make('status')
                    ->label(__('erp.common.status'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => (string) __('erp.ledger.statuses.' . $state, [], null) ?: $state)
                    ->color(fn(string $state): string => match ($state) {
                        'posted' => 'success',
                        'voided' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('source_type')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => $state ? ((string) __('erp.ledger.source_types.' . $state, [], null) ?: $state) : '—'),
                TextColumn::make('lines_sum_debit')
                    ->label(__('erp.ledger.debit'))
                    ->sum('lines', 'debit')
                    ->numeric(2)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('erp.common.status'))
                    ->options(trans('erp.ledger.statuses')),
                SelectFilter::make('source_type')
                    ->label('Source')
                    ->options(trans('erp.ledger.source_types')),
            ])
            ->defaultSort('entry_date', 'desc')
            ->recordAction('edit')
            ->actions([
                EditAction::make(),
                Action::make('post')
                    ->label(__('erp.ledger.post_action'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn(JournalEntry $record): bool => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (JournalEntry $record): void {
                        try {
                            $record->post(auth()->id());
                            Notification::make()->success()->title(__('erp.ledger.posted_notification'))->send();
                        } catch (\Throwable $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('void')
                    ->label(__('erp.ledger.void_action'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn(JournalEntry $record): bool => $record->status === 'posted')
                    ->form([
                        Textarea::make('void_reason')
                            ->label(__('erp.ledger.void_reason'))
                            ->required(),
                    ])
                    ->action(function (JournalEntry $record, array $data): void {
                        $record->void(auth()->id(), $data['void_reason']);
                        Notification::make()->warning()->title(__('erp.ledger.voided_notification'))->send();
                    }),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'edit'   => EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
