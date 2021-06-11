<?php

namespace Tests\Feature;

use App\Group;
use App\Party;

use Carbon\Carbon;
use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupStatsTest extends TestCase
{
    //use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Group::truncate();
        Party::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function a_group_with_no_events_has_empty_stats()
    {
        $group = factory(Group::class)->create()->first();

        $expectedStats = [
            'pax' => 0,
            'hours' => 0,
            'parties' => 0,
            'co2' => 0,
            'waste' => 0,
        ];
        $this->assertEquals($expectedStats, $group->getGroupStats(0.5));
    }

    /** @test */
    public function a_group_with_one_past_event_has_stats_for_that_event()
    {
        $group = factory(Group::class)->create();
        $event = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday(),
            'group' => $group->idgroups
        ]);

        $expectedStats = [
            'pax' => 0,
            'hours' => 21,
            'parties' => 1,
            'co2' => 0,
            'waste' => 0,
        ];
        $this->assertEquals($expectedStats, $group->getGroupStats(0.5));
    }
}
