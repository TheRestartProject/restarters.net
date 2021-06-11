<?php

namespace Tests\Feature;

use App\Device;

use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeviceStatsTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

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
    public function a_fixed_device_has_ewaste_diverted()
    {
        $device = factory(Device::class)->states('fixed')->create();

        $ewasteDiverted = $device->ewasteDiverted();

        $this->assertGreaterThan(0, $ewasteDiverted);
    }
}
