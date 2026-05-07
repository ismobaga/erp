<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'user_id',
    'name',
    'scope',
    'token_hash',
    'abilities',
    'last_used_at',
    'expires_at',
    'revoked_at',
])]
#[Hidden(['token_hash'])]
class ApiToken extends Model
{
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{token: self, plainTextToken: string}
     */
    public static function issue(
        User $user,
        Company $company,
        string $name = 'Integration token',
        string $scope = 'private',
    ): array {
        $plainTextToken = Str::random(64);

        $token = static::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'name' => $name,
            'scope' => in_array($scope, ['public', 'private'], true) ? $scope : 'private',
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => Carbon::now()->addDays((int) config('erp.api.token_ttl_days', 365)),
        ]);

        return [
            'token' => $token,
            'plainTextToken' => $plainTextToken,
        ];
    }
}
