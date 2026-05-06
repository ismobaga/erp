<?php

namespace Crommix\Inventory\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected $table = 'inventory_products';

    protected $fillable = [
        'company_id',
        'category_id',
        'sku',
        'name',
        'description',
        'unit',
        'cost_price',
        'sale_price',
        'stock_quantity',
        'min_stock_level',
        'is_active',
        'track_inventory',
    ];

    protected function casts(): array
    {
        return [
            'cost_price'      => 'decimal:2',
            'sale_price'      => 'decimal:2',
            'is_active'       => 'boolean',
            'track_inventory' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
    }
}
