<?php

namespace Tests\Feature;

use App\Device;
use App\Group;
use App\Party;
use Carbon\Carbon;
use Tests\Feature\Stats\StatsTestCase;

class GroupStatsTest extends StatsTestCase
{
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
            'ewaste' => 0,
            'unpowered_waste' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
        ];
        $this->assertEquals($expectedStats, $group->getGroupStats(0.5));
    }

    /** @test */
    public function a_group_with_one_past_event_has_stats_for_that_event()
    {
        $group = factory(Group::class)->create();
        $event = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday(),
            'group' => $group->idgroups,
        ]);

        $expectedStats = [
            'pax' => 0,
            'hours' => 21,
            'parties' => 1,
            'co2' => 0,
            'waste' => 0,
            'unpowered_waste' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
            'ewaste' => 0,
        ];
        $this->assertEquals($expectedStats, $group->getGroupStats(0.5));
    }

    /** @test */
    public function group_stats_with_both_powered_and_unpowered_devices()
    {
        $group = factory(Group::class)->create();
        $event = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday(),
            'group' => $group->idgroups,
        ]);

        $this->_setupCategoriesWithUnpoweredWeights();

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => $event->idevents,
        ]);
        $expect =  [
            'parties' => 1,
            'co2' => 14.4 * $this->_displacementFactor,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'waste' => 4 + 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
        ];
        $result = $group->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #2 add a powered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idMiscPowered,
            'category_creation' => $this->_idMiscPowered,
            'event' => 1,
        ]);
        $expect = [
            'co2' => 14.4 * $this->_displacementFactor,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'fixed_devices' => 2,
            'fixed_powered' => 2,
            'fixed_unpowered' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 1,
            'devices_powered' => 2,
            'devices_unpowered' => 0,
        ];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => 1,
        ]);
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 14.4 * $this->_displacementFactor,
            'ewaste' => 4,
            'unpowered_waste' => 5,
            'fixed_devices' => 3,
            'fixed_powered' => 2,
            'fixed_unpowered' => 1,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 1,
            'devices_powered' => 2,
            'devices_unpowered' => 1,
        ];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idMiscUnpowered,
            'category_creation' => $this->_idMiscUnpowered,
            'event' => 1,
        ]);
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 14.4 * $this->_displacementFactor,
            'ewaste' => 4,
            'unpowered_waste' => 5,
            'fixed_devices' => 4,
            'fixed_powered' => 2,
            'fixed_unpowered' => 2,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 2,
            'devices_powered' => 2,
            'devices_unpowered' => 2,
        ];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }
}
