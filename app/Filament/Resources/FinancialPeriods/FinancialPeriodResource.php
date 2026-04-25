<?php

namespace App\Filament\Resources\FinancialPeriods;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\FinancialPeriods\Pages\CreateFinancialPeriod;
use App\Filament\Resources\FinancialPeriods\Pages\EditFinancialPeriod;
use App\Filament\Resources\FinancialPeriods\Pages\ListFinancialPeriods;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\FinancialPeriod;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class FinancialPeriodResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'financial_periods';

    protected static ?string $model = FinancialPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Périodes comptables';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Définition de la période')
                            ->description('Définissez la fenêtre comptable et son état de verrouillage.')
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom')
                                    ->placeholder('Avril 2026')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->label('Code')
                                    ->placeholder('2026-04')
                                    ->default(fn(): string => static::generatePeriodCode())
                                    ->helperText('Code généré automatiquement selon la date de début, modifiable si nécessaire.')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(100),
                                DatePicker::make('starts_on')
                                    ->label('Début')
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        $set('code', static::generatePeriodCode($state));
                                    })
                                    ->required(),
                                DatePicker::make('ends_on')
                                    ->label('Fin')
                                    ->native(false)
                                    ->required()
                                    ->afterOrEqual('starts_on'),
                            ]),
                        Section::make('Contrôles')
                            ->description('Gardez la maîtrise des ouvertures et des clôtures de période.')
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'open' => 'Ouverte',
                                        'closed' => 'Clôturée',
                                    ])
                                    ->default('open')
                                    ->native(false)
                                    ->required(),
                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(5)
                                    ->placeholder('Commentaires d’audit, justification de clôture, ajustements...'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Période')
                    ->description(fn(FinancialPeriod $record): string => $record->code)
                    ->searchable(['name', 'code'])
                    ->sortable(),
                TextColumn::make('range')
                    ->label('Plage')
                    ->state(fn(FinancialPeriod $record): string => sprintf(
                        '%s → %s',
                        \Illuminate\Support\Carbon::parse($record->starts_on)->format('d/m/Y'),
                        \Illuminate\Support\Carbon::parse($record->ends_on)->format('d/m/Y'),
                    ))
                    ->sortable(false),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => $state === 'closed' ? 'Clôturée' : 'Ouverte')
                    ->color(fn(string $state): string => $state === 'closed' ? 'danger' : 'success'),
                TextColumn::make('closed_at')
                    ->label('Clôturée le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->defaultSort('starts_on', 'desc')
            ->recordActions([
                Action::make('close')
                    ->label('Clôturer')
                    ->visible(fn(FinancialPeriod $record): bool => $record->isOpen() && (auth()->user()?->can('financial_periods.update') ?? false))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (FinancialPeriod $record): void {
                        $record->close(auth()->id(), 'Clôture initiée depuis le panneau finance.');

                        Notification::make()
                            ->title('La période comptable a été clôturée.')
                            ->success()
                            ->send();
                    }),
                Action::make('reopen')
                    ->label('Rouvrir')
                    ->visible(fn(FinancialPeriod $record): bool => $record->isClosed() && (auth()->user()?->can('financial_periods.update') ?? false))
                    ->requiresConfirmation()
                    ->color('warning')
                    ->action(function (FinancialPeriod $record): void {
                        $record->reopen(auth()->id(), 'Réouverture initiée depuis le panneau finance.');

                        Notification::make()
                            ->title('La période comptable a été rouverte.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
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
            'index' => ListFinancialPeriods::route('/'),
            'create' => CreateFinancialPeriod::route('/create'),
            'edit' => EditFinancialPeriod::route('/{record}/edit'),
        ];
    }

    public static function generatePeriodCode(mixed $startsOn = null): string
    {
        $date = $startsOn ? Carbon::parse($startsOn) : now();

        return $date->format('Y-m');
    }
}
