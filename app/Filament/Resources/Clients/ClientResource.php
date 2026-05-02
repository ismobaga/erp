<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClientDetails;
use App\Filament\Resources\RelationManagers\NotesRelationManager;
use App\Models\Client;
use App\Services\TaxProfileResolver;
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
                                    ->options(static::countryOptions())
                                    ->helperText('Le pays détermine le profil de TVA ou de taxe par défaut pour ce client.')
                                    ->searchable()
                                    ->native(false),
                                Placeholder::make('tax_profile_preview')
                                    ->label('Profil fiscal appliqué')
                                    ->content(function (Get $get): string {
                                        $country = (string) ($get('country') ?? '');
                                        $city    = (string) ($get('city') ?? '');

                                        if (blank($country) && blank($city)) {
                                            return 'Aucun profil fiscal spécifique sélectionné.';
                                        }

                                        $fakeClient = new Client(['country' => $country, 'city' => $city]);
                                        $profile    = app(TaxProfileResolver::class)->resolveForClient($fakeClient);

                                        $label = $profile['label'] ?? 'Profil standard';
                                        $rate  = number_format((float) ($profile['rate'] ?? 0), 2, ',', ' ');

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
            ->defaultSort('updated_at', 'desc')
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
                Action::make('sendWhatsapp')
                    ->label('Message WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn(Client $record): bool => filled($record->phone) && (auth()->user()?->can('clients.view') ?? false))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Client $record, array $data, \App\Services\Whatsapp\WhatsappSendService $service): void {
                        $log = $service->sendTextToClient($record, $data['message']);
                        if ($log->status === 'sent') {
                            \Filament\Notifications\Notification::make()->title('Message envoyé via WhatsApp')->success()->send();
                        } else {
                            \Filament\Notifications\Notification::make()->title('Échec de l\'envoi WhatsApp')->body($log->error_message)->danger()->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucun client enregistré')
            ->emptyStateDescription('Ajoutez votre premier client pour commencer à créer des factures.')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
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

    /**
     * Build the country select options.
     *
     * Countries that have a configured tax profile appear first for easy
     * access; all other ISO 3166-1 common names follow in alphabetical order.
     */
    protected static function countryOptions(): array
    {
        $taxProfileCountries = array_keys((array) config('erp.tax_profiles.countries', []));

        $primaryOptions = array_combine($taxProfileCountries, $taxProfileCountries);

        $allCountries = [
            'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Argentina', 'Armenia',
            'Australia', 'Austria', 'Azerbaijan', 'Bahrain', 'Bangladesh', 'Belarus', 'Belgium',
            'Benin', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Bulgaria',
            'Burkina Faso', 'Burundi', 'Cambodia', 'Cameroon', 'Canada', 'Cape Verde',
            'Central African Republic', 'Chad', 'Chile', 'China', 'Colombia', 'Comoros',
            'Congo', 'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Denmark',
            'Djibouti', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea',
            'Eritrea', 'Estonia', 'Ethiopia', 'Finland', 'France', 'Gabon', 'Gambia', 'Georgia',
            'Germany', 'Ghana', 'Greece', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Haiti',
            'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland',
            'Israel', 'Italy', 'Ivory Coast', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya',
            'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya',
            'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali',
            'Malta', 'Mauritania', 'Mauritius', 'Mexico', 'Moldova', 'Mongolia', 'Montenegro',
            'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nepal', 'Netherlands', 'New Zealand',
            'Nicaragua', 'Niger', 'Nigeria', 'North Macedonia', 'Norway', 'Oman', 'Pakistan',
            'Panama', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania',
            'Russia', 'Rwanda', 'Saudi Arabia', 'Senegal', 'Serbia', 'Sierra Leone', 'Singapore',
            'Slovakia', 'Slovenia', 'Somalia', 'South Africa', 'South Sudan', 'Spain', 'Sri Lanka',
            'Sudan', 'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan', 'Tanzania',
            'Thailand', 'Togo', 'Tunisia', 'Turkey', 'Turkmenistan', 'Uganda', 'Ukraine',
            'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan',
            'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe',
        ];

        $secondaryOptions = collect($allCountries)
            ->reject(fn (string $c): bool => in_array($c, $taxProfileCountries, true))
            ->sort()
            ->mapWithKeys(fn (string $c): array => [$c => $c])
            ->all();

        return array_merge($primaryOptions, $secondaryOptions);
    }
}
