<?php

namespace Crommix\Procurement\Services;

use Crommix\Procurement\Models\PurchaseOrder;
use Crommix\Procurement\Models\PurchaseOrderItem;

class ProcurementService
{
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
}
