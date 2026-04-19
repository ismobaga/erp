<?php

namespace App\Filament\Resources\CompanySettings;

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
    protected static ?string $model = CompanySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Company Settings';

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function canCreate(): bool
    {
        return CompanySetting::query()->doesntExist();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['lg' => 12])
                    ->schema([
                        Section::make('Legal identity')
                            ->description('Manage the core organizational profile used across invoices, quotes, and compliance exports.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('company_name')
                                    ->label('Company name')
                                    ->required(),
                                TextInput::make('legal_name')
                                    ->label('Legal name'),
                                TextInput::make('tax_number')
                                    ->label('Tax ID / NIF'),
                                TextInput::make('website')
                                    ->label('Website'),
                                Textarea::make('address')
                                    ->label('Registered address')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Brand visuals')
                            ->description('Maintain the current visual identity used in administrative documents.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-tertiary'])
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                FileUpload::make('logo_path')
                                    ->label('Company logo')
                                    ->directory('company-assets')
                                    ->image()
                                    ->disk('public'),
                                TextInput::make('slogan')
                                    ->label('Slogan'),
                            ]),
                        Section::make('Communications')
                            ->description('Set the default contact channels for customers and internal finance teams.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-secondary'])
                            ->columnSpan(['lg' => 8])
                            ->columns(['lg' => 2])
                            ->schema([
                                TextInput::make('email')
                                    ->label('Contact email')
                                    ->email(),
                                TextInput::make('phone')
                                    ->label('Billing support phone')
                                    ->tel(),
                                TextInput::make('city'),
                                TextInput::make('country'),
                            ]),
                        Section::make('Financial defaults')
                            ->description('Regional and invoicing defaults for the active ledger instance.')
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
                                    ->label('Configuration state')
                                    ->content('Administrative profile ready for document generation.'),
                            ]),
                        Section::make('Template defaults')
                            ->description('Standard copy inserted into invoices and quote documents.')
                            ->extraAttributes(['class' => 'ledger-pillar ledger-pillar-primary'])
                            ->columnSpanFull()
                            ->columns(['lg' => 2])
                            ->schema([
                                Textarea::make('invoice_default_notes')
                                    ->label('Invoice notes')
                                    ->rows(5),
                                Textarea::make('quote_default_notes')
                                    ->label('Quote notes')
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
                    ->label('Organization')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('currency')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
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
