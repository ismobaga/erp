<?php

namespace Crommix\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a SaaS subscription plan.
 *
 * @property int    $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property float  $price_monthly
 * @property float  $price_yearly
 * @property string $currency
 * @property bool   $is_active
 * @property bool   $is_public
 * @property int    $trial_days
 * @property array<string> $features
 * @property array<string, int|null> $limits
 * @property int    $sort_order
 */
class TenantPlan extends Model
{
    protected $table = 'tenant_plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'is_active',
        'is_public',
        'trial_days',
        'features',
        'limits',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly'  => 'decimal:2',
            'is_active'     => 'boolean',
            'is_public'     => 'boolean',
            'trial_days'    => 'integer',
            'features'      => 'array',
            'limits'        => 'array',
            'sort_order'    => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id');
    }

    public function billingEvents(): HasMany
    {
        return $this->hasMany(TenantBillingEvent::class, 'plan_id');
    }

    /**
     * Whether this plan includes the given feature key.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, (array) ($this->features ?? []), true);
    }

    /**
     * Return the quota limit for a given metric, or null if unlimited.
     */
    public function limitFor(string $metric): ?int
    {
        $limits = (array) ($this->limits ?? []);

        return array_key_exists($metric, $limits) ? (int) $limits[$metric] : null;
    }
}
