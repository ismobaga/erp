<?php

namespace App\Filament\Resources\Services;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'services';

    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Identité du service')
                            ->description('Définissez une nouvelle prestation et son suivi dans le système ERP.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columns(['lg' => 2])
                            ->columnSpan(['lg' => 8])
                            ->schema([
                                TextInput::make('code')
                                    ->label('Code service')
                                    ->placeholder('ARC-PLAN-2024')
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label('Nom du service')
                                    ->placeholder('Structural analysis')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('category')
                                    ->placeholder('Consulting, Logistics, Audit...')
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->rows(5)
                                    ->placeholder('Provide a detailed breakdown of the service scope, deliverables, and resource allocation requirements...')
                                    ->columnSpanFull(),
                                TextInput::make('default_price')
                                    ->label('Prix de base')
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),
                            ]),
                        Section::make('État du registre')
                            ->description('Visibilité opérationnelle et état de conformité.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Actif')
                                    ->helperText('Affiche ou masque ce service dans les catalogues.')
                                    ->default(true),
                                Placeholder::make('price_preview')
                                    ->label('Aperçu du prix')
                                    ->content(fn(Get $get): string => 'FCFA ' . number_format((float) ($get('default_price') ?? 0), 2, '.', ' ')),
                                Placeholder::make('audit_notice')
                                    ->label('Note de contrôle')
                                    ->content('Toutes les prestations peuvent être soumises à vérification interne.'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('default_price')
                    ->label('Prix de base')
                    ->formatStateUsing(fn($state): string => 'FCFA ' . number_format((float) $state, 2, '.', ' '))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Mis à jour'),
            ])
            ->recordActions([
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
