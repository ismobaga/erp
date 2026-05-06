<?php

namespace Crommix\HR\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected $table = 'hr_employee_documents';

    protected $fillable = [
        'company_id',
        'employee_id',
        'document_type',
        'title',
        'file_path',
        'issued_at',
        'expires_at',
        'is_confidential',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'is_confidential' => 'boolean',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
