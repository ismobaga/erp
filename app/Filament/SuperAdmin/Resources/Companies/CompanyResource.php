<?php

namespace App\Filament\SuperAdmin\Resources\Companies;

use App\Filament\SuperAdmin\Resources\Companies\Pages\CreateCompany;
use App\Filament\SuperAdmin\Resources\Companies\Pages\EditCompany;
use App\Filament\SuperAdmin\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use App\Support\ErpEdition;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Sociétés';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Identité légale')
                            ->description('Informations administratives utilisées dans les documents officiels.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom de la société')
                                    ->required(),
                                TextInput::make('legal_name')
                                    ->label('Raison sociale'),
                                TextInput::make('email')
                                    ->label('E-mail de contact')
                                    ->email(),
                                TextInput::make('phone')
                                    ->label('Téléphone')
                                    ->tel(),
                                TextInput::make('nif')
                                    ->label('NIF / Identifiant fiscal'),
                                TextInput::make('rccm')
                                    ->label('RCCM (Registre du Commerce et du Crédit Mobilier)')
                                    ->maxLength(100),
                                TextInput::make('nina')
                                    ->label('NINA (Numéro d’Identification Nationale)')
                                    ->maxLength(50),
                                Select::make('currency')
                                    ->label('Devise')
                                    ->options([
                                        'FCFA' => 'West African CFA franc (XOF)',
                                        'EUR' => 'Euro (EUR)',
                                        'USD' => 'US Dollar (USD)',
                                    ])
                                    ->default('FCFA')
                                    ->native(false)
                                    ->required(),
                                Textarea::make('address')
                                    ->label('Adresse enregistrée')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Identité visuelle')
                            ->description('Logo et slogan utilisés dans les exports et documents.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-tertiary'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->directory('company-assets')
                                    ->image()
                                    ->disk('public'),
                                TextInput::make('slogan')
                                    ->label('Slogan'),
                            ]),

                        Section::make('Édition de l\'application')
                            ->description('Sélectionnez l\'édition ERP pour cette société. Laissez vide pour hériter de la valeur serveur (' . config('erp.edition.active', 'full') . ').')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpanFull()
                            ->schema([
                                Select::make('edition')
                                    ->label('Édition ERP')
                                    ->options([
                                        'simple' => 'Simple — Modules de base uniquement',
                                        'growing' => 'Growing — Modules intermédiaires',
                                        'full' => 'Full — Tous les modules',
                                    ])
                                    ->placeholder('Défaut serveur (' . config('erp.edition.active', 'full') . ')')
                                    ->native(false)
                                    ->nullable()
                                    ->live()
                                    ->helperText('Le changement d\'édition prend effet immédiatement après sauvegarde.'),
                            ]),

                        Section::make('Modules avancés')
                            ->description('Activez les fonctionnalités optionnelles pour cette société. Les modules désactivés sont masqués et bloqués en accès direct.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpanFull()
                            ->columns(['lg' => 2])
                            ->hidden(function (Get $get): bool {
                                $selected = $get('edition');
                                if (blank($selected)) {
                                    return ErpEdition::isSimple();
                                }

                                return $selected === 'simple';
                            })
                            ->schema([
                                Placeholder::make('basic_mode_note')
                                    ->label('Toujours disponibles')
                                    ->content('Tableau de bord, clients, services, projets, factures, paiements, dépenses, rapports de base et paramètres.'),
                                Placeholder::make('visibility_note')
                                    ->label('Visibilité')
                                    ->content('Les modules désactivés sont retirés de la navigation et bloqués en accès direct pour cette société uniquement.'),
                                Toggle::make('advanced_options.quotes')
                                    ->label('Devis')
                                    ->default(false),
                                Toggle::make('advanced_options.credit_notes')
                                    ->label('Avoirs')
                                    ->default(false),
                                Toggle::make('advanced_options.recurring_invoices')
                                    ->label('Factures récurrentes')
                                    ->default(false),
                                Toggle::make('advanced_options.general_ledger')
                                    ->label('Grand livre / comptabilité')
                                    ->default(false),
                                Toggle::make('advanced_options.financial_periods')
                                    ->label('Périodes comptables')
                                    ->default(false),
                                Toggle::make('advanced_options.documents')
                                    ->label('Gestion documentaire')
                                    ->default(false),
                                Toggle::make('advanced_options.advanced_reports')
                                    ->label('Rapports avancés')
                                    ->default(false),
                                Toggle::make('advanced_options.advanced_tax_settings')
                                    ->label('Paramètres fiscaux avancés')
                                    ->default(false),
                                Toggle::make('advanced_options.multi_currency')
                                    ->label('Multi-devise')
                                    ->default(false),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Société')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('currency')
                    ->label('Devise')
                    ->badge(),
                TextColumn::make('edition')
                    ->label('Édition')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'simple' => 'gray',
                        'growing' => 'warning',
                        'full' => 'success',
                        default => 'info',
                    })
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'simple' => 'Simple',
                        'growing' => 'Growing',
                        'full' => 'Full',
                        default => 'Serveur',
                    }),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
