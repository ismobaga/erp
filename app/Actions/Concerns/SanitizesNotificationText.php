<?php

namespace App\Actions\Concerns;

trait SanitizesNotificationText
{
    protected function sanitizeNotificationText(mixed $value, string $fallback = ''): string
    {
        $text = filled($value) ? (string) $value : $fallback;

        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
