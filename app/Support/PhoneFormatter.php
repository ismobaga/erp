<?php

namespace App\Support;

class PhoneFormatter
{
    public static function normalize(string $phone, string $defaultCountryCode = '223'): string
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

        if (!str_starts_with($phone, $defaultCountryCode) && strlen($phone) <= 8) {
            $phone = $defaultCountryCode . $phone;
        }

        return $phone;
    }

    public static function toWhatsappJid(string $phone, string $defaultCountryCode = '223'): string
    {
        return static::normalize($phone, $defaultCountryCode) . '@s.whatsapp.net';
    }
}
