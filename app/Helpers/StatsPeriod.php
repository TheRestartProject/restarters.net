<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Date windows for headline stats toggles ("All time" / "Last 12 months"), shared so the Fixometer and group pages
 * can offer the same toggle later.
 */
class StatsPeriod
{
    public const ALL = 'all';
    public const LAST_12_MONTHS = 'last_12_months';

    /**
     * The window for a period name as [from, to] (UTC, to exclusive), or [null, null] for all time.  Last 12 months
     * is the last 12 completed calendar months, so the figure doesn't change day by day: on 2026-10-24 it is
     * 2025-10-01 to 2026-09-30.
     */
    public static function window(?string $period, ?Carbon $now = null): array
    {
        if ($period !== self::LAST_12_MONTHS) {
            return [null, null];
        }

        $to = ($now ?? Carbon::now('UTC'))->copy()->setTimezone('UTC')->startOfMonth();

        return [$to->copy()->subMonths(12), $to];
    }

    /**
     * The first and last days covered by a window, for display.
     */
    public static function describe(Carbon $from, Carbon $to): array
    {
        return ['start' => $from->toDateString(), 'end' => $to->copy()->subDay()->toDateString()];
    }
}
