<?php

namespace Crommix\CRM\Models;

use App\Models\Company;
use App\Models\Concerns\HasCompanyScope;
use App\Models\User;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'company_id',
        'lead_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'job_title',
        'company_name',
        'address',
        'notes',
        'assigned_to',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
