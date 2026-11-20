<?php

namespace App\Services;

use InvalidArgumentException;
use LogicException;
use OverflowException;

/** Exact USD arithmetic. One stored unit is 10^-12 USD (a picodollar). */
final class Money
{
    public const UNITS_PER_USD = 1_000_000_000_000;

    public static function decimalToUnits(string|int $amount): int
    {
        $value = trim((string) $amount);

        if (!preg_match('/^(\d+)(?:\.(\d{1,12}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Invalid non-negative USD amount.');
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 12, '0');

        if ($whole > intdiv(PHP_INT_MAX - $fraction, self::UNITS_PER_USD)) {
            throw new OverflowException('USD amount exceeds supported integer precision.');
        }

        return ($whole * self::UNITS_PER_USD) + $fraction;
    }

    public static function unitsToDecimal(int $units, int $places = 9): string
    {
        if ($places < 0 || $places > 12) {
            throw new InvalidArgumentException('Decimal places must be between 0 and 12.');
        }

        if ($units === PHP_INT_MIN) {
            throw new OverflowException('USD amount exceeds supported integer precision.');
        }

        $sign = $units < 0 ? '-' : '';
        $units = abs($units);
        $whole = intdiv($units, self::UNITS_PER_USD);
        $fraction = str_pad((string) ($units % self::UNITS_PER_USD), 12, '0', STR_PAD_LEFT);

        return $places === 0
            ? $sign . $whole
            : $sign . $whole . '.' . substr($fraction, 0, $places);
    }

    /** Rates are USD per million tokens; no floating-point arithmetic is used. */
    public static function tokenCost(int $tokens, string $ratePerMillion): int
    {
        if ($tokens < 0) {
            throw new InvalidArgumentException('Token count cannot be negative.');
        }

        $rateUnits = self::decimalToUnits($ratePerMillion);

        if ($tokens !== 0 && $rateUnits > intdiv(PHP_INT_MAX, $tokens)) {
            throw new OverflowException('Token cost exceeds supported integer precision.');
        }

        $numerator = $tokens * $rateUnits;

        if ($numerator % 1_000_000 !== 0) {
            throw new LogicException('Rate cannot be represented at configured precision.');
        }

        return intdiv($numerator, 1_000_000);
    }

    public static function withMarkup(int $providerCostUnits): int
    {
        if ($providerCostUnits < 0 || $providerCostUnits % 4 !== 0) {
            throw new LogicException('25% markup is not exactly representable.');
        }

        $quarter = intdiv($providerCostUnits, 4);

        if ($quarter > intdiv(PHP_INT_MAX, 5)) {
            throw new OverflowException('Customer charge exceeds supported integer precision.');
        }

        return $quarter * 5;
    }
}
