<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
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
    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

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
                $service->code = static::generateServiceCode();
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

    public static function generateServiceCode(): string
    {
        $prefix = 'SVC';

        $max = static::query()
            ->where('code', 'like', $prefix . '-%')
            ->pluck('code')
            ->reduce(function (int $carry, ?string $code) use ($prefix): int {
                if (!filled($code)) {
                    return $carry;
                }

                $normalized = Str::upper((string) $code);
                $expectedPrefix = $prefix . '-';

                if (!str_starts_with($normalized, $expectedPrefix)) {
                    return $carry;
                }

                $suffix = substr($normalized, strlen($expectedPrefix));

                if (!ctype_digit($suffix)) {
                    return $carry;
                }

                return max($carry, (int) $suffix);
            }, 0);

        return $prefix . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
