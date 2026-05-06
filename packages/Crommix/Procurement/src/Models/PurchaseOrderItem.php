<?php

namespace Crommix\Procurement\Models;

use Crommix\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $table = 'procurement_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'quantity_received',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'decimal:3',
            'unit_price'        => 'decimal:2',
            'total_price'       => 'decimal:2',
            'quantity_received' => 'decimal:3',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
