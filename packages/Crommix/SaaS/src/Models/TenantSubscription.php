<?php

namespace Crommix\SaaS\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tracks a tenant's active subscription to a plan.
 *
 * @property int         $id
 * @property int         $company_id
 * @property int         $plan_id
 * @property string      $status
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property Carbon|null $cancelled_at
 * @property string|null $external_reference
 * @property array|null  $metadata
 */
class TenantSubscription extends Model
{
    protected $table = 'tenant_subscriptions';

    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'external_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at'        => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end'   => 'datetime',
            'cancelled_at'         => 'datetime',
            'metadata'             => 'array',
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

    // ── Status helpers ─────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing'
            && ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->current_period_end !== null && $this->current_period_end->isPast() && !$this->isTrialing());
    }

    public function isValid(): bool
    {
        return $this->isActive() || $this->isTrialing();
    }

    /**
     * Days remaining in the current billing period (or trial).
     */
    public function daysRemaining(): int
    {
        $end = $this->isTrialing() ? $this->trial_ends_at : $this->current_period_end;

        if ($end === null) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($end, false));
    }
}
