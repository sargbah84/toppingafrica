<?php

declare(strict_types=1);

namespace App\Support;

class FollowerFormat
{
    /**
     * Format a follower count as a short, human-friendly string.
     *
     *   500       → "500"
     *   1_500     → "1.5K"
     *   12_000    → "12K"
     *   250_000   → "250K"
     *   1_500_000 → "1.5M"
     *   12_000_000 → "12M"
     *
     * Returns null for null/zero so callers can hide the label entirely
     * instead of showing "0 followers".
     */
    public static function short(?int $count): ?string
    {
        if ($count === null || $count <= 0) {
            return null;
        }

        if ($count >= 1_000_000) {
            $v = $count / 1_000_000;
            return self::trim($v) . 'M';
        }

        if ($count >= 1_000) {
            $v = $count / 1_000;
            return self::trim($v) . 'K';
        }

        return (string) $count;
    }

    /**
     * Trim a float to at most one decimal, dropping ".0" for whole numbers.
     * 1.50 → "1.5", 12.00 → "12".
     */
    private static function trim(float $v): string
    {
        $rounded = round($v, 1);

        return rtrim(rtrim(number_format($rounded, 1, '.', ''), '0'), '.');
    }
}
