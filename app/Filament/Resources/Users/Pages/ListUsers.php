<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\SecurityAccessOverview;
use App\Filament\Resources\Users\Widgets\StaffDirectoryStats;
use App\Mail\StaffInviteMail;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\ReportExportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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
        /** Role label -> Spatie role name mapping */
        $roleMap = [
            'financial_auditor' => 'Finance',
            'ledger_controller' => 'Finance',
            'regional_manager' => 'Project Manager',
            'staff_coordinator' => 'Staff',
        ];

        $roleLabels = [
            'financial_auditor' => 'Auditeur financier',
            'ledger_controller' => 'Contrôleur comptable',
            'regional_manager' => 'Responsable régional',
            'staff_coordinator' => 'Coordinateur du personnel',
        ];

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
                ->action(function (array $data) use ($roleMap, $roleLabels): void {
                    if (User::query()->where('email', $data['email'])->exists()) {
                        Notification::make()
                            ->title('Adresse e-mail déjà utilisée.')
                            ->body('Un compte avec cette adresse existe déjà dans le système.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $temporaryPassword = Str::password(12, symbols: false);

                    /** @var \App\Models\User $user */
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($temporaryPassword),
                        'status' => 'active',
                    ]);

                    $spatieRoleName = $roleMap[$data['role']] ?? 'Staff';
                    $user->assignRole(Role::findByName($spatieRoleName, 'web'));

                    Mail::to($user->email)->queue(new StaffInviteMail(
                        $user,
                        $temporaryPassword,
                        $roleLabels[$data['role']] ?? $spatieRoleName,
                    ));

                    app(AuditTrailService::class)->log('staff_invited', $user, [
                        'role' => $data['role'],
                        'spatie_role' => $spatieRoleName,
                        'region' => $data['region'],
                        'invited_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Invitation envoyée')
                        ->body($data['name'] . ' a été créé(e) et une invitation a été envoyée à ' . $data['email'] . '.')
                        ->success()
                        ->send();
                }),

            Action::make('report')
                ->label('Exporter le rapport de sécurité')
                ->action(function (): void {
                    $userId = auth()->id();

                    $report = app(ReportExportService::class)->generate(
                        now()->subDays(30)->startOfDay(),
                        now()->endOfDay(),
                        ['audit' => true, 'revenue' => false, 'expenses' => false, 'payments' => false, 'taxes' => false],
                        'csv',
                        false,
                        $userId,
                    );

                    app(AuditTrailService::class)->log('user_security_report_exported', null, [
                        'path' => $report['path'],
                        'generated_at' => $report['generatedAt'],
                        'exported_by' => $userId,
                    ]);

                    $this->redirect($report['downloadUrl'], navigate: false);
                }),

            CreateAction::make()->label('Ajouter un collaborateur'),
        ];
    }
}
