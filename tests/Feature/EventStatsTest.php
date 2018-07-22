<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use App\Group;
use App\Party;

use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventStatsTest extends TestCase
{
    //use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        Group::truncate();
        Party::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function an_event_with_no_devices_has_no_co2()
    {
        $event = factory(Party::class)->create();

        $eventStats = $event->getEventStats(0.5);
        $eventCo2 = $eventStats['co2'];

        $expectedCo2 = 0;

        $this->assertEquals($expectedCo2, $eventCo2);
    }

    /** @test */
    public function an_event_with_a_fixed_device_has_some_co2()
    {
        $event = factory(Party::class)->create();
        $device = factory(Device::class)->states('fixed', 'mobile')->create([
            'event' => $event->idevents,
        ]);

        $displacementRatio = 0.5;

        $eventStats = $event->getEventStats($displacementRatio);
        $eventCo2 = $eventStats['co2'];

        $expectedCo2 = round($device->deviceCategory->footprint) * $displacementRatio;

        $this->assertEquals($expectedCo2, $eventCo2);
    }

    /** @test */
    public function an_event_with_some_devices_but_none_fixed_has_some_co2()
    {
        $event = factory(Party::class)->create();
        factory(Device::class)->states('repairable', 'mobile')->create([
            'event' => $event->idevents,
        ], 5);
        factory(Device::class)->states('end', 'mobile')->create([
            'event' => $event->idevents,
        ], 5);

        $displacementRatio = 0.5;

        $eventStats = $event->getEventStats($displacementRatio);
        $eventCo2 = $eventStats['co2'];

        $expectedCo2 = 0;

        $this->assertEquals($expectedCo2, $eventCo2);
    }
}
