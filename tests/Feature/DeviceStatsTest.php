<?php

namespace Tests\Feature;

use App\Device;
use App\DeviceBarrier;
use App\Category;
use App\Helpers\FootprintRatioCalculator;

use DB;
use Tests\TestCase;

class DeviceStatsTest extends TestCase
{
    private $_displacementFactor;

    public function setUp()
    {
        parent::setUp();
        $Device = new Device;
        $this->_displacementFactor = $Device->displacement;
    }

    /** @test */
    public function emission_ratios_with_unpowered_lca_data()
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
            'idcategories' => 46,
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
            'idcategories' => 50,
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

        $footprintRatioCalculator = new FootprintRatioCalculator();

        // a single powered non-misc device
        factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
        ]);
        $expect = round((14.4 / (4 + 0.0)), 1);
        $result = round($footprintRatioCalculator->calculateRatio(), 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");

        // add a powered misc device
        factory(Device::class)->states('fixed')->create([
            'category' => 46,
            'category_creation' => 46,
        ]);
        $result = round($footprintRatioCalculator->calculateRatio(), 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");

        // add an upowered non-misc device
        factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
        ]);
        $expect = round(((14.4 + 15.5) / ((4 + 0.0) + (5 + 0.0))), 1);
        $result = round($footprintRatioCalculator->calculateRatio(), 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");

        // add an upowered misc device
        factory(Device::class)->states('fixed')->create([
            'category' => 50,
            'category_creation' => 50,
        ]);
        $result = round($footprintRatioCalculator->calculateRatio(), 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");

        // calculate ratio for powered only
        $expect = round((14.4 / (4 + 0.0)), 1);
        $result = round($footprintRatioCalculator->calculateRatioPowered(), 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");

        // calculate ratio for unpowered only
        $expect = round((15.5 / (5 + 0.0)), 1);
        $result = round($footprintRatioCalculator->calculateRatioUnpowered(), 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");
    }

    /** WASTE TESTS */

    /** @test */
    public function a_fixed_device_has_ewaste_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create();

        $ewasteDiverted = $device->ewasteDiverted();

        $this->assertGreaterThan(0, $ewasteDiverted);
    }

    /** @test */
    public function a_powered_misc_device_without_estimate_has_no_waste_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 46,
            'category_creation' => 46,
        ]);
        $result = $device->ewasteDiverted();
        $this->assertEquals(0, $result, "ewasteDiverted = 0");
    }

    /** @test */
    public function a_powered_misc_device_with_estimate_has_waste_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 46,
            'category_creation' => 46,
            'estimate' => 123
        ]);
        $result = $device->ewasteDiverted();
        $this->assertEquals(123, $result, "ewasteDiverted = 123");
    }

    /** @test */
    public function a_powered_non_misc_device_has_waste_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 333,
            'category_creation' => 333,
        ]);
        $result = $device->ewasteDiverted();
        $this->assertEquals(3, $result, "ewasteDiverted = 3");
    }

    /** @test */
    public function a_powered_non_fixed_device_has_no_waste_diverted()
    {
        $device = factory(Device::class)->states('repairable')->create([
            'category' => 333,
            'category_creation' => 333,
        ]);
        $result = $device->ewasteDiverted();
        $this->assertEquals(0, $result, "ewasteDiverted = 0");

        $device = factory(Device::class)->states('end')->create([
            'category' => 333,
            'category_creation' => 333,
        ]);
        $result = $device->ewasteDiverted();
        $this->assertEquals(0, $result, "ewasteDiverted = 0");

        $device = factory(Device::class)->states('unknown')->create([
            'category' => 333,
            'category_creation' => 333,
        ]);
        $result = $device->ewasteDiverted();
        $this->assertEquals(0, $result, "ewasteDiverted = 0");
    }

    /** @test */
    public function an_upowered_misc_device_without_estimate_has_no_waste_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 50,
            'category_creation' => 50,
        ]);
        $result = $device->unpoweredWasteDiverted();
        $this->assertEquals(0, $result, "unpoweredWasteDiverted = 0");
    }

    /** @test */
    public function an_upowered_misc_device_with_estimate_has_waste_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 50,
            'category_creation' => 50,
            'estimate' => 456
        ]);
        $result = $device->unpoweredWasteDiverted();
        $this->assertEquals(456, $result, "unpoweredWasteDiverted = 456");
    }

    /** @test */
    public function an_upowered_non_misc_device_has_waste_diverted()
    {
        DB::table('categories')->where('idcategories', 555)->update([
            'weight' => 5,
        ]);
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 555,
            'category_creation' => 555,
        ]);
        $result = $device->unpoweredWasteDiverted();
        $this->assertEquals(5, $result, "unpoweredWasteDiverted = 5");
    }

    /** CO2 TESTS */

    /** @test */
    public function a_fixed_device_has_co2_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create();

        $emissionRatio = 20;
        $displacementFactor = 0.5;

        $co2Diverted = $device->co2Diverted($emissionRatio, $displacementFactor);

        $this->assertGreaterThan(0, $co2Diverted);
    }

    /** @test */
    public function an_unpowered_non_fixed_device_has_no_waste_diverted()
    {
        $device1 = factory(Device::class)->states('repairable')->create([
            'category' => 111,
            'category_creation' => 111,
        ]);
        $device2 = factory(Device::class)->states('end')->create([
            'category' => 222,
            'category_creation' => 222,
        ]);
        $device3 = factory(Device::class)->states('unknown')->create([
            'category' => 333,
            'category_creation' => 333,
        ]);

        $emissionRatio = $this->_getEmissionRatio();
        $displacement = $this->_getDisplacementFactor();

        $result = $device1->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals(0, $result, "co2Diverted = 0");

        $result = $device2->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals(0, $result, "co2Diverted = 0");

        $result = $device3->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals(0, $result, "co2Diverted = 0");
    }

    /** @test */
    public function a_powered_misc_device_with_no_estimate_has_no_c02_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 46,
            'category_creation' => 46,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $displacement = $this->_getDisplacementFactor();
        $result = $device->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals(0, $result, "co2Diverted = 0");
    }

    /** @test */
    public function a_powered_misc_device_with_estimate_has_c02_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 46,
            'category_creation' => 46,
            'estimate' => 123,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $displacement = $this->_getDisplacementFactor();
        $expect = 123 * $emissionRatio;
        $result = $device->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals($expect, $result, "co2Diverted = $expect");
    }

    /** @test */
    public function a_powered_non_misc_device_has_c02_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 333,
            'category_creation' => 333,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $displacement = $this->_getDisplacementFactor();
        $expect = $device->deviceCategory->footprint * $displacement;
        $result = $device->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals($expect, $result, "co2Diverted = $expect");
    }

    /** @test */
    public function an_upowered_misc_device_with_no_estimate_has_no_c02_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 50,
            'category_creation' => 50,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $displacement = $this->_getDisplacementFactor();
        $result = $device->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals(0, $result, "co2Diverted = 0");
    }

    /** @test */
    public function an_upowered_misc_device_with_estimate_has_c02_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 50,
            'category_creation' => 50,
            'estimate' => 456,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $displacement = $this->_getDisplacementFactor();
        $expect = 456 * $emissionRatio;
        $result = $device->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals($expect, $result, "co2Diverted = $expect");
    }

    /** @test */
    public function an_upowered_non_misc_device_has_c02_diverted()
    {
        DB::table('categories')->where('idcategories', 555)->update([
            'footprint' => 5.5,
        ]);
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 555,
            'category_creation' => 555,
        ]);
        $emissionRatio = $this->_getEmissionRatio();
        $displacement = $this->_getDisplacementFactor();
        $expect = $device->deviceCategory->footprint * $displacement;
        $result = $device->co2Diverted($emissionRatio, $displacement);
        $this->assertEquals($expect, $result, "co2Diverted = $expect");
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
