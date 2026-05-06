<?php

namespace Crommix\Payroll\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use App\Models\User;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected $table = 'payroll_runs';

    protected $fillable = [
        'company_id',
        'reference',
        'period_month',
        'status',
        'total_gross',
        'total_net',
        'total_deductions',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_gross'      => 'decimal:2',
            'total_net'        => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'processed_at'     => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
