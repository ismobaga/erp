<?php

namespace Crommix\SaaS\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tracks a tenant's usage against a specific metric quota.
 *
 * @property int         $id
 * @property int         $company_id
 * @property string      $metric
 * @property int         $used
 * @property int|null    $limit       NULL means unlimited.
 * @property string      $period
 * @property Carbon|null $reset_at
 */
class UsageQuota extends Model
{
    protected $table = 'tenant_usage_quotas';

    protected $fillable = [
        'company_id',
        'metric',
        'used',
        'limit',
        'period',
        'reset_at',
    ];

    protected function casts(): array
    {
        return [
            'used'     => 'integer',
            'limit'    => 'integer',
            'reset_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Whether the tenant has reached or exceeded the limit for this metric.
     * Always returns false when the limit is null (unlimited).
     */
    public function isExceeded(): bool
    {
        if ($this->limit === null) {
            return false;
        }

        return $this->used >= $this->limit;
    }

    /**
     * Remaining usage before the quota is exceeded (null = unlimited).
     */
    public function remaining(): ?int
    {
        if ($this->limit === null) {
            return null;
        }

        return max(0, $this->limit - $this->used);
    }

    /**
     * Percentage of quota consumed (0–100). Returns 0 when unlimited.
     */
    public function percentUsed(): float
    {
        if ($this->limit === null || $this->limit === 0) {
            return 0.0;
        }

        return min(100.0, round(($this->used / $this->limit) * 100, 1));
    }
}
