<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
}
