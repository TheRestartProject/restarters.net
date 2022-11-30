<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\Group;
use App\Helpers\LcaStats;
use App\Party;
use DB;
use Tests\Feature\Stats\StatsTestCase;

class LcaStatsTest extends StatsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

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
        $result = LcaStats::getEmissionRatioPowered();
        $this->assertEquals($this->_ratioPowered, $result);
    }

    /** @test */
    public function get_waste_stats()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        DB::statement("UPDATE categories SET weight=0, footprint=0 WHERE idcategories IN (' . $this->_idPoweredMisc . ',' . $this->_idUnpoweredMisc .')");

        // #1 add a powered non-misc device
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
        ]);
        $expect = [
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
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
        ]);
        $expect = [
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
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
        ]);
        $expect = [
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

        // #4 add an unpowered misc device without estimate
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
        ]);
        $expect = [
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

        // #5 add another powered non-misc device
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
        ]);
        $expect = [
            'powered_waste' => 8,
            'unpowered_waste' => 5,
            'powered_footprint' => (14.4 * 2) * $this->_displacementFactor,
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStats::getWasteStats();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #6 add a powered misc device with estimate
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'estimate' => 1.23,
        ]);
        $expect = [
            'powered_waste' => 8 + 1.23,
            'unpowered_waste' => 5,
            'powered_footprint' => round(((14.4 * 2) + (1.23 * $this->_ratioPowered)) * $this->_displacementFactor, 2),
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStats::getWasteStats();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #6 add an unpowered non-misc device with estimate
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
            'estimate' => 4.56,
        ]);
        $expect = [
            'powered_waste' => 8 + 1.23,
            'unpowered_waste' => 5 + 4.56,
            'powered_footprint' => round(((14.4 * 2) + (1.23 * $this->_ratioPowered)) * $this->_displacementFactor, 2),
            'unpowered_footprint' => round((15.5 + (4.56 * $this->_ratioUnpowered)) * $this->_displacementFactor, 2),
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

        DB::statement("UPDATE categories SET weight=0, footprint=0 WHERE idcategories IN (' . $this->_idPoweredMisc . ',' . $this->_idUnpoweredMisc .')");

        $groupObj = Group::factory()->create();
        $group = $groupObj->idgroups;
        $event = Party::factory()->create([ 'group' => $group ]);

        // #1 add a single powered non-misc device
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event' => $event->idevents,
        ]);
        $expect = [
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
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event->idevents,
        ]);
        $expect = [
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
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
            'event' => $event->idevents,
        ]);
        $expect = [
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
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event->idevents,
        ]);
        $expect = [
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

        // #5 add a powered misc device with estimate
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event->idevents,
            'estimate' => 1.23,
        ]);
        $expect = [
            'powered_waste' => 4 + 1.23,
            'unpowered_waste' => 5,
            'powered_footprint' => round((14.4 + (1.23 * $this->_ratioPowered)) * $this->_displacementFactor, 2),
            'unpowered_footprint' => 15.5 * $this->_displacementFactor,
        ];
        $result = LcaStats::getWasteStats($group);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #6 add a powered misc device with estimate
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event->idevents,
            'estimate' => 4.56,
        ]);
        $expect = [
            'powered_waste' => 4 + 1.23,
            'unpowered_waste' => 5 + 4.56,
            'powered_footprint' => round((14.4 + (1.23 * $this->_ratioPowered)) * $this->_displacementFactor, 2),
            'unpowered_footprint' => round((15.5 + (4.56 * $this->_ratioUnpowered)) * $this->_displacementFactor, 2),
        ];
        $result = LcaStats::getWasteStats($group);
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }
    }
}
