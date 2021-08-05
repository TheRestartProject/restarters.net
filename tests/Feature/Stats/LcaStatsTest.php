<?php

namespace Tests\Feature;

use App\Device;
use App\Helpers\LcaStats;

use DB;
use Tests\Feature\Stats\StatsTestCase;

/**
 *
 */
class LcaStatsTest extends StatsTestCase
{

    /** @test */
    public function get_displacement_factor()
    {
        $result = LcaStats::getDisplacementFactor();
        $this->assertEquals($this->_displacementFactor, $result);
    }

    /** @test */
    public function get_ratio_unpowered()
    {
        $result = LcaStats::getEmissionRatioUnpowered();
        $this->assertEquals($this->_ratioUnpowered, $result);
    }

    /** @test */
    public function get_ratio_powered()
    {
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
        $e = round(LcaStats::getEmissionRatioPowered(), 1);
        $expect = round((14.4) / (4), 1);
        $this->assertEquals($expect, round($e, 1));
    }

    /** @test */
    public function get_waste_stats()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        DB::statement("UPDATE categories SET weight=0, footprint=0 WHERE idcategories IN (' . $this->_idMiscPowered . ',' . $this->_idMiscUnpowered .')");

        // #1 add a single powered non-misc device
        factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
        ]);
        $expect = [
            'total_weight' => 4,
            'powered_waste' => 4,
            'unpowered_waste' => 0,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 0,
        ];
        $result = LcaStats::getWasteStats();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        factory(Device::class)->states('fixed')->create([
            'category' => $this->_idMiscPowered,
            'category_creation' => $this->_idMiscPowered,
        ]);
        $expect = [
            'total_weight' => 4,
            'powered_waste' => 4,
            'unpowered_waste' => 0,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 0,
        ];
        $result = LcaStats::getWasteStats();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
        ]);
        $expect = [
            'total_weight' => 9,
            'powered_waste' => 4,
            'unpowered_waste' => 5,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStats::getWasteStats();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        factory(Device::class)->states('fixed')->create([
            'category' => $this->_idMiscUnpowered,
            'category_creation' => $this->_idMiscUnpowered,
        ]);
        $expect = [
            'total_weight' => 9,
            'powered_waste' => 4,
            'unpowered_waste' => 5,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStats::getWasteStats();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }
    }

    /** @test */
    public function get_waste_stats_group()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        DB::statement("UPDATE categories SET weight=0, footprint=0 WHERE idcategories IN (' . $this->_idMiscPowered . ',' . $this->_idMiscUnpowered .')");

        $group = 1;

        // #1 add a single powered non-misc device
        factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => 1,
        ]);
        $expect = [
            'total_weight' => 4,
            'powered_waste' => 4,
            'unpowered_waste' => 0,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 0,
        ];
        $result = LcaStats::getWasteStats($group);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        factory(Device::class)->states('fixed')->create([
            'category' => $this->_idMiscPowered,
            'category_creation' => $this->_idMiscPowered,
            'event' => 1,
        ]);
        $expect = [
            'total_weight' => 4,
            'powered_waste' => 4,
            'unpowered_waste' => 0,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 0,
        ];
        $result = LcaStats::getWasteStats($group);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => 1,
        ]);
        $expect = [
            'total_weight' => 9,
            'powered_waste' => 4,
            'unpowered_waste' => 5,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStats::getWasteStats($group);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        factory(Device::class)->states('fixed')->create([
            'category' => $this->_idMiscUnpowered,
            'category_creation' => $this->_idMiscUnpowered,
            'event' => 1,
        ]);
        $expect = [
            'total_weight' => 9,
            'powered_waste' => 4,
            'unpowered_waste' => 5,
            'powered_footprint' => 14.4 * $this->_displacementFactor,
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStats::getWasteStats($group);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }
    }


}
