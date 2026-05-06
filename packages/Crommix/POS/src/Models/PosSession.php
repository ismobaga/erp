<?php

namespace Crommix\POS\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use App\Models\User;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected $table = 'pos_sessions';

    protected $fillable = [
        'company_id',
        'opened_by',
        'closed_by',
        'status',
        'opening_float',
        'closing_float',
        'total_sales',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_float' => 'decimal:2',
            'closing_float' => 'decimal:2',
            'total_sales'   => 'decimal:2',
            'opened_at'     => 'datetime',
            'closed_at'     => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'session_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }
}
