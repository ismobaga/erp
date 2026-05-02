<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'legal_name',
    'slug',
    'email',
    'phone',
    'website',
    'address',
    'city',
    'country',
    'currency',
    'tax_number',
    'logo_path',
    'slogan',
    'bank_name',
    'bank_account_name',
    'bank_account_number',
    'bank_swift_code',
    'invoice_default_notes',
    'quote_default_notes',
    'is_active',
    'whatsapp_device_id',
    'whatsapp_enabled',
])]
class Company extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'bank_account_number' => 'encrypted',
            'bank_swift_code' => 'encrypted',
        ];
    }

    /**
     * Backward-compatible accessor so that views and services that previously
     * read `CompanySetting->company_name` continue to work with the unified
     * Company model without requiring view changes.
     */
    public function getCompanyNameAttribute(): string
    {
        return $this->name;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function financialPeriods(): HasMany
    {
        return $this->hasMany(FinancialPeriod::class);
    }

    public function ledgerAccounts(): HasMany
    {
        return $this->hasMany(LedgerAccount::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
