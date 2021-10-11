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

        $idevents = 1;

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => $idevents,
        ]);
        $this->assertEquals($idevents, $device->deviceEvent->idevents);
        $expect = [
            'co2' => (14.4 * $displacement_factor),
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
        $result = $device->deviceEvent->getEventStats();
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
        $this->assertEquals($idevents, $device->deviceEvent->idevents);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_powered'] += 1;
        $result = $device->deviceEvent->getEventStats();
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
        $this->assertEquals($idevents, $device->deviceEvent->idevents);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->getEventStats();
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
        $this->assertEquals($idevents, $device->deviceEvent->idevents);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->getEventStats();
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
        $this->assertEquals($idevents, $device->deviceEvent->idevents);
        $expect['unpowered_waste'] += 1.5;
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->getEventStats();
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
        $this->assertEquals($idevents, $device->deviceEvent->idevents);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['devices_powered'] += 1;
        $expect['ewaste'] += 1.6;
        $expect['co2'] = round(((1.6 * $emission_ratio) * $displacement_factor) + (14.4 * $displacement_factor),2);
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        $this->assertEquals(14, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }
    }
}
