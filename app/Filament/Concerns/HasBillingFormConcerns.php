<?php

namespace App\Filament\Concerns;

use App\ValueObjects\Money;

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
    public static function formatMoney(float $amount): string
    {
        $currency = (string) config('erp.billing.currency', 'FCFA');

        return Money::of($amount)->format($currency);
    }

    /**
     * Compute billing totals from a Repeater items array using BCMath to
     * avoid floating-point accumulation on line-item sums.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  float  $discount  Total discount amount (subtracted from subtotal).
     * @param  float  $tax       Total tax amount (added to subtotal after discount).
     * @return array{subtotal: float, total: float}
     */
    protected static function calculateTotals(array $items, float $discount, float $tax): array
    {
        $subtotal = Money::zero();

        foreach ($items as $item) {
            $qty   = (string) ($item['quantity'] ?? 0);
            $price = (string) ($item['unit_price'] ?? 0);
            $subtotal = $subtotal->add(Money::of($qty)->multiply($price));
        }

        $discountMoney = Money::of((string) $discount);
        $taxMoney      = Money::of((string) $tax);
        $total         = Money::zero()->max($subtotal->subtract($discountMoney)->add($taxMoney));

        return [
            'subtotal' => $subtotal->toFloat(),
            'total'    => $total->toFloat(),
        ];
    }
}
