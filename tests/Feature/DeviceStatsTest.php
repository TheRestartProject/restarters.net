<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use DB;
use Tests\TestCase;

class DeviceStatsTest extends TestCase
{
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

    /** @test */
    public function a_fixed_device_has_correct_stats()
    {
        $displacement_factor = env('DISPLACEMENT_VALUE');
        $emission_ratio = env('EMISSION_RATIO_POWERED');
        $id_misc_powered = env('MISC_CATEGORY_ID_POWERED');
        $id_misc_unpowered = env('MISC_CATEGORY_ID_UNPOWERED');

        DB::statement('SET foreign_key_checks=0');
        Category::truncate();
        DB::statement('SET foreign_key_checks=1');
        factory(Category::class)->create([
            'idcategories' => 4,
            'revision' => 1,
            'name' => 'powered non-misc',
            'powered' => 1,
            'weight' => 4,
            'footprint' => 14.4,
        ]);
        factory(Category::class)->create([
            'idcategories' => $id_misc_powered,
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
            'weight' => 0,
            'footprint' => 0,
        ]);
        factory(Category::class)->create([
            'idcategories' => $id_misc_unpowered,
            'revision' => 1,
            'name' => 'unpowered misc',
            'powered' => 0,
            'weight' => 0,
            'footprint' => 0,
        ]);

        // #1 add a single powered non-misc device
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement('SET foreign_key_checks=1');
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 4;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->co2Diverted($emission_ratio, $displacement_factor);
        $expect = (14.4 * $displacement_factor); // footprint * displacement
        $this->assertEquals($expect, $result);

        // #2 add a powered misc device
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement('SET foreign_key_checks=1');
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->co2Diverted($emission_ratio, $displacement_factor);
        $expect = 0;
        $this->assertEquals($expect, $result);

        // #3 add an unpowered non-misc device
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement('SET foreign_key_checks=1');
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 0; // category 5 has no weight
        $this->assertEquals($expect, $result);

        $result = $device->co2Diverted($emission_ratio, $displacement_factor);
        $expect = 0;
        $this->assertEquals($expect, $result);

        // #4 add an unpowered misc device
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement('SET foreign_key_checks=1');
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->co2Diverted($emission_ratio, $displacement_factor);
        $expect = 0;
        $this->assertEquals($expect, $result);

        // #5 add an unpowered misc device with estimate
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement('SET foreign_key_checks=1');
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'estimate' => 1,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 1;
        $this->assertEquals($expect, $result);

        $result = $device->co2Diverted($emission_ratio, $displacement_factor);
        $expect = 0;
        $this->assertEquals($expect, $result);

        // #6 add a powered misc device with estimate
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement('SET foreign_key_checks=1');
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'estimate' => 1.6,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 1.6;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->co2Diverted($emission_ratio, $displacement_factor);
        $expect = ((1.6 * $emission_ratio) * $displacement_factor); // ((weight * ratio) * displacement)
        $this->assertEquals($expect, $result);
    }

    /** Device->GetWeights() */

    /** @test */
    public function a_set_of_mixed_devices_have_correct_stats()
    {
        $displacement_factor = env('DISPLACEMENT_VALUE');
        $emission_ratio = env('EMISSION_RATIO_POWERED');
        $id_misc_powered = env('MISC_CATEGORY_ID_POWERED');
        $id_misc_unpowered = env('MISC_CATEGORY_ID_UNPOWERED');

        DB::statement('SET foreign_key_checks=0');
        Category::truncate();
        DB::statement('SET foreign_key_checks=1');
        factory(Category::class)->create([
            'idcategories' => 4,
            'revision' => 1,
            'name' => 'powered non-misc',
            'powered' => 1,
            'weight' => 4,
            'footprint' => 14.4,
        ]);
        factory(Category::class)->create([
            'idcategories' => $id_misc_powered,
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
            'weight' => 0,
            'footprint' => 0,
        ]);
        factory(Category::class)->create([
            'idcategories' => $id_misc_unpowered,
            'revision' => 1,
            'name' => 'unpowered misc',
            'powered' => 0,
            'weight' => 0,
            'footprint' => 0,
        ]);
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement('SET foreign_key_checks=1');

        $event = factory(\App\Party::class)->create();

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => $event->idevents,
        ]);
        $expect = [
            'total_weights' => 4,
            'ewaste' => 4,
            'unpowered_waste' => 0,
            'total_footprints' => 14.4 * $displacement_factor,
        ];
        $result = $device->getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $event->idevents,
        ]);
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
            'event' => $event->idevents,
        ]);
        $result = $device->getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $event->idevents,
        ]);
        $result = $device->getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #5 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $event->idevents,
            'estimate' => 1,
        ]);
        $expect['unpowered_waste'] += 1;
        $result = $device->getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        // #6 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $event->idevents,
            'estimate' => 1,
        ]);
        $expect['total_weights'] += 1;
        $expect['ewaste'] += 1;
        $expect['total_footprints'] = round((1 * $emission_ratio) + (14.4 * $displacement_factor),2);
        $result = $device->getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }
    }
}
