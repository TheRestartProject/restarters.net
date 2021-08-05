<?php

namespace Tests\Feature;

use App\Device;
use App\Party;
use Tests\Feature\Stats\StatsTestCase;


class EventStatsTest extends StatsTestCase
{
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

    /** @test */
    public function event_stats_with_both_powered_and_unpowered_devices_and_unpowered_weights()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => 1,
        ]);
        $expect = [
            'co2' => 14.4 * $this->_displacementFactor,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'fixed_devices' => 1,
            'fixed_powered' => 1,
            'fixed_unpowered' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
            'devices_powered' => 1,
            'devices_unpowered' => 0,
        ];
        $result = $device->deviceEvent->getEventStats();
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

    /** @test */
    public function group_stats_with_only_unpowered_devices_with_unpowered_weights()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        // #1 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => 1,
        ]);
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 5,
            'fixed_devices' => 1,
            'fixed_powered' => 0,
            'fixed_unpowered' => 1,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
            'devices_powered' => 0,
            'devices_unpowered' => 1,
        ];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #2 add an unpowered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idMiscUnpowered,
            'category_creation' => $this->_idMiscUnpowered,
            'event' => 1,
        ]);
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 5,
            'fixed_devices' => 2,
            'fixed_powered' => 0,
            'fixed_unpowered' => 2,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 1,
            'devices_powered' => 0,
            'devices_unpowered' => 2,
        ];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #3 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idMiscUnpowered,
            'category_creation' => $this->_idMiscUnpowered,
            'event' => 1,
            'estimate' => 9,
        ]);
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 14,
            'fixed_devices' => 3,
            'fixed_powered' => 0,
            'fixed_unpowered' => 3,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 1,
            'devices_powered' => 0,
            'devices_unpowered' => 3,
        ];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device not "Fixed"
        $device = factory(Device::class)->states('repairable')->create([
            'category' => $this->_idMiscUnpowered,
            'category_creation' => $this->_idMiscUnpowered,
            'event' => 1,
        ]);
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 14,
            'fixed_devices' => 3,
            'fixed_powered' => 0,
            'fixed_unpowered' => 3,
            'repairable_devices' => 1,
            'dead_devices' => 0,
            'no_weight' => 1,
            'devices_powered' => 0,
            'devices_unpowered' => 4,
        ];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }
}
