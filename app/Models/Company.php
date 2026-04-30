<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
#[Fillable([
    'name',
    'legal_name',
    'slug',
    'currency',
    'log_path',
    'settings',
])]
class Company extends Model
{
       protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function users() {
        return $this->belongsToMany(User::class)
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
}
