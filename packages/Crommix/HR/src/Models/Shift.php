<?php

namespace Crommix\HR\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected $table = 'hr_shifts';

    protected $fillable = [
        'company_id',
        'name',
        'start_time',
        'end_time',
        'break_duration',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
