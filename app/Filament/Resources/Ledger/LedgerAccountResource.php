<?php

namespace App\Filament\Resources\Ledger;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Ledger\Pages\CreateLedgerAccount;
use App\Filament\Resources\Ledger\Pages\EditLedgerAccount;
use App\Filament\Resources\Ledger\Pages\ListLedgerAccounts;
use App\Models\LedgerAccount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LedgerAccountResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'ledger';

    protected static ?string $model = LedgerAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = null;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('erp.ledger.accounts_nav_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make(__('erp.ledger.chart_title'))
                            ->description(__('erp.ledger.chart_desc'))
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('erp.ledger.account_code'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20),
                                TextInput::make('name')
                                    ->label(__('erp.ledger.account_name'))
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('type')
                                    ->label(__('erp.ledger.account_type'))
                                    ->options(trans('erp.ledger.account_types'))
                                    ->native(false)
                                    ->required()
                                    ->live(),
                                Select::make('normal_balance')
                                    ->label(__('erp.ledger.normal_balance'))
                                    ->options(trans('erp.ledger.normal_balances'))
                                    ->native(false)
                                    ->required(),
                                Select::make('parent_id')
                                    ->label(__('erp.ledger.parent_account'))
                                    ->relationship('parent', 'name')
                                    ->getOptionLabelFromRecordUsing(fn(LedgerAccount $r): string => $r->code . ' – ' . $r->name)
                                    ->searchable(['code', 'name'])
                                    ->nullable()
                                    ->placeholder(__('erp.ledger.no_parent'))
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->label(__('erp.common.description'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                        Section::make(__('erp.common.status'))
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Toggle::make('is_active')
                                    ->label(__('erp.ledger.is_active'))
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('erp.ledger.account_code'))
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('name')
                    ->label(__('erp.ledger.account_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('erp.ledger.account_type'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => (string) __('erp.ledger.account_types.' . $state, [], null) ?: $state)
                    ->color(fn(string $state): string => match ($state) {
                        'asset' => 'info',
                        'liability' => 'warning',
                        'equity' => 'success',
                        'revenue' => 'primary',
                        'expense' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('normal_balance')
                    ->label(__('erp.ledger.normal_balance'))
                    ->formatStateUsing(fn(string $state): string => (string) __('erp.ledger.normal_balances.' . $state, [], null) ?: $state),
                TextColumn::make('parent.name')
                    ->label(__('erp.ledger.parent_account'))
                    ->placeholder(__('erp.ledger.no_parent'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('erp.ledger.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('erp.ledger.account_type'))
                    ->options(trans('erp.ledger.account_types')),
            ])
            ->defaultSort('code')
            ->recordAction('edit')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLedgerAccounts::route('/'),
            'create' => CreateLedgerAccount::route('/create'),
            'edit'   => EditLedgerAccount::route('/{record}/edit'),
        ];
    }
}
