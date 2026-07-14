<?php

namespace Crommix\Inventory\Filament\Resources\StockMovements;

use BackedEnum;
use Crommix\Inventory\Filament\Resources\StockMovements\Pages\ListStockMovements;
use Crommix\Inventory\Models\StockMovement;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only ledger of stock movements. Adjustments are made via the
 * "Adjust stock" action on the list page, which goes through
 * InventoryService so quantity_before/after stay consistent — movements
 * are never edited or deleted directly.
 */
class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Movements';

    protected static ?int $navigationSort = 3;

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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('product.name')->label('Product')->searchable()->sortable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->placeholder('—')->toggleable(),
                TextColumn::make('type')->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('quantity')->label('Qty')
                    ->formatStateUsing(fn ($state): string => ($state > 0 ? '+' : '').$state),
                TextColumn::make('quantity_before')->label('Before')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('quantity_after')->label('After'),
                TextColumn::make('reference_type')->label('Source')
                    ->formatStateUsing(fn (?string $state): string => $state ? str_replace('_', ' ', $state) : 'manual')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('creator.name')->label('By')->toggleable(),
                TextColumn::make('notes')->label('Notes')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                        'adjustment' => 'Adjustment',
                    ]),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
