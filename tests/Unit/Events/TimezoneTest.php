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

        DB::connection()->enableQueryLog();

        // Create an event in a different timezone, using local times.
        $e = factory(Party::class)->create([
            'group' => $g->idgroups,
            'event_date' => '2021-01-01',
            'start' => '10:15',
            'end' => '13:45',
            'timezone' => NULL // Inherit from group.
        ]);

        $queries = DB::getQueryLog();
        error_log(var_export($queries, TRUE));

        // Check that the ISO times are as we would expect for this zone.
        self::assertEquals('2021-01-01T05:15:00+00:00', $e->startDateTimeISO8601);
        self::assertEquals('2021-01-01T08:45:00+00:00', $e->endDateTimeISO8601);
    }
}
