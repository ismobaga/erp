<?php

namespace Crommix\POS\Filament\Resources\PosSessions\Pages;

use Crommix\POS\Filament\Resources\PosSessions\PosSessionResource;
use Crommix\POS\Models\PosSession;
use Crommix\POS\Services\PosService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPosSessions extends ListRecords
{
    protected static string $resource = PosSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openSession')
                ->label('Ouvrir une session')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(fn (): bool => ! PosSession::query()->open()->exists())
                ->modalHeading('Ouvrir une session de caisse')
                ->schema([
                    TextInput::make('opening_float')
                        ->label('Fonds d’ouverture')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->minValue(0),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2),
                ])
                ->action(function (array $data, PosService $posService): void {
                    $posService->openSession((float) $data['opening_float'], $data['notes'] ?? null);

                    Notification::make()
                        ->title('Session de caisse ouverte.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
