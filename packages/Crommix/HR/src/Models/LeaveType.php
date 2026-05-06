<?php

namespace Crommix\HR\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected $table = 'hr_leave_types';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'days_per_year',
        'is_paid',
        'requires_approval',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
