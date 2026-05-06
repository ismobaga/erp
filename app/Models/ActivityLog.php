<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'action',
    'subject_type',
    'subject_id',
    'meta_json',
    'ip_address',
    'user_agent',
])]
class ActivityLog extends Model
{
    use HasCompanyScope;
    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
        ];
    }
}
