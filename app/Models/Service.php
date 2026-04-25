<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'code',
    'name',
    'category',
    'description',
    'default_price',
    'is_active',
])]
class Service extends Model
{
    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Service $service): void {
            if (blank($service->code)) {
                throw ValidationException::withMessages([
                    'code' => 'A service code is required.',
                ]);
            }

            $duplicate = static::query()
                ->where('code', $service->code)
                ->when($service->exists, fn($q) => $q->whereKeyNot($service->getKey()))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'code' => 'This service code is already in use.',
                ]);
            }
        });
    }
}
