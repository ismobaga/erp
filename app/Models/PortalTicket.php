<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'client_id',
    'subject',
    'body',
    'status',
    'priority',
    'reply',
    'replied_at',
])]
class PortalTicket extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
