<?php

namespace Crommix\Inventory\Filament\Resources\Warehouses;

use BackedEnum;
use Crommix\Inventory\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use Crommix\Inventory\Filament\Resources\Warehouses\Pages\EditWarehouse;
use Crommix\Inventory\Filament\Resources\Warehouses\Pages\ListWarehouses;
use Crommix\Inventory\Models\Warehouse;
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

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Warehouses';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static function isModuleEnabled(): bool
    {
        return (bool) config('crommix_modules.inventory', config('inventory.enabled', true));
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
                ->label('Name')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->label('Code')
                ->maxLength(50),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
            Textarea::make('address')
                ->label('Address')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('code')->label('Code')->searchable()->toggleable(),
                TextColumn::make('stock_movements_count')
                    ->label('Movements')
                    ->counts('stockMovements')
                    ->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Warehouse $record): bool => ! $record->stockMovements()->exists()),
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
            'index'  => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit'   => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
