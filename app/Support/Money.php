<?php

namespace App\Support;

class Money
{
    /**
     * Parse a typed amount like "19.99", "$1,200" or "5" into integer cents,
     * using string arithmetic only (never floats). Returns null when the
     * input isn't a plain amount of at most $9,999,999.99.
     */
    public static function parseCents(string $input): ?int
    {
        if (! preg_match('/^\$?(\d{1,3}(?:,\d{3})+|\d+)(?:\.(\d{1,2}))?$/', trim($input), $matches)) {
            return null;
        }

        $whole = str_replace(',', '', $matches[1]);

        if (strlen(ltrim($whole, '0')) > 7) {
            return null;
        }

        return (int) $whole * 100 + (int) str_pad($matches[2] ?? '', 2, '0');
    }
}
