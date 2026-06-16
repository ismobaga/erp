<?php

namespace App\Auth;

use App\Support\PhoneFormatter;
use Illuminate\Auth\EloquentUserProvider;

class PhoneOrEmailUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        $identifier = trim((string) ($credentials['email'] ?? $credentials['login'] ?? ''));

        if ($identifier === '') {
            return null;
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->createModel()->newQuery()
                ->where('email', $identifier)
                ->first();
        }

        $normalized = PhoneFormatter::normalize($identifier);

        // Build the set of exact variants to match against.
        // Covers: raw input, digits-only normalized, and local part (no country code).
        $variants = array_values(array_unique(array_filter(
            [$identifier, $normalized],
            fn (string $v) => $v !== '',
        )));

        // If the normalized form has the default country prefix, also try the
        // local part so that a number stored as "75123456" is found when the
        // user types "+22375123456" (and vice-versa).
        $localPart = '';
        $countryCode = PhoneFormatter::DEFAULT_COUNTRY_CODE;

        if ($normalized !== '' && str_starts_with($normalized, $countryCode)) {
            $localPart = substr($normalized, strlen($countryCode));

            if ($localPart !== '') {
                $variants[] = $localPart;
            }
        }

        $variants = array_values(array_unique($variants));

        return $this->createModel()->newQuery()
            ->where(function ($query) use ($variants, $normalized, $localPart): void {
                // Exact match on any variant (fast index scan).
                $query->whereIn('phone', $variants);

                // Fallback: strip all non-digit characters from the stored phone
                // and compare — catches numbers stored as "+223 75 12 34 56", etc.
                $stripSql = $this->stripNonDigitsSql();

                if ($stripSql !== null && $normalized !== '') {
                    $query->orWhereRaw("{$stripSql} = ?", [$normalized]);

                    if ($localPart !== '') {
                        $query->orWhereRaw("{$stripSql} = ?", [$localPart]);
                    }
                }
            })
            ->first();
    }

    /**
     * Returns a SQL expression that strips non-digit characters from `phone`,
     * or null when the current driver has no built-in regex support (SQLite).
     */
    private function stripNonDigitsSql(): ?string
    {
        return match ($this->createModel()->getConnection()->getDriverName()) {
            'pgsql'           => "regexp_replace(coalesce(phone, ''), '\\D+', '', 'g')",
            'mysql', 'mariadb' => "REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '')",
            default           => null,
        };
    }
}
