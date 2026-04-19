<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\SecurityAccessOverview;
use App\Filament\Resources\Users\Widgets\StaffDirectoryStats;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Intégration et sécurité du personnel';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StaffDirectoryStats::class,
            SecurityAccessOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inviteStaff')
                ->label('Inviter un membre')
                ->schema([
                    TextInput::make('name')->label('Nom complet')->required(),
                    TextInput::make('email')->label('Adresse e-mail')->email()->required(),
                    Select::make('role')->options([
                        'financial_auditor' => 'Auditeur financier',
                        'ledger_controller' => 'Contrôleur comptable',
                        'regional_manager' => 'Responsable régional',
                        'staff_coordinator' => 'Coordinateur du personnel',
                    ])->required()->native(false),
                    Select::make('region')->options([
                        'bamako' => 'Siège de Bamako',
                        'kayes' => 'Région de Kayes',
                        'mopti' => 'Région de Mopti',
                        'global_treasury' => 'Trésorerie globale',
                    ])->required()->native(false),
                ])
                ->action(function (array $data): void {
                    Notification::make()
                        ->title('Invitation préparée pour ' . $data['name'] . ' à l’adresse ' . $data['email'] . '.')
                        ->success()
                        ->send();
                }),
            Action::make('report')
                ->label('Exporter le rapport de sécurité')
                ->action(fn() => Notification::make()->title('Le rapport de sécurité a été ajouté à la file d’export.')->success()->send()),
            CreateAction::make()->label('Ajouter un collaborateur'),
        ];
    }
}
