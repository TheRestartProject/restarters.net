<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\Group;
use App\Party;
use Carbon\Carbon;
use Tests\Feature\Stats\StatsTestCase;

class GroupStatsTest extends StatsTestCase
{
    /** @test */
    public function a_group_with_no_events_has_empty_stats()
    {
        $group = factory(Group::class)->create()->first();
        $expect = \App\Group::getGroupStatsArrayKeys();
        $this->assertEquals($expect, $group->getGroupStats());
    }

    /** @test */
    public function a_group_with_one_past_event_has_stats_for_that_event()
    {
        $group = factory(Group::class)->create();
        factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday()->toDateString(),
            'group' => $group->idgroups,
        ]);
        $expect = \App\Group::getGroupStatsArrayKeys();
        $expect['parties'] = 1;
        $expect['hours_volunteered'] = 21;
        $this->assertEquals($expect, $group->getGroupStats());

        // Get the stats via the web page.
        $rsp = $this->get('/group/stats/' . $group->idgroups);
        $rsp->assertSee('<h5>hours volunteered</h5>');
        $rsp->assertSee('<span class="largetext">21</span>');
    }

    /** @test */
    public function a_group_with_mixed_devices_has_correct_stats()
    {
        $group = factory(Group::class)->create();
        $event = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday()->toDateString(),
            'group' => $group->idgroups,
        ]);

        $this->_setupCategoriesWithUnpoweredWeights();

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event' => $event->idevents,
        ]);
        $expect = \App\Group::getGroupStatsArrayKeys();
        $expect['parties'] = 1;
        $expect['waste_total'] = 4;
        $expect['co2_powered'] = 14.4 * $this->_displacementFactor;
        $expect['waste_powered'] = 4;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['hours_volunteered'] = 21;
        $result = $group->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #2 add a powered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event->idevents,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['no_weight_powered']++;
        $result = $group->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => $event->idevents,
        ]);
        $expect['co2_unpowered'] = 15.5 * $this->_displacementFactor;
        $expect['waste_unpowered'] = 5;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $result = $group->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event->idevents,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['no_weight_unpowered']++;
        $result = $group->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #5 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event->idevents,
            'estimate' => 1.23,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['co2_powered'] = (14.4 + (1.23 * $this->_ratioPowered)) * $this->_displacementFactor;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_powered'] += 1.23;
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $result = $group->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #6 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event->idevents,
            'estimate' => 4.56,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['co2_unpowered'] = (15.5 + (4.56 * $this->_ratioUnpowered)) * $this->_displacementFactor;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_unpowered'] += 4.56;
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $result = $group->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #7 add an unpowered non-misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event->idevents,
            'estimate' => 7.89,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['co2_unpowered'] = (15.5 + ((4.56 + 7.89) * $this->_ratioUnpowered)) * $this->_displacementFactor;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_unpowered'] += 7.89;
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $result = $group->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }

    /** @test */
    public function two_groups_with_mixed_devices_have_correct_stats()
    {
        $group1 = factory(Group::class)->create();
        $event1 = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday()->toDateString(),
            'group' => $group1->idgroups,
        ]);

        $this->_setupCategoriesWithUnpoweredWeights();

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event' => $event1->idevents,
        ]);

        // #2 add a powered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event1->idevents,
        ]);

        // #3 add an unpowered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => $event1->idevents,
        ]);

        // #4 add an unpowered misc device without estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event1->idevents,
        ]);

        // #5 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event1->idevents,
            'estimate' => 123,
        ]);

        // #6 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event1->idevents,
            'estimate' => 456,
        ]);
        $expect = \App\Group::getGroupStatsArrayKeys();
        $expect['parties'] = 1;
        $expect['hours_volunteered'] = 21;
        $expect['fixed_devices'] = 6;
        $expect['fixed_powered'] = 3;
        $expect['fixed_unpowered'] = 3;
        $expect['devices_powered'] = 3;
        $expect['devices_unpowered'] = 3;
        $expect['no_weight_powered'] = 1;
        $expect['no_weight_unpowered'] = 1;
        $expect['co2_powered'] = (14.4 + (123 * $this->_ratioPowered)) * $this->_displacementFactor;
        $expect['co2_unpowered'] = (15.5 + (456 * $this->_ratioUnpowered)) * $this->_displacementFactor;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_powered'] = 4 + 123;
        $expect['waste_unpowered'] = 5 + 456;
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];

        $result = $group1->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        $group2 = factory(Group::class)->create();
        $event2 = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday()->toDateString(),
            'group' => $group2->idgroups,
        ]);
        $event3 = factory(Party::class)->states('moderated')->create([
            'event_date' => Carbon::yesterday()->toDateString(),
            'group' => $group2->idgroups,
        ]);

        // #1 add a powered non-misc device
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event' => $event2->idevents,
        ]);

        // #2 add a powered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event2->idevents,
            'estimate' => 1.23,
        ]);

        // #3 add an unpowered misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event3->idevents,
            'estimate' => 4.56,
        ]);

        // #7 add an unpowered non-misc device with estimate
        $device = factory(Device::class)->states('fixed')->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event3->idevents,
            'estimate' => 7.89,
        ]);

        $expect = \App\Group::getGroupStatsArrayKeys();
        $expect['parties'] = 2;
        $expect['hours_volunteered'] = 42;
        $expect['fixed_devices'] = 4;
        $expect['fixed_powered'] = 2;
        $expect['fixed_unpowered'] = 2;
        $expect['devices_powered'] = 2;
        $expect['devices_unpowered'] = 2;
        $expect['no_weight_powered'] = 0;
        $expect['no_weight_unpowered'] = 0;
        $expect['waste_powered'] = 4 + 1.23;
        $expect['waste_unpowered'] = 4.56 + 7.89;
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $expect['co2_powered'] = (14.4 + (1.23 * $this->_ratioPowered)) * $this->_displacementFactor;
        $expect['co2_unpowered'] = ((4.56 + 7.89) * $this->_ratioUnpowered) * $this->_displacementFactor;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $result = $group2->getGroupStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }
    }
}
