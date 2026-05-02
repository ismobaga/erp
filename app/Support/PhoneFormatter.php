<?php

namespace App\Support;

class PhoneFormatter
{
    public static function toWhatsappJid(string $phone, string $defaultCountryCode = '223'): string
    {
        // Normalize leading + and 00 before stripping non-digits
        $phone = ltrim(trim($phone));

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        } elseif (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        $phone = (string) preg_replace('/\D+/', '', $phone);

        if (! str_starts_with($phone, $defaultCountryCode) && strlen($phone) <= 8) {
            $phone = $defaultCountryCode . $phone;
        }

        return $phone . '@s.whatsapp.net';
    }
}
