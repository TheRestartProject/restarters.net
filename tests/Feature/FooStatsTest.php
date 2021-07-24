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
 * SEE "ERROR?" COMMENTS FOR KNOWN/POSSIBLE CODING ISSUES *
 *
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
        ]);
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** EVENTS */

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
            'ewaste' => 4, // ERROR? Unpowered Misc weight is counted, powered Misc is not
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
            'ewaste' => 13, // ERROR? Unpowered Misc weight is counted, powered Misc is not
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
            'ewaste' => 13, // ERROR? Unpowered Misc weight is counted, powered Misc is not
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
            'unpowered_waste' => 6, // ERROR? Unpowered Misc weight is counted, powered Misc is not
            'fixed_devices' => 2,
            'fixed_powered' => 0,
            'fixed_unpowered' => 2,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0, // ERROR? Unpowered Misc weight is counted, powered Misc is not
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
            'no_weight' => 0, // ERROR? Unpowered Misc weight is counted, powered Misc is not
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
            'no_weight' => 0, // ERROR? Unpowered Misc weight is counted, powered Misc is not
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
            'ewaste' => 4, // ERROR? Unpowered Misc weight is counted, powered Misc is not
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
            'no_weight' => 1, // ERROR? Unpowered Misc weight is counted, powered Misc is not
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


    /** WEIGHTS */

    /**
     * Tests query in Device::getWeights().
     * Note that the getWeights query contains a sub query that is duplicated in the footprint helper getRatio() query.
    */
    /** @test */
    public function get_weights_old()
    {
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
        ]);
        factory(Category::class)->create([
            'idcategories' => 5,
            'revision' => 1,
            'name' => 'unpowered non-misc',
            'powered' => 0,
        ]);
        factory(Category::class)->create([
            'idcategories' => $this->_id_misc_unpowered,
            'revision' => 1,
            'name' => 'unpowered misc',
            'powered' => 0,
        ]);
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeights();
        logger('#1');
        logger(print_r($expect,1));
        logger(print_r($result,1));
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k},2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeights();
        logger('#2');
        logger(print_r($expect,1));
        logger(print_r($result,1));
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k},2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4, // ERROR? Powered Misc weight is not counted
            'unpowered_waste' => 0, // ERROR? Unpowered Misc weight is usually counted, here it is not
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeights();
        logger('#3');
        logger(print_r($expect,1));
        logger(print_r($result,1));
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k},2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4, // ERROR? Powered Misc weight is not counted
            'unpowered_waste' => 0, // ERROR? Unpowered Misc weight is usually counted, here it is not
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeights();
        logger('#4');
        logger(print_r($expect,1));
        logger(print_r($result,1));
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k},2), "Wrong value for $k => $v");
        }
    }

    /**
     * Tests proposed changes to query in Device::getWeightsNew().
     * Note that the getWeights query contains a sub query that is duplicated in the footprint helper getRatio() query.
    */
    /** @test */
    public function get_weights_new()
    {
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
        ]);
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeightsNew();
        logger('#1');
        logger(print_r($expect,1));
        logger(print_r($result,1));
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k},2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4, // ERROR? Unpowered Misc weight is counted, powered Misc is not
            'unpowered_waste' => 0,
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeightsNew();
        logger('#2');
        logger(print_r($expect,1));
        logger(print_r($result,1));
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k},2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
        ]);
        $expect = [
            'total_weights' => 9,
            'ewaste' => 4, // ERROR? Unpowered Misc weight is counted, powered Misc is not
            'unpowered_waste' => 5,
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeightsNew();
        logger('#3');
        logger(print_r($expect,1));
        logger(print_r($result,1));
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k},2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
        ]);
        $expect = [
            'total_weights' => 9,
            'ewaste' => 4, // ERROR? Unpowered Misc weight is counted, powered Misc is not
            'unpowered_waste' => 5, // ERROR? Unpowered Misc weight is counted, powered Misc is not
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeightsNew();
        logger('#4');
        logger(print_r($expect,1));
        logger(print_r($result,1));
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k},2), "Wrong value for $k => $v");
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
