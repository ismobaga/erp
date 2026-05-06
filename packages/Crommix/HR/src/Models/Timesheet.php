<?php

namespace Crommix\HR\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use App\Models\User;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected $table = 'hr_timesheets';

    protected $fillable = [
        'company_id',
        'employee_id',
        'week_start',
        'week_end',
        'regular_hours',
        'overtime_hours',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'regular_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function getTotalHoursAttribute(): float
    {
        return (float) $this->regular_hours + (float) $this->overtime_hours;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
