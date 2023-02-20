<?php

namespace Tests\Feature\Stats;

use App\Device;
use Tests\Feature\Stats\StatsTestCase;

class DeviceStatsTest extends StatsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->_setupCategoriesWithUnpoweredWeights();
    }

    public function emission_ratios_with_unpowered_lca_data()
    {
        // add a powered non-misc device
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
        ]);
        $expect = round((14.4 / (4 + 0.0)), 1);
        $result = round($this->_ratioPowered, 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");

        // add a powered misc device
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
        ]);
        $result = round($this->_ratioPowered, 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");

        // add an unpowered non-misc device
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
        ]);
        $expect = round(((14.4 + 15.5) / ((4 + 0.0) + (5 + 0.0))), 1);
        $result = round($this->_ratioPowered, 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");

        // add an unpowered misc device
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
        ]);
        $result = round($this->_ratioPowered, 1);
        $this->assertEquals($expect, $result, "calculateRatio = $expect");
    }

    /** WASTE TESTS */

    /** @test */
    public function an_unpowered_nonmisc_device_has_waste_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
        ]);
        $result = $device->uWasteDiverted();
        $this->assertEquals(5, $result);
    }

    /** @test */
    public function a_powered_misc_device_without_estimate_has_no_waste_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
        ]);
        $result = $device->eWasteDiverted();
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function a_powered_misc_device_with_estimate_has_waste_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'estimate' => 123,
        ]);
        $result = $device->eWasteDiverted();
        $this->assertEquals(123, $result);
    }

    /** @test */
    public function a_powered_non_misc_device_has_waste_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
        ]);
        $result = $device->eWasteDiverted();
        $this->assertEquals(4, $result);
    }

    /** @test */
    public function a_powered_non_fixed_device_has_no_waste_diverted()
    {
        $device = Device::factory()->repairable()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
        ]);
        $result = $device->eWasteDiverted();
        $this->assertEquals(0, $result);

        $device = Device::factory()->end()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
        ]);
        $result = $device->eWasteDiverted();
        $this->assertEquals(0, $result);

        $device = Device::factory()->create([
            'category' => 5,
            'category_creation' => 5,
            'repair_status' => 0,
        ]);
        $result = $device->eWasteDiverted();
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function an_unpowered_misc_device_without_estimate_has_no_waste_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
        ]);
        $result = $device->uWasteDiverted();
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function an_unpowered_misc_device_with_estimate_has_waste_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'estimate' => 456,
        ]);
        $result = $device->uWasteDiverted();
        $this->assertEquals(456, $result);
    }

    /** @test */
    public function an_unpowered_non_misc_device_has_waste_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
        ]);
        $result = $device->uWasteDiverted();
        $this->assertEquals(5, $result);
    }

    /** CO2 TESTS */

    /** @test */
    public function an_unpowered_non_fixed_device_has_no_waste_diverted()
    {
        $device1 = Device::factory()->repairable()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
        ]);
        $device2 = Device::factory()->end()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
        ]);
        $device3 = Device::factory()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
            'repair_status' => 0,
        ]);

        $emissionRatio = $this->_ratioPowered;

        $result = $device1->uCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals(0, $result);

        $result = $device2->uCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals(0, $result);

        $result = $device3->uCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function a_powered_misc_device_with_no_estimate_has_no_c02_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
        ]);
        $emissionRatio = $this->_ratioPowered;
        $result = $device->eCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function a_powered_misc_device_with_estimate_has_c02_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'estimate' => 123,
        ]);
        $emissionRatio = $this->_ratioPowered;
        $expect = (123 * $emissionRatio) * $this->_displacementFactor;
        $result = $device->eCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals($expect, $result);
    }

    /** @test */
    public function a_powered_non_misc_device_has_c02_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
        ]);
        $emissionRatio = $this->_ratioPowered;
        $expect = 14.4 * $this->_displacementFactor;
        $result = $device->eCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals($expect, $result);
    }

    /** @test */
    public function an_unpowered_misc_device_with_no_estimate_has_no_c02_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
        ]);
        $emissionRatio = $this->_ratioPowered;
        $result = $device->uCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function an_unpowered_misc_device_with_estimate_has_c02_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'estimate' => 456,
        ]);
        $emissionRatio = $this->_ratioPowered;
        $expect = (456 * $emissionRatio) * $this->_displacementFactor;
        $result = $device->uCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals($expect, $result);
    }

    /** @test */
    public function an_unpowered_non_misc_device_has_c02_diverted()
    {
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
        ]);
        $emissionRatio = $this->_ratioPowered;
        $expect = 15.5 * $this->_displacementFactor;
        $result = $device->uCO2Diverted($emissionRatio, $this->_displacementFactor);
        $this->assertEquals($expect, $result);
    }
}
