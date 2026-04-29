<?php

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable monetary value object backed by BCMath string arithmetic.
 *
 * All operations use scale=2, matching the decimal:2 database columns.
 * Using BCMath eliminates the floating-point rounding errors that can
 * accumulate when adding/multiplying FCFA amounts via PHP floats.
 *
 * Usage:
 *   $price    = Money::of('1000.00');
 *   $quantity = '3';
 *   $line     = $price->multiply($quantity);   // '3000.00'
 *   $total    = $line->add(Money::of('200'));   // '3200.00'
 */
final class Money
{
    private const SCALE = 2;

    /** BCMath-formatted decimal string, always at SCALE decimal places. */
    private readonly string $amount;

    private function __construct(string $amount)
    {
        $this->amount = bcadd($amount, '0', self::SCALE);
    }

    /**
     * Create a Money instance from a string, int, or float.
     *
     * Floats are converted via string cast first to preserve the digits the
     * caller intended; BCMath then re-rounds to scale 2.
     */
    public static function of(string|int|float $amount): self
    {
        return new self((string) $amount);
    }

    /** Return Money(0.00). */
    public static function zero(): self
    {
        return new self('0');
    }

    public function add(self $other): self
    {
        return new self(bcadd($this->amount, $other->amount, self::SCALE));
    }

    public function subtract(self $other): self
    {
        return new self(bcsub($this->amount, $other->amount, self::SCALE));
    }

    /**
     * Multiply by a scalar (quantity, rate, etc.).
     * The multiplier is cast to string so floats like 1.5 work without
     * precision loss beyond scale 2.
     */
    public function multiply(string|int|float $multiplier): self
    {
        return new self(bcmul($this->amount, (string) $multiplier, self::SCALE));
    }

    /**
     * Return the larger of this or $other (identical to PHP max() for floats).
     */
    public function max(self $other): self
    {
        return bccomp($this->amount, $other->amount, self::SCALE) >= 0 ? $this : $other;
    }

    public function isGreaterThan(self $other): bool
    {
        return bccomp($this->amount, $other->amount, self::SCALE) > 0;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return bccomp($this->amount, $other->amount, self::SCALE) >= 0;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) === 0;
    }

    /** Return the raw BCMath string (e.g. "1234.56"). */
    public function toString(): string
    {
        return $this->amount;
    }

    /** Compatibility accessor – returns the BCMath string, NOT a float. */
    public function __toString(): string
    {
        return $this->amount;
    }

    /**
     * Convert to float for use in legacy code paths or Filament placeholders.
     *
     * Prefer toString() wherever a decimal string suffices; floats can lose
     * precision for values beyond 15 significant digits.
     */
    public function toFloat(): float
    {
        return (float) $this->amount;
    }

    /**
     * Format as a human-readable currency string, e.g. "FCFA 1 234.56".
     */
    public function format(string $currency = 'FCFA'): string
    {
        return $currency . ' ' . number_format((float) $this->amount, self::SCALE, '.', ' ');
    }
}
