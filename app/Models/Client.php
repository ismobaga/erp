<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use App\Services\TaxProfileResolver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[Fillable([
    'company_id', // scoped by HasCompanyScope but still needs to be fillable for proper mass assignment in factories and imports
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
    'portal_token',
    'created_by',
    'updated_by',
])]
class Client extends Model
{
    use HasCompanyScope;
    protected static function booted(): void
    {
        static::creating(function (Client $client): void {
            if (blank($client->portal_token)) {
                $client->portal_token = (string) Str::uuid();
            }
        });
    }

    public function portalUrl(): string
    {
        return route('portal.index', ['token' => $this->portal_token]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

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
        // Use pre-loaded withSum aggregate to avoid N+1 in list views.
        if (array_key_exists('invoices_sum_balance_due', $this->getAttributes())) {
            return (float) $this->getAttribute('invoices_sum_balance_due');
        }

        return (float) $this->invoices()->sum('balance_due');
    }

    public function totalPaid(): float
    {
        if (array_key_exists('payments_sum_amount', $this->getAttributes())) {
            return (float) $this->getAttribute('payments_sum_amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    public function taxProfile(): array
    {
        return app(TaxProfileResolver::class)->resolveForClient($this);
    }

    /**
     * Rotate the portal token, invalidating any previously shared portal links.
     */
    public function regeneratePortalToken(): void
    {
        $this->forceFill(['portal_token' => (string) Str::uuid()])->save();
    }
}
