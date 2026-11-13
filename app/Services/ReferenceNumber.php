<?php

namespace App\Services;

/**
 * Human-friendly numbers built from a row id, so they are unique without a counter table:
 * make('AMS', 42) in November 2026 is "AMS-261100042".
 */
class ReferenceNumber
{
    public static function make(string $prefix, int $id): string
    {
        return $prefix.'-'.now()->format('ym').str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }
}
