<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
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
])]
class CompanySetting extends Model
{
}
