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
        return 'Staff Onboarding & Security';
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
                ->label('Invite staff')
                ->schema([
                    TextInput::make('name')->label('Full name')->required(),
                    TextInput::make('email')->label('Email address')->email()->required(),
                    Select::make('role')->options([
                        'financial_auditor' => 'Financial Auditor',
                        'ledger_controller' => 'Ledger Controller',
                        'regional_manager' => 'Regional Manager',
                        'staff_coordinator' => 'Staff Coordinator',
                    ])->required()->native(false),
                    Select::make('region')->options([
                        'bamako' => 'Bamako HQ',
                        'kayes' => 'Kayes Regional',
                        'mopti' => 'Mopti Regional',
                        'global_treasury' => 'Global Treasury',
                    ])->required()->native(false),
                ])
                ->action(function (array $data): void {
                    Notification::make()
                        ->title('Invitation prepared for ' . $data['name'] . ' at ' . $data['email'] . '.')
                        ->success()
                        ->send();
                }),
            Action::make('report')
                ->label('Export security report')
                ->action(fn() => Notification::make()->title('Security access report queued for export.')->success()->send()),
            CreateAction::make()->label('Add collaborator'),
        ];
    }
}
