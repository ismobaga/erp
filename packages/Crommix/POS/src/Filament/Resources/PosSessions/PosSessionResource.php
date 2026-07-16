<?php

namespace Crommix\POS\Filament\Resources\PosSessions;

use BackedEnum;
use Crommix\POS\Filament\Resources\PosSessions\Pages\ListPosSessions;
use Crommix\POS\Models\PosSession;
use Crommix\POS\Services\PosService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Till sessions. Opened and closed exclusively through actions that go via
 * PosService, so totals are always computed from the session's orders —
 * sessions are never created or edited as plain records.
 */
class PosSessionResource extends Resource
{
    protected static ?string $model = PosSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Point de vente';

    protected static ?string $navigationLabel = 'Sessions de caisse';

    protected static ?string $modelLabel = 'session de caisse';

    protected static ?string $pluralModelLabel = 'sessions de caisse';

    protected static ?int $navigationSort = 2;

    protected static function isModuleEnabled(): bool
    {
        return (bool) config('crommix_modules.pos', config('pos.enabled', true));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isModuleEnabled() && parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        return static::isModuleEnabled() && parent::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opened_at')->label('Ouverte le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('opener.name')->label('Ouverte par'),
                TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'open' ? 'Ouverte' : 'Fermée')
                    ->color(fn (string $state): string => $state === 'open' ? 'success' : 'gray'),
                TextColumn::make('opening_float')->label('Fonds d’ouverture')->numeric(decimalPlaces: 0),
                TextColumn::make('orders_count')->label('Ventes')->counts('orders'),
                TextColumn::make('total_sales')->label('Total ventes')->numeric(decimalPlaces: 0)->placeholder('—'),
                TextColumn::make('closing_float')->label('Fonds de clôture')->numeric(decimalPlaces: 0)->placeholder('—'),
                TextColumn::make('closed_at')->label('Fermée le')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('closer.name')->label('Fermée par')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Ouverte',
                        'closed' => 'Fermée',
                    ]),
            ])
            ->recordActions([
                Action::make('closeSession')
                    ->label('Fermer')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (PosSession $record): bool => $record->status === 'open')
                    ->requiresConfirmation()
                    ->modalHeading('Fermer cette session de caisse ?')
                    ->modalDescription('Le total des ventes sera calculé à partir des commandes finalisées de la session.')
                    ->schema([
                        TextInput::make('closing_float')
                            ->label('Fonds de clôture compté')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ])
                    ->action(function (PosSession $record, array $data, PosService $posService): void {
                        $session = $posService->closeSession($record, (float) $data['closing_float']);

                        $expected = (float) $session->opening_float + (float) $session->total_sales;
                        $difference = (float) $data['closing_float'] - $expected;

                        $notification = Notification::make()
                            ->title('Session fermée.')
                            ->body(sprintf(
                                'Ventes : %s — fonds attendu : %s — compté : %s — écart : %s',
                                number_format((float) $session->total_sales),
                                number_format($expected),
                                number_format((float) $data['closing_float']),
                                number_format($difference),
                            ));

                        (abs($difference) < 0.01 ? $notification->success() : $notification->warning())->send();
                    }),
            ])
            ->defaultSort('opened_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosSessions::route('/'),
        ];
    }
}
