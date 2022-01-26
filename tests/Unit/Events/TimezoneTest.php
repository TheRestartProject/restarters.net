<?php

namespace Tests\Unit;

use App\Group;
use App\Party;
use DB;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    /**
     * @test
     * @dataProvider timezoneProvider
     */
    public function timezone_inheritance($event, $group, $result, $exception) {
        $g = factory(Group::class)->create([
                                                   'timezone' => $group
                                               ]);

        $e = factory(Party::class)->create([
           'timezone' => $event,
           'group' => $g->idgroups
        ]);

        try {
            $timezone = $e->timezone;
            $this->assertEquals($result, $timezone);
        } catch(\Exception $e) {
            if ($exception) {
                $this->assertTrue(true);
            } else {
                $this->assertFalse(true, 'Unexpected exception thrown');
            }
        }
    }

    public function timezoneProvider() {
        return [
            [ NULL, 'Asia/Samarkand', 'Asia/Samarkand', FALSE ],
            [ 'Asia/Samarkand', NULL, 'Asia/Samarkand', FALSE ],
            [ NULL, NULL, NULL, TRUE],
        ];
    }

    /**
     * @test
     */
    public function startEnd() {
        $g = factory(Group::class)->create([
           'timezone' => 'Asia/Samarkand'
        ]);

        // Create an event in a different timezone, using local times.
        $e = factory(Party::class)->create([
            'group' => $g->idgroups,
            'event_start_utc' => '2021-01-01T10:15:05+05:00',
            'event_end_utc' => '2021-01-01T13:45:05+05:00',
            'timezone' => NULL // Inherit from group.
        ]);

        // Check that the ISO times are as we would expect for this zone.
        self::assertEquals('2021-01-01T10:15:05+00:00', $e->startDateTimeISO8601);
        self::assertEquals('2021-01-01T13:45:05+00:00', $e->endDateTimeISO8601);

        // Check that the local times are as we expect.
        self::assertEquals('2021-01-01', $e->event_date);
        self::assertEquals('15:15:05', $e->start);
        self::assertEquals('18:45:05', $e->end);

        // Update the ISO times using a different timezone and check that the local times update.
        $e->event_start_utc = '2021-01-01T13:00:00+00:00';
        $e->event_end_utc = '2021-01-01T14:00:00+00:00';

        self::assertEquals('2021-01-01', $e->event_date);
        self::assertEquals('18:00:00', $e->start);
        self::assertEquals('19:00:00', $e->end);
    }
}
