<?php

namespace Tests\Unit;

use App\Helpers\StatsPeriod;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class StatsPeriodTest extends TestCase
{
    public function testAllTimeHasNoWindow(): void
    {
        $this->assertSame([null, null], StatsPeriod::window('all'));
        $this->assertSame([null, null], StatsPeriod::window(null));
    }

    public function testLastTwelveMonthsAreCompletedCalendarMonths(): void
    {
        foreach (['2026-10-01 00:00:00', '2026-10-24 12:00:00', '2026-10-31 23:59:59'] as $now) {
            [$from, $to] = StatsPeriod::window('last_12_months', Carbon::parse($now, 'UTC'));
            $this->assertEquals(['start' => '2025-10-01', 'end' => '2026-09-30'], StatsPeriod::describe($from, $to));
        }

        [$from, $to] = StatsPeriod::window('last_12_months', Carbon::parse('2026-01-15', 'UTC'));
        $this->assertEquals(['start' => '2025-01-01', 'end' => '2025-12-31'], StatsPeriod::describe($from, $to));
    }
}
