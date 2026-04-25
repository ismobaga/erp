<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClientDetails;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'clients';

    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Informations générales')
                            ->description('Enregistrez un nouveau client ou une nouvelle entité dans le référentiel central.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-tertiary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                Radio::make('type')
                                    ->label('Type de client')
                                    ->options([
                                        'company' => 'Entreprise',
                                        'individual' => 'Particulier',
                                    ])
                                    ->default('company')
                                    ->inline()
                                    ->live()
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('status')
                                    ->options([
                                        'lead' => 'Prospect',
                                        'active' => 'Actif',
                                        'customer' => 'Client',
                                        'inactive' => 'Inactif',
                                    ])
                                    ->default('lead')
                                    ->native(false)
                                    ->required(),
                                TextInput::make('company_name')
                                    ->label(fn(Get $get): string => $get('type') === 'individual' ? 'Nom du client' : 'Nom de l’entreprise')
                                    ->placeholder('Acme Architecture Ltd.')
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('contact_name')
                                    ->label('Contact principal')
                                    ->placeholder('Nom complet')
                                    ->required(),
                                TextInput::make('email')
                                    ->label('Adresse e-mail')
                                    ->email()
                                    ->placeholder('contact@company.com'),
                                TextInput::make('phone')
                                    ->tel()
                                    ->placeholder('+223 00 00 00 00')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Notes internes')
                            ->description('Notes confidentielles pour l’équipe en charge du compte.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Textarea::make('notes')
                                    ->rows(8)
                                    ->placeholder('Document specific requirements, historical context, or preferred communication channels...'),
                                Placeholder::make('registry_state')
                                    ->label('État du dossier')
                                    ->content('Brouillon · Prêt pour vérification'),
                            ]),
                        Section::make('Localisation et logistique')
                            ->description('Conservez l’adresse opérationnelle et les informations de contact.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('address')
                                    ->label('Adresse physique')
                                    ->placeholder('Adresse')
                                    ->columnSpanFull(),
                                TextInput::make('city')
                                    ->label('Région / ville fiscale')
                                    ->placeholder('Dakar')
                                    ->helperText('Utilisée pour résoudre les profils fiscaux régionaux quand ils existent.'),
                                Select::make('country')
                                    ->options([
                                        'Mali' => 'Mali',
                                        'Senegal' => 'Senegal',
                                        'Ghana' => 'Ghana',
                                        'France' => 'France',
                                        'United Arab Emirates' => 'United Arab Emirates',
                                    ])
                                    ->helperText('Le pays détermine le profil de TVA ou de taxe par défaut pour ce client.')
                                    ->searchable()
                                    ->native(false),
                                Placeholder::make('tax_profile_preview')
                                    ->label('Profil fiscal appliqué')
                                    ->content(function (Get $get): string {
                                        $profiles = (array) config('erp.tax_profiles.countries', []);
                                        $country = (string) ($get('country') ?? '');
                                        $region = (string) ($get('city') ?? '');
                                        $countryProfile = $profiles[$country] ?? [];
                                        $regionProfile = ($countryProfile['regions'][$region] ?? []);
                                        $profile = array_merge((array) config('erp.tax_profiles.default', []), $countryProfile, $regionProfile);

                                        if (blank($country) && blank($region)) {
                                            return 'Aucun profil fiscal spécifique sélectionné.';
                                        }

                                        $label = $profile['label'] ?? 'Profil standard';
                                        $rate = number_format((float) ($profile['rate'] ?? 0), 2, ',', ' ');

                                        return $label . ' · ' . $rate . ' %';
                                    })
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Résumé du dossier')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Placeholder::make('draft_mode')
                                    ->label('Mode du dossier')
                                    ->content('Brouillon'),
                                Placeholder::make('verification_hint')
                                    ->label('Vérification')
                                    ->content('Prêt pour revue et validation.'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn(Client $client): string => static::getUrl('details', ['record' => $client]))
            ->columns([
                TextColumn::make('identity')
                    ->label('Client')
                    ->state(fn(Client $record): string => $record->company_name ?: $record->contact_name ?: 'Client sans nom')
                    ->searchable(['company_name', 'contact_name', 'email']),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Mis à jour'),
            ])
            ->recordActions([
                Action::make('details')
                    ->label(__('erp.actions.details'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn(Client $client): string => static::getUrl('details', ['record' => $client])),
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
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'details' => ViewClientDetails::route('/{record}/details'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
