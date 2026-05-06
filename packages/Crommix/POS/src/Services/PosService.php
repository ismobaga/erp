<?php

namespace Crommix\POS\Services;

use Crommix\Inventory\Services\InventoryService;
use Crommix\POS\Models\PosOrder;
use Crommix\POS\Models\PosSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    /**
     * Open a new POS session.
     */
    public function openSession(float $openingFloat = 0, ?string $notes = null): PosSession
    {
        return PosSession::create([
            'opened_by'     => Auth::id(),
            'status'        => 'open',
            'opening_float' => $openingFloat,
            'opened_at'     => now(),
            'notes'         => $notes,
        ]);
    }

    /**
     * Close an open POS session.
     */
    public function closeSession(PosSession $session, float $closingFloat): PosSession
    {
        $totalSales = $session->orders()->completed()->sum('total_amount');

        $session->update([
            'status'        => 'closed',
            'closed_by'     => Auth::id(),
            'closing_float' => $closingFloat,
            'total_sales'   => $totalSales,
            'closed_at'     => now(),
        ]);

        return $session->refresh();
    }

    /**
     * Process a POS sale.
     *
     * @param array<string, mixed>             $orderData
     * @param array<int, array<string, mixed>> $items
     */
    public function processSale(PosSession $session, array $orderData, array $items): PosOrder
    {
        return DB::transaction(function () use ($session, $orderData, $items): PosOrder {
            $order = PosOrder::create(array_merge($orderData, [
                'session_id' => $session->id,
                'status'     => 'completed',
            ]));

            foreach ($items as $item) {
                $item['total_price'] = $item['quantity'] * $item['unit_price'] - ($item['discount'] ?? 0);
                $order->items()->create($item);

                if (!empty($item['product_id'])) {
                    $product = \Crommix\Inventory\Models\Product::find($item['product_id']);

                    if ($product && $product->track_inventory) {
                        $this->inventoryService->adjustStock(
                            $product,
                            -(int) $item['quantity'],
                            'out',
                            null,
                            "POS Order #{$order->id}",
                            'pos_order',
                            $order->id,
                        );
                    }
                }
            }

            $this->recalculate($order);

            return $order->refresh();
        });
    }

    /**
     * Recalculate order totals from items.
     */
    public function recalculate(PosOrder $order): void
    {
        $subtotal = $order->items()->sum('total_price');
        $order->update([
            'subtotal'     => $subtotal,
            'total_amount' => $subtotal + $order->tax_amount - $order->discount_amount,
        ]);
    }
}
