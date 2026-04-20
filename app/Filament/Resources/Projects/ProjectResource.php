<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProjectDetails;
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
                                    ->label('Statut opérationnel')
                                    ->options([
                                        'planned' => 'Planifié',
                                        'in_progress' => 'En cours',
                                        'on_hold' => 'En pause',
                                        'completed' => 'Terminé',
                                        'cancelled' => 'Annulé',
                                    ])
                                    ->default('planned')
                                    ->native(false)
                                    ->required(),
                                Select::make('approval_status')
                                    ->label('Validation')
                                    ->options([
                                        'pending' => 'En attente',
                                        'approved' => 'Approuvé',
                                        'review' => 'À vérifier',
                                        'rejected' => 'Rejeté',
                                    ])
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
                                    ->label('Processus')
                                    ->content('Les projets approuvés peuvent démarrer immédiatement depuis la liste.'),
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
            ->recordUrl(fn(Project $record): string => static::getUrl('details', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Projet')
                    ->description(fn(Project $record): string => $record->client?->company_name ?: $record->client?->contact_name ?: 'Aucun client lié')
                    ->searchable(['name'])
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Exécution')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'info',
                        'on_hold' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('approval_status')
                    ->label('Validation')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('assignee.name')
                    ->label('Responsable')
                    ->placeholder('Non affecté'),
                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date()
                    ->placeholder('À planifier')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'planned' => 'Planifié',
                        'in_progress' => 'En cours',
                        'on_hold' => 'En pause',
                        'completed' => 'Terminé',
                        'cancelled' => 'Annulé',
                    ]),
                SelectFilter::make('approval_status')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'review' => 'À vérifier',
                        'rejected' => 'Rejeté',
                    ]),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Détails')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn(Project $record): string => static::getUrl('details', ['record' => $record])),
                Action::make('approve')
                    ->label('Approuver')
                    ->color('success')
                    ->visible(fn(Project $record): bool => $record->approval_status !== 'approved' && (auth()->user()?->can('projects.update') ?? false))
                    ->action(function (Project $record): void {
                        $record->approve(auth()->user(), 'Projet approuvé pour lancement.');

                        Notification::make()
                            ->title('Projet approuvé.')
                            ->success()
                            ->send();
                    }),
                Action::make('start')
                    ->label('Démarrer')
                    ->color('info')
                    ->visible(fn(Project $record): bool => in_array($record->status, ['planned', 'on_hold'], true) && $record->approval_status !== 'rejected' && (auth()->user()?->can('projects.update') ?? false))
                    ->action(function (Project $record): void {
                        if ($record->approval_status === 'pending') {
                            $record->approve(auth()->user(), 'Validation automatique au démarrage.');
                        }

                        $record->markInProgress();

                        Notification::make()
                            ->title('Projet lancé.')
                            ->success()
                            ->send();
                    }),
                Action::make('complete')
                    ->label('Clôturer')
                    ->color('primary')
                    ->visible(fn(Project $record): bool => $record->status !== 'completed' && (auth()->user()?->can('projects.update') ?? false))
                    ->action(function (Project $record): void {
                        $record->markCompleted();

                        Notification::make()
                            ->title('Projet marqué comme terminé.')
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
        return [];
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
