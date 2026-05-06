<?php

namespace Crommix\Inventory\Services;

use Crommix\Inventory\Models\Product;
use Crommix\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Adjust the stock level of a product and record the movement.
     */
    public function adjustStock(
        Product $product,
        int $quantity,
        string $type = 'adjustment',
        ?int $warehouseId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        return DB::transaction(function () use (
            $product,
            $quantity,
            $type,
            $warehouseId,
            $notes,
            $referenceType,
            $referenceId,
        ): StockMovement {
            $quantityBefore = $product->stock_quantity;
            $quantityAfter  = $quantityBefore + $quantity;

            $product->update(['stock_quantity' => $quantityAfter]);

            return StockMovement::create([
                'company_id'     => $product->company_id,
                'product_id'     => $product->id,
                'warehouse_id'   => $warehouseId,
                'type'           => $type,
                'quantity'       => $quantity,
                'quantity_before'=> $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
                'created_by'     => Auth::id(),
            ]);
        });
    }

    /**
     * Return all products below their minimum stock level.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function lowStockProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::active()->lowStock()->get();
    }
}
