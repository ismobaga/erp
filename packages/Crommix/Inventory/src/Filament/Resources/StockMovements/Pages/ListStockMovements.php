<?php

namespace Crommix\Inventory\Filament\Resources\StockMovements\Pages;

use Crommix\Inventory\Filament\Resources\StockMovements\StockMovementResource;
use Crommix\Inventory\Models\Product;
use Crommix\Inventory\Models\Warehouse;
use Crommix\Inventory\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('adjustStock')
                ->label('Ajuster le stock')
                ->icon('heroicon-o-adjustments-horizontal')
                ->modalHeading('Ajuster le stock')
                ->schema([
                    Select::make('product_id')
                        ->label('Produit')
                        ->options(fn (): array => Product::query()
                            ->where('track_inventory', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    Select::make('warehouse_id')
                        ->label('Entrepôt')
                        ->options(fn (): array => Warehouse::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->placeholder('—'),
                    TextInput::make('quantity')
                        ->label('Quantité (+ entrée / − sortie)')
                        ->numeric()
                        ->required()
                        ->rules(['integer', 'not_in:0']),
                    Textarea::make('notes')
                        ->label('Motif / notes')
                        ->rows(2)
                        ->required(),
                ])
                ->action(function (array $data, InventoryService $inventoryService): void {
                    $product = Product::query()->findOrFail($data['product_id']);
                    $quantity = (int) $data['quantity'];

                    if ($quantity < 0 && $product->stock_quantity + $quantity < 0) {
                        Notification::make()
                            ->title('Stock insuffisant.')
                            ->body("Le stock actuel de {$product->name} est de {$product->stock_quantity}.")
                            ->danger()
                            ->send();

                        return;
                    }

                    $inventoryService->adjustStock(
                        $product,
                        $quantity,
                        'adjustment',
                        $data['warehouse_id'] ? (int) $data['warehouse_id'] : null,
                        (string) $data['notes'],
                    );

                    Notification::make()
                        ->title('Stock ajusté.')
                        ->body("{$product->name} : ".($quantity > 0 ? '+' : '')."{$quantity} → ".$product->fresh()->stock_quantity)
                        ->success()
                        ->send();
                }),
        ];
    }
}
