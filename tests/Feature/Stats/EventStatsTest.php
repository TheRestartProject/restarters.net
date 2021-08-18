<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\Party;
use Tests\Feature\Stats\StatsTestCase;


class EventStatsTest extends StatsTestCase
{
    /** @test */
    public function an_event_with_no_devices_has_empty_stats()
    {
        $event = factory(Party::class)->create();
        $expect = \App\Party::getEventStatsArrayKeys();
        $expect['hours_volunteered'] = 21;
        $this->assertEquals($expect, $event->getEventStats());
    }

    /** @test */
    public function event_stats_with_both_powered_and_unpowered_devices()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event' => 1,
        ]);
        $expect = \App\Party::getEventStatsArrayKeys();
        $expect['powered_co2'] = 14.4 * $this->_displacementFactor;
        $expect['powered_waste'] = 4;
        $expect['co2'] += $expect['powered_co2'];
        $expect['waste'] += $expect['powered_waste'];
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['hours_volunteered'] = 21;
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #2 add a powered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => 1,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['powered_no_weight']++;
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
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['unpowered_co2'] = 15.5 * $this->_displacementFactor;
        $expect['unpowered_waste'] = 5;
        $expect['co2'] = $expect['powered_co2'] + $expect['unpowered_co2'];
        $expect['waste'] = $expect['powered_waste'] + $expect['unpowered_waste'];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => 1,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['unpowered_no_weight']++;
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #5 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => 1,
            'estimate' => 1.23,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['powered_co2'] = (14.4 + (1.23 * $this->_ratioPowered)) * $this->_displacementFactor;
        $expect['powered_waste'] += 1.23;
        $expect['co2'] = $expect['powered_co2'] + $expect['unpowered_co2'];
        $expect['waste'] = $expect['powered_waste'] + $expect['unpowered_waste'];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #6 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => 1,
            'estimate' => 4.56,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['unpowered_co2'] = (15.5 + (4.56 * $this->_ratioUnpowered)) * $this->_displacementFactor;
        $expect['unpowered_waste'] += 4.56;
        $expect['co2'] = $expect['powered_co2'] + $expect['unpowered_co2'];
        $expect['waste'] = $expect['powered_waste'] + $expect['unpowered_waste'];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #7 add an unpowered non-misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => 1,
            'estimate' => 7.89,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['unpowered_co2'] = (15.5 + ((4.56 + 7.89) * $this->_ratioUnpowered)) * $this->_displacementFactor;
        $expect['unpowered_waste'] += 7.89;
        $expect['co2'] = $expect['powered_co2'] + $expect['unpowered_co2'];
        $expect['waste'] = $expect['powered_waste'] + $expect['unpowered_waste'];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }
}
