<?php

namespace App\Support;

class PhoneFormatter
{
    public const DEFAULT_COUNTRY_CODE = '223';

    public static function normalize(string $phone, string $defaultCountryCode = self::DEFAULT_COUNTRY_CODE): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        } elseif (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        $phone = (string) preg_replace('/\D+/', '', $phone);

        if ($phone === '') {
            return '';
        }

        if (!str_starts_with($phone, $defaultCountryCode) && \strlen($phone) <= 8) {
            $phone = $defaultCountryCode . $phone;
        }

        return $phone;
    }

    public static function toWhatsappJid(string $phone, string $defaultCountryCode = self::DEFAULT_COUNTRY_CODE): string
    {
        return static::normalize($phone, $defaultCountryCode) . '@s.whatsapp.net';
    }

    /**
     * Returns a SQL expression that strips non-digit characters from the `phone` column,
     * or null when the driver has no built-in regex support (e.g. SQLite).
     */
    public static function stripNonDigitsSql(string $driver): ?string
    {
        return match ($driver) {
            'pgsql'            => "regexp_replace(coalesce(phone, ''), '\\D+', '', 'g')",
            'mysql', 'mariadb' => "REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '')",
            default            => null,
        };
    }
}
