<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProjectDetails;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\Client;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
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

class ProjectResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'projects';

    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'Opérations';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Projets';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Cadre du projet')
                            ->description('Centralisez le pilotage, l’affectation et le suivi d’exécution.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom du projet')
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('client_id')
                                    ->label('Client')
                                    ->relationship('client', 'company_name')
                                    ->getOptionLabelFromRecordUsing(fn(Client $record): string => $record->company_name ?: $record->contact_name ?: ('Client #' . $record->getKey()))
                                    ->searchable(['company_name', 'contact_name', 'email'])
                                    ->preload(),
                                Select::make('service_id')
                                    ->label('Service')
                                    ->relationship('service', 'name')
                                    ->getOptionLabelFromRecordUsing(fn(Service $record): string => $record->name)
                                    ->searchable()
                                    ->preload(),
                                Select::make('assigned_to')
                                    ->label('Responsable')
                                    ->relationship('assignee', 'name')
                                    ->getOptionLabelFromRecordUsing(fn(User $record): string => $record->name)
                                    ->searchable()
                                    ->preload(),
                                Select::make('status')
                                    ->label(__('erp.common.status'))
                                    ->options(trans('erp.resources.project.statuses'))
                                    ->default('planned')
                                    ->native(false)
                                    ->required(),
                                Select::make('approval_status')
                                    ->label(__('erp.common.validation'))
                                    ->options(trans('erp.resources.project.approval_statuses'))
                                    ->default('pending')
                                    ->native(false)
                                    ->required(),
                                DatePicker::make('start_date')
                                    ->label('Début'),
                                DatePicker::make('due_date')
                                    ->label('Échéance'),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Gouvernance')
                            ->description('Décisions, points de contrôle et remarques managériales.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Placeholder::make('workflow_note')
                                    ->label(__('erp.common.notes'))
                                    ->content(__('erp.resources.project.workflow_note')),
                                Textarea::make('approval_notes')
                                    ->label('Notes de validation')
                                    ->rows(5),
                                Textarea::make('notes')
                                    ->label('Notes internes')
                                    ->rows(4),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn(Project $record): string => static::getUrl('details', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('erp.common.project'))
                    ->description(fn(Project $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: __('erp.resources.project.no_client'))
                    ->searchable(['name'])
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('erp.common.status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'info',
                        'on_hold' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => __('erp.resources.project.statuses.' . $state)),
                TextColumn::make('approval_status')
                    ->label(__('erp.common.validation'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => __('erp.resources.project.approval_statuses.' . $state)),
                TextColumn::make('assignee.name')
                    ->label('Responsable')
                    ->placeholder(__('erp.common.not_assigned')),
                TextColumn::make('due_date')
                    ->label(__('erp.common.due_date'))
                    ->date()
                    ->placeholder(__('erp.common.to_plan'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('erp.common.updated_at'))
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('erp.common.status'))
                    ->options(trans('erp.resources.project.statuses')),
                SelectFilter::make('approval_status')
                    ->label(__('erp.common.validation'))
                    ->options(trans('erp.resources.project.approval_statuses')),
            ])
            ->recordActions([
                Action::make('details')
                    ->label(__('erp.actions.details'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn(Project $record): string => static::getUrl('details', ['record' => $record])),
                Action::make('approve')
                    ->label(__('erp.actions.approve'))
                    ->color('success')
                    ->visible(fn(Project $record): bool => $record->approval_status !== 'approved' && (auth()->user()?->can('projects.update') ?? false))
                    ->action(function (Project $record): void {
                        $record->approve(auth()->user(), 'Projet approuvé pour lancement.');

                        Notification::make()
                            ->title(__('erp.resources.project.approved_notification'))
                            ->success()
                            ->send();
                    }),
                Action::make('start')
                    ->label(__('erp.actions.start'))
                    ->color('info')
                    ->visible(fn(Project $record): bool => in_array($record->status, ['planned', 'on_hold'], true) && $record->approval_status !== 'rejected' && (auth()->user()?->can('projects.update') ?? false))
                    ->action(function (Project $record): void {
                        if ($record->approval_status === 'pending') {
                            $record->approve(auth()->user(), 'Validation automatique au démarrage.');
                        }

                        $record->markInProgress();

                        Notification::make()
                            ->title(__('erp.resources.project.started_notification'))
                            ->success()
                            ->send();
                    }),
                Action::make('complete')
                    ->label(__('erp.actions.complete'))
                    ->color('primary')
                    ->visible(fn(Project $record): bool => $record->status !== 'completed' && (auth()->user()?->can('projects.update') ?? false))
                    ->action(function (Project $record): void {
                        $record->markCompleted();

                        Notification::make()
                            ->title(__('erp.resources.project.completed_notification'))
                            ->success()
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

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'details' => ViewProjectDetails::route('/{record}/details'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
