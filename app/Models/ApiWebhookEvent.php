<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'api_token_id',
    'source',
    'event',
    'payload_json',
    'received_at',
])]
class ApiWebhookEvent extends Model
{
    use HasCompanyScope;

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'received_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class);
    }
}
