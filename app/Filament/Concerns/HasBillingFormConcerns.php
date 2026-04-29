<?php

namespace App\Filament\Concerns;

/**
 * Shared billing helpers for Filament Resources that deal with
 * monetary line items (Invoice, Quote, etc.).
 *
 * Centralises:
 *   – formatMoney()      – locale-aware FCFA display
 *   – calculateTotals()  – subtotal / total from a Repeater items array
 */
trait HasBillingFormConcerns
{
    /**
     * Format a float amount as "FCFA X.XX" using the configured currency
     * symbol from company settings.
     *
     * The currency symbol falls back to 'FCFA' so the method is safe to
     * call before any company settings have been saved.
     */
    protected static function formatMoney(float $amount): string
    {
        $currency = (string) config('erp.billing.currency', 'FCFA');

        return $currency . ' ' . number_format($amount, 2, '.', ' ');
    }

    /**
     * Compute billing totals from a Repeater items array.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  float  $discount  Total discount amount (subtracted from subtotal).
     * @param  float  $tax       Total tax amount (added to subtotal after discount).
     * @return array{subtotal: float, total: float}
     */
    protected static function calculateTotals(array $items, float $discount, float $tax): array
    {
        $subtotal = collect($items)->sum(
            fn(array $item): float => ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0))
        );

        return [
            'subtotal' => $subtotal,
            'total'    => max(0.0, $subtotal - $discount + $tax),
        ];
    }
}
