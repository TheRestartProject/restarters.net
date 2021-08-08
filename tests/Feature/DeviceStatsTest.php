<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $displacementFactor = 0.5;
        $id_misc_powered = 46;
        $id_misc_unpowered = 50;

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

        $Calculator = new \App\Helpers\FootprintRatioCalculator;

        // #1 add a single powered non-misc device
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 4;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 4; // !! ERROR: SHOULD BE 0 !!
        $this->assertEquals($expect, $result);

        $emissionRatio = $Calculator->calculateRatio();
        $result = $device->co2Diverted($emissionRatio, $displacementFactor);
        $expect = (14.4 * $displacementFactor); // footprint * displacement
        $this->assertEquals($expect, $result);

        // #2 add a powered misc device
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
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

        $emissionRatio = $Calculator->calculateRatio();
        $result = $device->co2Diverted($emissionRatio, $displacementFactor);
        $expect = 0;
        $this->assertEquals($expect, $result);

        // #3 add an unpowered non-misc device
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 0;
        $this->assertEquals($expect, $result);

        $emissionRatio = $Calculator->calculateRatio();
        $result = $device->co2Diverted($emissionRatio, $displacementFactor);
        $expect = 0;
        $this->assertEquals($expect, $result);

        // #4 add an unpowered misc device
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
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

        $emissionRatio = $Calculator->calculateRatio();
        $result = $device->co2Diverted($emissionRatio, $displacementFactor);
        $expect = 0;
        $this->assertEquals($expect, $result);

        // #5 add an unpowered misc device with estimate
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
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

        $emissionRatio = $Calculator->calculateRatio();
        $result = $device->co2Diverted($emissionRatio, $displacementFactor);
        $expect = 0;
        $this->assertEquals($expect, $result);

        // #6 add a powered misc device with estimate
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'estimate' => 1.6,
        ]);
        $result = $device->ewasteDiverted();
        $expect = 1.6;
        $this->assertEquals($expect, $result);

        $result = $device->unpoweredWasteDiverted();
        $expect = 1.6; // !! ERROR: SHOULD BE 0 !!
        $this->assertEquals($expect, $result);

        $emissionRatio = $Calculator->calculateRatio();
        $result = $device->co2Diverted($emissionRatio, $displacementFactor);
        $expect = ((1.6 * $emissionRatio) * $displacementFactor); // ((weight * ratio) * displacement)
        $this->assertEquals($expect, $result);
    }

    /** Device->GetWeights() */
    /** @test */
    public function a_set_of_mixed_devices_have_correct_stats()
    {
        $displacementFactor = 0.5;
        $id_misc_powered = 46;
        $id_misc_unpowered = 50;

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
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        \App\DeviceBarrier::truncate();
        DB::statement("SET foreign_key_checks=1");

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
            'total_footprints' => 14.4 * $displacementFactor,
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
        $emissionRatio = (14.4 * $displacementFactor) / (4 + 0.0); // see below for explanation
        $expect['total_weights'] += 1;
        $expect['ewaste'] += 1;
        $expect['total_footprints'] = (1 * $emissionRatio) + (14.4 * $displacementFactor);
        $result = $device->getWeights();
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[0]->{$k}, 2), "Wrong value for $k => $v");
        }

        logger('=========================== DEVICESTATSTEST DEBUG INFO BEGINS =======================================');
        logger('
**** Device->getWeights() ****
select @ratio := ((sum(`categories`.`footprint`) * :displacement1) / sum(`categories`.`weight` + 0.0)) from `devices`, `categories` where `categories`.`idcategories` = `devices`.`category` and `devices`.`repair_status` = 1 and categories.idcategories != 46
uses own ratio calculation where sum of footprints IS multiplied by displacement factor');
        logger("emissionRatio calculated by Device->getWeights()=$emissionRatio");
        logger('Device->getWeights() for idevents=' . $event->idevents);
        logger(print_r($result, 1));
        logger('
CALLED IN
/var/www/html/restarters.dev/app/Helpers/Fixometer.php
  786,43:             $stats[\'co2Total\'] = $Device->getWeights();
/var/www/html/restarters.dev/app/Http/Controllers/AdminController.php
  25,29:         $weights = $Device->getWeights();
  51,29:         $weights = $Device->getWeights();
  113,30:         $co2Total = $Device->getWeights();
/var/www/html/restarters.dev/app/Http/Controllers/ApiController.php
  33,30:         $co2Total = $Device->getWeights();
/var/www/html/restarters.dev/app/Http/Controllers/ExportController.php
  35,29:         $weights = $Device->getWeights();
/var/www/html/restarters.dev/app/Http/Controllers/GroupController.php
  40,29:         $weights = $Device->getWeights();
  368,29:         $weights = $Device->getWeights($group->idgroups);
  1147,29:         $weights = $Device->getWeights($group->idgroups);
/var/www/html/restarters.dev/app/Http/Controllers/PartyController.php
  56,29:         $weights = $Device->getWeights();
  434,30:         $co2Total = $Device->getWeights();
/var/www/html/restarters.dev/app/Http/Controllers/SearchController.php
  25,29:         $weights = $Device->getWeights();
/var/www/html/restarters.dev/app/Http/Controllers/UserController.php
  432,29:         $weights = $Device->getWeights();
  516,29:         $weights = $Device->getWeights();
');

        logger('=========================== DEVICESTATSTEST DEBUG INFO ENDS =======================================');
    }
}
