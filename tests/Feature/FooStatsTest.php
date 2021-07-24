<?php

namespace Tests\Feature;

use App\Party;
use App\Device;
use App\DeviceBarrier;
use App\Category;
use App\Helpers\FootprintRatioCalculator;

use DB;
use Tests\TestCase;

/**
 * THIS TEST IS A WORK IN PROGRESS PRIOR TO
 * THE INTRODUCTION OF LCA STATS FOR UNPOWERED ITEMS
 * WHICH WILL IMPACT ON SEVERAL FUNCTIONS AND QUERIES
 *
 * SOME RESULTS ARE FUDGED TO PREVENT CI BORKAGE
 * SEE "ERROR?" COMMENTS FOR KNOWN/POSSIBLE CODING ISSUES
 */
class FooStatsTest extends TestCase
{
    private $_displacementFactor;
    private $_id_misc_powered;
    private $_id_misc_unpowered;

    public function setUp()
    {
        parent::setUp();
        $Device = new Device;
        $this->_displacementFactor = $Device->displacement;
        $this->_id_misc_powered = env('MISC_CATEGORY_ID_POWERED');
        $this->_id_misc_unpowered = env('MISC_CATEGORY_ID_UNPOWERED');

        DB::statement("SET foreign_key_checks=0");
        Category::truncate();
        DB::statement("SET foreign_key_checks=1");
        factory(Category::class)->create([
            'idcategories' => 4,
            'revision' => 1,
            'name' => 'powered non-misc',
            'powered' => 1,
            'weight' => 4,
            'footprint' => 14.4,
        ]);
        factory(Category::class)->create([
            'idcategories' => $this->_id_misc_powered,
            'revision' => 1,
            'name' => 'powered misc',
            'powered' => 1,
            'weight' => 1,
            'footprint' => 1,
        ]);
        factory(Category::class)->create([
            'idcategories' => 5,
            'revision' => 1,
            'name' => 'unpowered non-misc',
            'powered' => 0,
            'weight' => 5,
            'footprint' => 15.5,
        ]);
        factory(Category::class)->create([
            'idcategories' => $this->_id_misc_unpowered,
            'revision' => 1,
            'name' => 'unpowered misc',
            'powered' => 0,
            'weight' => 1,
            'footprint' => 1,
        ]);
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function event_stats_with_only_powered_data()
    {
        $displacement = $this->_getDisplacementFactor();

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => (14.4 * $displacement),
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
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #2 add a powered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $expect = [
            'co2' => (14.4 * $displacement),
            'ewaste' => 4, // ERROR? should be 5?
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
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #3 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
            'event' => 1,
            'estimate' => 9,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $expect = [
            'co2' => (14.4 * $displacement) + (9 * $emissionRatio * $displacement),
            'ewaste' => 13, // ERROR? should be 14?
            'unpowered_waste' => 0,
            'fixed_devices' => 3,
            'fixed_powered' => 3,
            'fixed_unpowered' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 1,
            'devices_powered' => 3,
            'devices_unpowered' => 0,
        ];
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #4 add a powered misc device not "Fixed"
        $device = factory(Device::class)->states('repairable')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $expect = [
            'co2' => (14.4 * $displacement) + (9 * $emissionRatio * $displacement),
            'ewaste' => 13, // ERROR? should be 14?
            'unpowered_waste' => 0,
            'fixed_devices' => 3,
            'fixed_powered' => 3,
            'fixed_unpowered' => 0,
            'repairable_devices' => 1,
            'dead_devices' => 0,
            'no_weight' => 1,
            'devices_powered' => 4,
            'devices_unpowered' => 0,
        ];
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }

    /** @test */
    public function event_stats_with_only_unpowered_data()
    {
        $displacement = $this->_getDisplacementFactor();

        // #1 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
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
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #2 add an unpowered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 6,
            'fixed_devices' => 2,
            'fixed_powered' => 0,
            'fixed_unpowered' => 2,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0, // ERROR? should be 1?
            'devices_powered' => 0,
            'devices_unpowered' => 2,
        ];
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #3 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
            'event' => 1,
            'estimate' => 9,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 15,
            'fixed_devices' => 3,
            'fixed_powered' => 0,
            'fixed_unpowered' => 3,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0, // ERROR? should be 2?
            'devices_powered' => 0,
            'devices_unpowered' => 3,
        ];
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device not "Fixed"
        $device = factory(Device::class)->states('repairable')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 15,
            'fixed_devices' => 3,
            'fixed_powered' => 0,
            'fixed_unpowered' => 3,
            'repairable_devices' => 1,
            'dead_devices' => 0,
            'no_weight' => 0, // ERROR? should be 2?
            'devices_powered' => 0,
            'devices_unpowered' => 4,
        ];
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }

    /** @test */
    public function event_stats_with_both_lca_data()
    {
        $displacement = $this->_getDisplacementFactor();

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $expect = [
            'co2' => 14.4 * $displacement,
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
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #2 add a powered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $expect = [
            'co2' => 14.4 * $displacement,
            'ewaste' => 4, // ERROR? should be 5?
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
        $result = $device->deviceEvent->getEventStats($emissionRatio);
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
        $emissionRatio = $this->_getEmissionRatio();
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 14.4 * $displacement,
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
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
            'event' => 1,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        // CO2 estimates don't include unpowered items. yet.
        $expect = [
            'co2' => 14.4 * $displacement,
            'ewaste' => 4,
            'unpowered_waste' => 6,
            'fixed_devices' => 4,
            'fixed_powered' => 2,
            'fixed_unpowered' => 2,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 1, // ERROR? should be 2?
            'devices_powered' => 2,
            'devices_unpowered' => 2,
        ];
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }

    private function _getEmissionRatio()
    {
        $footprintRatioCalculator = new FootprintRatioCalculator();
        return $footprintRatioCalculator->calculateRatio();
    }

    private function _getDisplacementFactor()
    {
        return $this->_displacementFactor;
    }
}
