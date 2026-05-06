<?php

namespace Crommix\Inventory\Filament\Resources\Products;

use BackedEnum;
use Crommix\Inventory\Filament\Resources\Products\Pages\CreateProduct;
use Crommix\Inventory\Filament\Resources\Products\Pages\EditProduct;
use Crommix\Inventory\Filament\Resources\Products\Pages\ListProducts;
use Crommix\Inventory\Models\Product;
use Crommix\Inventory\Models\ProductCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')
                ->label('SKU')
                ->maxLength(255),
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            Select::make('category_id')
                ->label('Category')
                ->options(fn(): array => ProductCategory::query()->pluck('name', 'id')->all())
                ->searchable()
                ->preload(),
            Textarea::make('description')
                ->label('Description')
                ->rows(3),
            Select::make('unit')
                ->label('Unit')
                ->options([
                    'pcs' => 'Pieces',
                    'kg'  => 'Kilograms',
                    'l'   => 'Litres',
                    'm'   => 'Metres',
                    'box' => 'Box',
                ])
                ->native(false)
                ->default('pcs'),
            TextInput::make('cost_price')
                ->label('Cost Price')
                ->numeric()
                ->prefix('$'),
            TextInput::make('sale_price')
                ->label('Sale Price')
                ->numeric()
                ->prefix('$'),
            TextInput::make('stock_quantity')
                ->label('Stock Quantity')
                ->numeric()
                ->default(0),
            TextInput::make('min_stock_level')
                ->label('Min Stock Level')
                ->numeric()
                ->default(0),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
            Toggle::make('track_inventory')
                ->label('Track Inventory')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable()->sortable(),
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->toggleable(),
                TextColumn::make('unit')->label('Unit')->badge(),
                TextColumn::make('cost_price')->label('Cost')->money('USD')->sortable(),
                TextColumn::make('sale_price')->label('Price')->money('USD')->sortable(),
                TextColumn::make('stock_quantity')->label('Stock')->sortable(),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->filters([
                SelectFilter::make('unit')
                    ->options([
                        'pcs' => 'Pieces',
                        'kg'  => 'Kilograms',
                        'l'   => 'Litres',
                        'm'   => 'Metres',
                        'box' => 'Box',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit'   => EditProduct::route('/{record}/edit'),
        ];
    }
}
