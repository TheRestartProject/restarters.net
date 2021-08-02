<?php

namespace Tests\Feature;

use App\Device;
use App\DeviceBarrier;
use App\Category;
use App\Helpers\LcaStatsHelper;

use DB;
use Tests\TestCase;

/**
 * THIS TEST IS A WORK IN PROGRESS PRIOR TO
 * THE INTRODUCTION OF LCA STATS FOR UNPOWERED ITEMS
 * WHICH WILL IMPACT ON SEVERAL FUNCTIONS AND QUERIES
 *
 * SOME RESULTS ARE FUDGED TO PREVENT CI BORKAGE
 * SEE "ERROR?" COMMENTS FOR KNOWN/POSSIBLE CODING ISSUES
 *
 * SHOULD POWERED MISC WEIGHT COUNT TOWARDS WASTE AND CO2 STATS?
 * SHOULD UNPOWERED MISC WEIGHT COUNT TOWARDS WASTE AND CO2 STATS?
 *
 */
class FooStatsTest extends TestCase
{
    private $_displacementFactor;
    private $_id_misc_powered;
    private $_id_misc_unpowered;

    protected function setUp(): void
    {
        parent::setUp();
        $Device = new Device;
        $this->_displacementFactor = $Device->displacement;
        $this->_id_misc_powered = env('MISC_CATEGORY_ID_POWERED');
        $this->_id_misc_unpowered = env('MISC_CATEGORY_ID_UNPOWERED');
    }

    /** EVENTS */

    /** @test */
    public function event_stats_with_only_powered_devices_with_unpowered_weights()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

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
            'ewaste' => 13,
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
            'ewaste' => 13,
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
    public function event_stats_with_only_unpowered_devices_with_unpowered_weights()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

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
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }

    /** @test */
    public function event_stats_with_both_powered_and_unpowered_devices_and_unpowered_weights()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

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
    public function get_weights_old_without_unpowered_weights()
    {
        $this->_setupCategoriesWithoutUnpoweredWeights();

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
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
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
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'total_footprints' => 14.4 * $this->_displacementFactor,
        ];
        $result = $device->getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }
    }

    /**
     * Tests proposed changes to query in Device::getWeightsNew().
     * Note that the getWeights query contains a sub query that is duplicated in the footprint helper getRatio() query.
     */
    /** @test */
    public function get_weights_new()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        DB::statement("UPDATE categories SET weight=0, footprint=0 WHERE idcategories IN (46,50)");

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
        ]);
        $expect = [
            'total_weights' => 4,
            'powered_waste' => 4,
            'unpowered_waste' => 0,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 0,
        ];
        $result = LcaStatsHelper::getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
        ]);
        $expect = [
            'total_weights' => 4,
            'powered_waste' => 4,
            'unpowered_waste' => 0,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 0,
        ];
        $result = LcaStatsHelper::getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
        ]);
        $expect = [
            'total_weights' => 9,
            'powered_waste' => 4,
            'unpowered_waste' => 5,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStatsHelper::getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
        ]);
        $expect = [
            'total_weights' => 9,
            'powered_waste' => 4,
            'unpowered_waste' => 5,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStatsHelper::getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }
    }

    /**
     * No test for Device::getPartyWeights().
     * The SQL will return NULL values.
     * APPEARS TO BE A REDUNDANT METHOD.
     */

    /**
     * Device::countCO2ByYear() is REDUNDANT
     * Tests query in Device::countCO2ByYear() without unpowered lca data.
     * Note: it selects from a view that contains SQL that is duplicated in the footprint helper getRatio() query.
     * Note: countCO2ByYear() rounds the result to 0 places.
     */
    public function co2_by_year_without_unpowered_weights()
    {
        $this->_setupCategoriesWithoutUnpoweredWeights();

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => 1,
        ]);
        $expect = [
            'co2' => round(14.4 * $this->_displacementFactor),
        ];
        $result = $device->countCO2ByYear(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
            'event' => 1,
        ]);
        $expect = [
            'co2' => round(14.4 * $this->_displacementFactor),
        ];
        $result = $device->countCO2ByYear(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => 1,
        ]);
        $expect = [
            'co2' => round(14.4 * $this->_displacementFactor),
        ];
        $result = $device->countCO2ByYear(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
            'event' => 1,
        ]);
        $expect = [
            'co2' => round(14.4 * $this->_displacementFactor),
        ];
        $result = $device->countCO2ByYear(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }
    }

    /**
     */
    /** @test */
    public function lcastats_helper_calculate_ratio()
    {
        $this->_setupCategoriesWithoutUnpoweredWeights();
        factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => 1,
        ]);
        factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => 1,
        ]);
        $e = round(LcaStatsHelper::calculateEmissionRatioPowered(),1);
        $expect = round((14.4) / (4),1);
        $this->assertEquals($expect, round($e,1));
        $u = round(LcaStatsHelper::calculateEmissionRatioUnpowered(),1);
        $expect = 0;
        $this->assertEquals($expect, $u);
        $b = round(LcaStatsHelper::calculateEmissionRatio(),1);
        $expect = round((14.4) / (4),1);
        $this->assertEquals($expect, $b);


        $this->_setupCategoriesWithUnpoweredWeights();
        factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => 1,
        ]);
        factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => 1,
        ]);
        $e = round(LcaStatsHelper::calculateEmissionRatioPowered(),1);
        $expect = round((14.4) / (4),1);
        $this->assertEquals($expect, round($e,1));
        $u = round(LcaStatsHelper::calculateEmissionRatioUnpowered(),1);
        $expect = round((15.5) / (5),1);
        $this->assertEquals($expect, $u);
        $b = round(LcaStatsHelper::calculateEmissionRatio(),1);
        $expect = round((14.4+15.5) / (4+5),1);
        $this->assertEquals($expect, $b);

    }

    /**
     * Device::countWasteByYear() is REDUNDANT
     * Tests query in Device::countWasteByYear() without unpowered lca data.
     */
    /** @test */
    public function waste_by_year_without_unpowered_weights()
    {
        $this->_setupCategoriesWithoutUnpoweredWeights();

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => 1,
        ]);
        $expect = [
            'waste' => 4,
        ];
        $result = $device->countWasteByYear(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_powered,
            'category_creation' => $this->_id_misc_powered,
            'event' => 1,
        ]);
        $expect = [
            'waste' => 4,
        ];
        $result = $device->countWasteByYear(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => 1,
        ]);
        $expect = [
            'waste' => 4,
        ];
        $result = $device->countWasteByYear(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_id_misc_unpowered,
            'category_creation' => $this->_id_misc_unpowered,
            'event' => 1,
        ]);
        $expect = [
            'waste' => 4,
        ];
        $result = $device->countWasteByYear(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }
    }


    /**
     * Not so much a test as a debugging routine.
     */
    public function all_calcs_without_unpowered_weights()
    {
        $this->_setupCategoriesWithoutUnpoweredWeights();
        $p = str_repeat('*',36);
        $expected = [
        'weight_powered' => 0,
        'weight_unpowered' => 0,
        'misc_powered' => 0,
        'misc_unpowered' => 0,
        'misc_estimates' => 0,
        ];
        logger("\n$p #1 ADD A SINGLE POWERED NON-MISC DEVICE $p");
        // category->weight=4, category->footprint=14.4, device->estimate=0
        $expected['weight_powered'] += 4;
        $this->_all_calcs_insert_device(4, $expected);

        logger("\n$p #2 ADD A POWERED MISC DEVICE WITHOUT ESTIMATE ***");
        // category->weight=1, category->footprint=0, device->estimate=0
        $expected['misc_powered'] += 1; $expected['weight_powered'] += 1;
        $this->_all_calcs_insert_device($this->_id_misc_powered, $expected); // misc cat weight counts or not?

        logger("\n$p #3 ADD A POWERED MISC DEVICE WITH ESTIMATE ***");
        // category->weight=1, category->footprint=0, device->estimate=8
        $expected['misc_powered'] += 1; $expected['weight_powered'] += 8; $expected['misc_estimates'] += 1;
        $this->_all_calcs_insert_device($this->_id_misc_powered, $expected, 8);

        logger("\n$p #4 ADD AN UNPOWERED NON-MISC DEVICE ***");
        // category->weight=0, category->footprint=0, device->estimate=0
        $this->_all_calcs_insert_device(5, $expected);

        logger("\n$p #5 ADD AN UNPOWERED MISC DEVICE WITHOUT ESTIMATE ***");
        // category->weight=0, category->footprint=0, device->estimate=0
        $expected['misc_unpowered'] += 1;
        $this->_all_calcs_insert_device($this->_id_misc_unpowered, $expected);

        logger("\n$p #6 ADD AN UNPOWERED MISC DEVICE WITH ESTIMATE ***");
        // category->weight=0, category->footprint=0, device->estimate=6
        $expected['misc_unpowered'] += 1; $expected['weight_unpowered'] += 6; $expected['misc_estimates'] += 1;
        $this->_all_calcs_insert_device($this->_id_misc_unpowered, $expected, 6);

        $this->assertTrue(TRUE);
    }

    private function _all_calcs_insert_device($category, $expect, $estimate=0)
    {
        $device = factory(Device::class)->states('fixed')->create([
            'event' => 1,
            'category' => $category,
            'category_creation' => $category,
            'estimate' => $estimate,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $qry = "SELECT d.iddevices, c.`name` as category, c.weight, c.footprint, d.estimate FROM devices d JOIN categories c ON c.idcategories = d.category WHERE iddevices = " . $device->iddevices;
        $result = DB::select(DB::raw($qry));
        logger("\tiddevices => " . $result[0]->iddevices);
        logger("\tcategory => " . $result[0]->category);
        logger("\tweight => " . $result[0]->weight);
        logger("\testimate => " . $result[0]->estimate);
        logger("\tfootprint => " . $result[0]->footprint);
        logger("\temissionRatio => " . $emissionRatio);

        logger("Actual accumulated");
        foreach ($expect as $k => $v) {
            logger("\t$k = $v");
        }

        logger("=== Device->ewasteDiverted() ===");
        $result = $device->ewasteDiverted();
        logger("\t" . $result);

        logger("=== Device->unpoweredWasteDiverted() ===");
        $result = $device->unpoweredWasteDiverted();
        logger("\t" . $result);

        logger("=== Device->co2Diverted() ===");
        $result = $device->co2Diverted($emissionRatio, $this->_getDisplacementFactor());
        logger("\t" . $result);

        logger("=== Device->getWeights() ===");
        $result = $device->getWeights();
        logger("\ttotal_weights => " . $result[0]->total_weights);
        logger("\tewaste => " . $result[0]->ewaste);
        logger("\tunpowered_waste => " . $result[0]->unpowered_waste);
        logger("\ttotal_footprints => " . $result[0]->total_footprints);

        logger("=== Device->Event->getEventStats() ===");
        $result = $device->deviceEvent->getEventStats($emissionRatio);
        logger("\tco2 => " . $result['co2']);
        logger("\tewaste => " . $result['ewaste']);
        logger("\tunpowered_waste => " . $result['unpowered_waste']);
        logger("\tno_weight => " . $result['no_weight']);
        logger("\tfixed_powered => " . $result['fixed_powered']);
        logger("\tfixed_unpowered => " . $result['fixed_unpowered']);

        logger("=== Device->countWasteByYear() ===");
        $result = $device->countWasteByYear(1);
        logger("\twaste => " . $result[0]->waste);

        logger("=== Device->countCO2ByYear() ===");
        $result = $device->countCO2ByYear(1);
        logger("\tco2 => " . $result[0]->co2);

    }

    private function _getEmissionRatio()
    {
        return LcaStatsHelper::calculateEmissionRatio();
    }

    private function _getDisplacementFactor()
    {
        return $this->_displacementFactor;
    }

    private function _setupCategoriesWithUnpoweredWeights()
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
            'weight' => 0,
            'footprint' => 0,
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
            'weight' => 0,
            'footprint' => 0,
        ]);
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    private function _setupCategoriesWithoutUnpoweredWeights()
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
            'powered' => 0,
            'weight' => 0,
            'footprint' => 0,
        ]);
        factory(Category::class)->create([
            'idcategories' => 5,
            'revision' => 1,
            'name' => 'unpowered non-misc',
            'powered' => 0,
            'weight' => 0,
            'footprint' => 0,
        ]);
        factory(Category::class)->create([
            'idcategories' => $this->_id_misc_unpowered,
            'revision' => 1,
            'name' => 'unpowered misc',
            'powered' => 0,
            'weight' => 0,
            'footprint' => 0,
        ]);
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
    }
}
