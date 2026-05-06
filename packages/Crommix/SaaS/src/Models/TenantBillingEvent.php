<?php

namespace Crommix\SaaS\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit record of every SaaS billing event for a tenant.
 *
 * @property int         $id
 * @property int         $company_id
 * @property int|null    $plan_id
 * @property string      $event_type
 * @property float       $amount
 * @property string      $currency
 * @property string      $status
 * @property string|null $external_reference
 * @property array|null  $metadata
 */
class TenantBillingEvent extends Model
{
    protected $table = 'tenant_billing_events';

    protected $fillable = [
        'company_id',
        'plan_id',
        'event_type',
        'amount',
        'currency',
        'status',
        'external_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TenantPlan::class, 'plan_id');
    }
}
