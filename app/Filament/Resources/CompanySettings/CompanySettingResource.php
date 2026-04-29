<?php

namespace App\Filament\Resources\CompanySettings;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\CompanySettings\Pages\CreateCompanySetting;
use App\Filament\Resources\CompanySettings\Pages\EditCompanySetting;
use App\Filament\Resources\CompanySettings\Pages\ListCompanySettings;
use App\Models\CompanySetting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanySettingResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'settings';

    protected static ?string $model = CompanySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Paramètres société';

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function canCreate(): bool
    {
        return static::canAccessPermission('update') && CompanySetting::query()->doesntExist();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Identité légale')
                            ->description('Gérez le profil principal utilisé dans les factures, devis et exports administratifs.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('company_name')
                                    ->label('Nom de l’entreprise')
                                    ->required(),
                                TextInput::make('legal_name')
                                    ->label('Raison sociale'),
                                TextInput::make('tax_number')
                                    ->label('NIF / Identifiant fiscal'),
                                TextInput::make('website')
                                    ->label('Site web'),
                                Textarea::make('address')
                                    ->label('Adresse enregistrée')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Identité visuelle')
                            ->description('Maintenez la charte visuelle utilisée dans les documents administratifs.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-tertiary'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                FileUpload::make('logo_path')
                                    ->label('Logo de l’entreprise')
                                    ->directory('company-assets')
                                    ->image()
                                    ->disk('public'),
                                TextInput::make('slogan')
                                    ->label('Slogan'),
                            ]),
                        Section::make('Communications')
                            ->description('Définissez les canaux de contact par défaut pour les clients et l’équipe finance.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('email')
                                    ->label('E-mail de contact')
                                    ->email(),
                                TextInput::make('phone')
                                    ->label('Téléphone support facturation')
                                    ->tel(),
                                TextInput::make('city'),
                                TextInput::make('country'),
                            ]),
                        Section::make('Paramètres financiers')
                            ->description('Valeurs par défaut pour la facturation et la comptabilité courante.')
                            ->extraAttributes(['class' => 'ledger-summary-card'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Select::make('currency')
                                    ->options([
                                        'FCFA' => 'West African CFA franc (XOF)',
                                        'EUR' => 'Euro (EUR)',
                                        'USD' => 'US Dollar (USD)',
                                    ])
                                    ->default('FCFA')
                                    ->native(false),
                                Placeholder::make('configuration_state')
                                    ->label('État de configuration')
                                    ->content('Le profil administratif est prêt pour la génération des documents.'),
                            ]),
                        Section::make('Coordonnées bancaires')
                            ->description('Informations figurant sur les factures émises. Laissez vide pour masquer la section.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpanFull()
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('bank_name')
                                    ->label('Nom de la banque'),
                                TextInput::make('bank_account_name')
                                    ->label('Titulaire du compte'),
                                TextInput::make('bank_account_number')
                                    ->label('Numéro de compte / IBAN'),
                                TextInput::make('bank_swift_code')
                                    ->label('Code SWIFT / BIC'),
                            ]),
                        Section::make('Notes par défaut')
                            ->description('Texte standard ajouté aux factures et aux devis.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpanFull()
                            ->columns(['lg' => 2])
                            ->schema([
                                Textarea::make('invoice_default_notes')
                                    ->label('Notes de facture')
                                    ->rows(5),
                                Textarea::make('quote_default_notes')
                                    ->label('Notes de devis')
                                    ->rows(5),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Organisation')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('currency')
                    ->badge(),
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
            'index' => ListCompanySettings::route('/'),
            'create' => CreateCompanySetting::route('/create'),
            'edit' => EditCompanySetting::route('/{record}/edit'),
        ];
    }
}
