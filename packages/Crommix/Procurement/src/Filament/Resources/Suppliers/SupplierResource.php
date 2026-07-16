<?php

namespace Crommix\Procurement\Filament\Resources\Suppliers;

use BackedEnum;
use Crommix\Procurement\Filament\Resources\Suppliers\Pages\CreateSupplier;
use Crommix\Procurement\Filament\Resources\Suppliers\Pages\EditSupplier;
use Crommix\Procurement\Filament\Resources\Suppliers\Pages\ListSuppliers;
use Crommix\Procurement\Models\Supplier;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Achats';

    protected static ?string $navigationLabel = 'Fournisseurs';

    protected static ?string $modelLabel = 'fournisseur';

    protected static ?string $pluralModelLabel = 'fournisseurs';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static function isModuleEnabled(): bool
    {
        return (bool) config('crommix_modules.procurement', config('procurement.enabled', true));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isModuleEnabled() && parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        return static::isModuleEnabled() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->label('Code')
                ->maxLength(50),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Téléphone')
                ->tel()
                ->maxLength(50),
            TextInput::make('nif')
                ->label('NIF / Identifiant fiscal')
                ->maxLength(100),
            TextInput::make('currency')
                ->label('Devise')
                ->default('FCFA')
                ->maxLength(10),
            TextInput::make('payment_terms')
                ->label('Conditions de paiement')
                ->placeholder('ex. Net 30')
                ->maxLength(255),
            Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
            Textarea::make('address')
                ->label('Adresse')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('code')->label('Code')->searchable()->toggleable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('phone')->label('Téléphone')->toggleable(),
                TextColumn::make('payment_terms')->label('Conditions')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchase_orders_count')
                    ->label('Commandes')
                    ->counts('purchaseOrders')
                    ->sortable(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Actif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Supplier $record): bool => ! $record->purchaseOrders()->exists()),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit'   => EditSupplier::route('/{record}/edit'),
        ];
    }
}
