<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use App\Party;
use DB;
use Tests\TestCase;

class EventStatsTest extends TestCase
{

    /** @test */
    public function an_event_with_no_devices_has_no_co2()
    {
        $event = factory(Party::class)->create();

        $eventStats = $event->getEventStats(0.5);
        $eventCo2 = $eventStats['co2'];

        $expectedCo2 = 0;

        $this->assertEquals($expectedCo2, $eventCo2);
    }

    /** @test */
    public function an_event_with_a_fixed_device_has_some_co2()
    {
        $event = factory(Party::class)->create();
        $device = factory(Device::class)->states('fixed', 'mobile')->create([
            'event' => $event->idevents,
        ]);

        $displacementRatio = 0.5;

        $eventStats = $event->getEventStats($displacementRatio);
        $eventCo2 = $eventStats['co2'];

        $expectedCo2 = round($device->deviceCategory->footprint) * $displacementRatio;

        $this->assertEquals($expectedCo2, $eventCo2);
    }

    /** @test */
    public function an_event_with_some_devices_but_none_fixed_has_some_co2()
    {
        $event = factory(Party::class)->create();
        factory(Device::class)->states('repairable', 'mobile')->create([
            'event' => $event->idevents,
        ], 5);
        factory(Device::class)->states('end', 'mobile')->create([
            'event' => $event->idevents,
        ], 5);

        $displacementRatio = 0.5;

        $eventStats = $event->getEventStats($displacementRatio);
        $eventCo2 = $eventStats['co2'];

        $expectedCo2 = 0;

        $this->assertEquals($expectedCo2, $eventCo2);
    }

    /** @test */
    public function an_event_with_mixed_devices_has_correct_stats()
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
        $idevents = $event->idevents;
        $Calculator = new \App\Helpers\FootprintRatioCalculator;

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => $idevents,
        ]);
        $expect = [
            'co2' => (14.4 * $displacementFactor),
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
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $result = $event->getEventStats($emissionRatio);
        logger(print_r($result, 1));
        $this->assertIsArray($result);
        $this->assertEquals(14, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $idevents,
        ]);
        $event = $event->findOrFail($idevents);
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_powered'] += 1;
        $result = $event->getEventStats($emissionRatio);
        logger(print_r($result, 1));
        $this->assertIsArray($result);
        $this->assertEquals(14, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => $idevents,
        ]);
        $event = $event->findOrFail($idevents);
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $event->getEventStats($emissionRatio);
        logger(print_r($result, 1));
        $this->assertIsArray($result);
        $this->assertEquals(14, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $idevents,
        ]);
        $event = $event->findOrFail($idevents);
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $event->getEventStats($emissionRatio);
        logger(print_r($result, 1));
        $this->assertIsArray($result);
        $this->assertEquals(14, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #5 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $idevents,
            'estimate' => 1.5,
        ]);
        $event = $event->findOrFail($idevents);
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $expect['unpowered_waste'] += 1.5;
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $event->getEventStats($emissionRatio);
        logger(print_r($result, 1));
        $this->assertIsArray($result);
        $this->assertEquals(14, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #6 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $idevents,
            'estimate' => 1.6,
        ]);
        $event = $event->findOrFail($idevents);
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['devices_powered'] += 1;
        $expect['ewaste'] += 1.6;
        $expect['co2'] = ((1.6 * $emissionRatio) * $displacementFactor) + (14.4 * $displacementFactor);
        $result = $event->getEventStats($emissionRatio);
        logger(print_r($result, 1));
        $this->assertIsArray($result);
        $this->assertEquals(14, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        logger('=========================== EVENTSTATSTEST DEBUG INFO BEGINS =======================================');
        logger('
**** FootprintRatioCalculator->calculateRatio() ****
select @ratio := sum(`categories`.`footprint`) / sum(`categories`.`weight` + 0.0) from `devices`, `categories` where  `categories`.`idcategories` = `devices`.`category` and `devices`.`repair_status` = 1 and categories.idcategories != 46
sum of footprints IS NOT multiplied by displacement factor
');
        logger("emissionRatio calculated by FootprintRatioCalculator->calculateRatio()=$emissionRatio");

        logger('
**** Party->getEventStats($emissionRatio) ****
uses Device->co2Diverted($emissionRatio, $Device->displacement)
where $emissionRatio param provided by FootprintRatioCalculator->calculateRatio()
');
        logger('getEventStats() for idevents=' . $event->idevents);
        logger(print_r($result, 1));

        logger('
CALLED IN
/var/www/html/restarters.dev/app/Group.php
  254,28:             $stats = $event->getEventStats($emissionRatio);
  274,33:             $eventStats = $event->getEventStats($emissionRatio);
/var/www/html/restarters.dev/app/Party.php
  866,27:         return round($this->getEventStats($emissionRatio)[\'ewaste\'], 2);
/var/www/html/restarters.dev/app/Http/Controllers/ApiController.php
  66,29:         $eventStats = $event->getEventStats($emissionRatio);
/var/www/html/restarters.dev/app/Http/Controllers/DeviceController.php
  391,34:         $return[\'stats\'] = $event->getEventStats($emissionRatio);
  523,28:             $stats = $event->getEventStats($emissionRatio);
  564,32:                 $stats = $event->getEventStats($emissionRatio);
/var/www/html/restarters.dev/app/Http/Controllers/OutboundController.php
  41,37:                 $eventStats = $event->getEventStats($EmissionRatio);
/var/www/html/restarters.dev/app/Http/Controllers/PartyController.php
  89,35:         $thisone[\'stats\'] = $event->getEventStats($emissionRatio);
  739,30:             \'stats\' => $event->getEventStats($this->EmissionRatio),
  888,29:         $eventStats = $event->getEventStats($emissionRatio);
  1467,53:                  \'co2_emissions_prevented\' => $party->getEventStats($emissionRatio)[\'co2\'],
/var/www/html/restarters.dev/app/Http/Controllers/API/EventController.php
  75,33:             $eventStats = $party->getEventStats($emissionRatio);
');
        logger('=========================== EVENTSTATSTEST DEBUG INFO ENDS =======================================');
    }
}
