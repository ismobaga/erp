<?php

namespace App\Support;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor that redacts sensitive fields from log records
 * before they are written to any log channel.
 */
class SensitiveDataRedactionProcessor implements ProcessorInterface
{
    /**
     * Top-level keys whose values should be fully redacted.
     */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'api_secret',
        'secret',
        'private_key',
        'card_number',
        'cvv',
        'pin',
        'authorization',
        'cookie',
        'x-csrf-token',
        'x-api-key',
        'portal_token',
        'temporary_password',
        'access_token',
        'refresh_token',
    ];

    /**
     * Keys that should have partial masking (first 4 / last 4 chars visible).
     */
    private const PARTIAL_MASK_KEYS = [
        'account_number',
        'bank_account_number',
        'swift_code',
        'iban',
    ];

    /**
     * Process a log record and redact sensitive data.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $this->redact($record->extra);
        $context = $this->redact($record->context);

        return $record->with(extra: $extra, context: $context);
    }

    /**
     * Recursively redact sensitive keys from an array.
     */
    private function redact(array $data, int $depth = 0): array
    {
        // Prevent infinite recursion on deeply nested structures
        if ($depth > 10) {
            return $data;
        }

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (in_array($lowerKey, self::REDACTED_KEYS, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (in_array($lowerKey, self::PARTIAL_MASK_KEYS, true) && is_string($value) && strlen($value) > 8) {
                $data[$key] = substr($value, 0, 4) . str_repeat('*', strlen($value) - 8) . substr($value, -4);
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value, $depth + 1);
            }
        }

        return $data;
    }
}
