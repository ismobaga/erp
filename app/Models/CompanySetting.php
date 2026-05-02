<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'company_name',
    'legal_name',
    'slogan',
    'email',
    'phone',
    'website',
    'address',
    'city',
    'country',
    'currency',
    'tax_number',
    'logo_path',
    'invoice_default_notes',
    'quote_default_notes',
    'bank_name',
    'bank_account_name',
    'bank_account_number',
    'bank_swift_code',
    'whatsapp_device_id',
    'whatsapp_enabled',
])]
class CompanySetting extends Model
{
    use HasCompanyScope;

    protected function casts(): array
    {
        return [
            'whatsapp_enabled' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
