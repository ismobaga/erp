<?php

namespace Crommix\Procurement\Services;

use Crommix\Inventory\Models\Product;
use Crommix\Inventory\Services\InventoryService;
use Crommix\Procurement\Models\PurchaseOrder;
use Crommix\Procurement\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcurementService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    /**
     * Create a purchase order with line items.
     *
     * @param array<string, mixed>          $orderData
     * @param array<int, array<string, mixed>> $items
     */
    public function createOrder(array $orderData, array $items = []): PurchaseOrder
    {
        $order = PurchaseOrder::create($orderData);

        foreach ($items as $item) {
            $item['total_price'] = $item['quantity'] * $item['unit_price'];
            $order->items()->create($item);
        }

        $this->recalculate($order);

        return $order->refresh();
    }

    /**
     * Approve a purchase order.
     */
    public function approve(PurchaseOrder $order, int $userId): PurchaseOrder
    {
        $order->update([
            'status'      => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $order->refresh();
    }

    /**
     * Recalculate the total amount from items.
     */
    public function recalculate(PurchaseOrder $order): void
    {
        $total = $order->items()->sum('total_price');
        $order->update(['total_amount' => $total]);
    }

    /**
     * Receive a purchase order: post the outstanding quantity of every
     * product-linked item into inventory, mark quantities received, and flip
     * the order status. Items without a product_id (services, fees…) are
     * marked received without a stock movement.
     */
    public function receive(PurchaseOrder $order, ?int $warehouseId = null, ?string $notes = null): PurchaseOrder
    {
        if (! in_array($order->status, ['submitted', 'approved'], true)) {
            throw new RuntimeException(
                "Only submitted or approved purchase orders can be received (current status: {$order->status}).",
            );
        }

        return DB::transaction(function () use ($order, $warehouseId, $notes): PurchaseOrder {
            foreach ($order->items as $item) {
                $outstanding = (float) $item->quantity - (float) $item->quantity_received;

                if ($outstanding <= 0) {
                    continue;
                }

                if ($item->product_id !== null) {
                    $product = Product::query()->find($item->product_id);

                    if ($product !== null && $product->track_inventory) {
                        $this->inventoryService->adjustStock(
                            $product,
                            (int) $outstanding,
                            'in',
                            $warehouseId,
                            $notes ?: "PO {$order->reference} received",
                            'purchase_order',
                            $order->id,
                        );
                    }
                }

                $item->update(['quantity_received' => $item->quantity]);
            }

            $order->update(['status' => 'received']);

            return $order->refresh();
        });
    }
}
