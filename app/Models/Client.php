<?php

namespace App\Models;

use App\Services\TaxProfileResolver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'type',
    'company_name',
    'contact_name',
    'email',
    'phone',
    'address',
    'city',
    'country',
    'notes',
    'status',
    'created_by',
    'updated_by',
])]
class Client extends Model
{
    public function taxProfile(): array
    {
        return app(TaxProfileResolver::class)->resolveForClient($this);
    }
}
