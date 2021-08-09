<?php

namespace Tests\Feature;

use App\Group;
use Carbon\Carbon;
use App\Category;
use App\Device;
use App\Party;
use DB;
use Tests\TestCase;

class GroupStatsTest extends TestCase
{
    /** @test */
    public function a_group_with_no_events_has_empty_stats()
    {
        $group = factory(Group::class)->create()->first();

        $expectedStats = [
            'pax' => 0,
            'hours' => 0,
            'parties' => 0,
            'co2' => 0,
            'waste' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
        ];
        $this->assertEquals($expectedStats, $group->getGroupStats(0.5));
    }

    /** @test */
    public function a_group_with_one_past_event_has_stats_for_that_event()
    {
        $group = factory(Group::class)->create();
        $event = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday(),
            'group' => $group->idgroups,
        ]);

        $expectedStats = [
            'pax' => 0,
            'hours' => 21,
            'parties' => 1,
            'co2' => 0,
            'waste' => 0,
            'unpowered_waste' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
            'ewaste' => 0,
        ];
        $this->assertEquals($expectedStats, $group->getGroupStats(0.5));
    }

    /** @test */
    public function a_group_with_mixed_devices_has_correct_stats()
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

        $Calculator = new \App\Helpers\FootprintRatioCalculator;

        $group = factory(Group::class)->create();
        $event = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday(),
            'group' => $group->idgroups,
            'wordpress_post_id' => 1,
        ]);

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect = [
            'parties' => 1,
            'waste' => 4,
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
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_powered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #5 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $event->idevents,
            'estimate' => 1.5,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['unpowered_waste'] += 1.5;
        $expect['waste'] += 1.5;
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #6 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $event->idevents,
            'estimate' => 1.6,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group->idgroups, $device->deviceEvent->theGroup->idgroups);
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['devices_powered'] += 1;
        $expect['ewaste'] += 1.6;
        $expect['waste'] += 1.6;
        $expect['co2'] = ((1.6 * $emissionRatio) * $displacementFactor) + (14.4 * $displacementFactor);
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }
    }

    /** @test */
    public function two_groups_with_mixed_devices_have_correct_stats()
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
            'name' => 'powered non-misc A',
            'powered' => 1,
            'weight' => 4,
            'footprint' => 14.4,
        ]);
        factory(Category::class)->create([
            'idcategories' => 6,
            'revision' => 1,
            'name' => 'powered non-misc B',
            'powered' => 1,
            'weight' => 6,
            'footprint' => 16.6,
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
            'idcategories' => 7,
            'revision' => 1,
            'name' => 'unpowered non-misc A',
            'powered' => 0,
            'weight' => 0,
            'footprint' => 0,
        ]);
        factory(Category::class)->create([
            'idcategories' => 7,
            'revision' => 1,
            'name' => 'unpowered non-misc B',
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

        $Calculator = new \App\Helpers\FootprintRatioCalculator;

        // GROUP 1
        $group1 = factory(Group::class)->create();
        $event = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday(),
            'group' => $group1->idgroups,
            'wordpress_post_id' => 1,
        ]);

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 4,
            'category_creation' => 4,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group1->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect = [
            'parties' => 1,
            'waste' => 4,
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
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group1->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_powered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group1->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        logger(print_r($result, 1));
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group1->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #5 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $event->idevents,
            'estimate' => 1.5,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group1->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['unpowered_waste'] += 1.5;
        $expect['waste'] += 1.5;
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #6 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $event->idevents,
            'estimate' => 1.6,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group1->idgroups, $device->deviceEvent->theGroup->idgroups);
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['devices_powered'] += 1;
        $expect['ewaste'] += 1.6;
        $expect['waste'] += 1.6;
        $expect['co2'] = ((1.6 * $emissionRatio) * $displacementFactor) + (14.4 * $displacementFactor);
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // GROUP 2
        $group2 = factory(Group::class)->create();
        $event = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday(),
            'group' => $group2->idgroups,
            'wordpress_post_id' => 2,
        ]);

        // #1 add a single powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 6,
            'category_creation' => 6,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group2->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect = [
            'parties' => 1,
            'waste' => 6,
            'co2' => (16.6 * $displacementFactor),
            'ewaste' => 6,
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
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #2 add a powered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group2->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_powered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 7,
            'category_creation' => 7,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group2->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $event->idevents,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group2->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['no_weight'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #5 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_unpowered,
            'category_creation' => $id_misc_unpowered,
            'event' => $event->idevents,
            'estimate' => 1.8,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group2->idgroups, $device->deviceEvent->theGroup->idgroups);
        $expect['unpowered_waste'] += 1.8;
        $expect['waste'] += 1.8;
        $expect['fixed_devices'] += 1;
        $expect['fixed_unpowered'] += 1;
        $expect['devices_unpowered'] += 1;
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }

        // #6 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $id_misc_powered,
            'category_creation' => $id_misc_powered,
            'event' => $event->idevents,
            'estimate' => 1.9,
        ]);
        $this->assertEquals($event->idevents, $device->deviceEvent->idevents);
        $this->assertEquals($group2->idgroups, $device->deviceEvent->theGroup->idgroups);
        $emissionRatio = round($Calculator->calculateRatio(), 2);
        $expect['fixed_devices'] += 1;
        $expect['fixed_powered'] += 1;
        $expect['devices_powered'] += 1;
        $expect['ewaste'] += 1.9;
        $expect['waste'] += 1.9;
        $expect['co2'] = ((1.9 * $emissionRatio) * $displacementFactor) + (16.6 * $displacementFactor);
        $result = $device->deviceEvent->theGroup->getGroupStats();
        $this->assertIsArray($result);
        $this->assertEquals(15, count($result));
        foreach ($expect as $k => $v) {
            $this->assertEquals($v, round($result[$k], 2), "Wrong value for $k => $v");
        }
    }
}
