<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ExpiryStatus
{
    public const SOON_DAYS = 3;

    public const EXPIRED = 'expired';

    public const EXPIRING_SOON = 'expiring_soon';

    public const FRESH = 'fresh';

    /** Null when the item has no expiry date. */
    public static function for(?CarbonInterface $expiry, ?CarbonInterface $today = null): ?string
    {
        if (! $expiry) {
            return null;
        }

        $days = self::daysLeft($expiry, $today);

        return match (true) {
            $days < 0 => self::EXPIRED,
            $days <= self::SOON_DAYS => self::EXPIRING_SOON,
            default => self::FRESH,
        };
    }

    /** Whole days until expiry: 0 = today, 1 = tomorrow, negative = past. */
    public static function daysLeft(CarbonInterface $expiry, ?CarbonInterface $today = null): int
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();

        return (int) $today->diffInDays($expiry->copy()->startOfDay(), false);
    }
}
