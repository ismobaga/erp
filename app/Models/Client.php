<?php

namespace App\Models;

use App\Services\TaxProfileResolver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function totalBalance(): float
    {
        return (float) $this->invoices()->sum('balance_due');
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function taxProfile(): array
    {
        return app(TaxProfileResolver::class)->resolveForClient($this);
    }
}
