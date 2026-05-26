<?php

namespace App\Auth;

use App\Support\PhoneFormatter;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class PhoneOrEmailUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        $identifier = trim((string) ($credentials['email'] ?? $credentials['login'] ?? ''));

        if ($identifier === '') {
            return null;
        }

        $query = $this->createModel()->newQuery();

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $query->where('email', $identifier)->first();
        }

        $normalizedPhone = PhoneFormatter::normalize($identifier);

        return $query->where(function ($inner) use ($identifier, $normalizedPhone): void {
            $inner->where('phone', $identifier)
                ->orWhere('phone', $normalizedPhone);

            if ($normalizedPhone !== '' && $normalizedPhone !== $identifier) {
                $inner->orWhereRaw("regexp_replace(coalesce(phone, ''), '\\D+', '', 'g') = ?", [$normalizedPhone]);
            }
        })->first();
    }
}