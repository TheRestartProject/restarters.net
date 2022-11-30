<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\Group;
use App\Party;
use App\Role;
use App\User;
use Tests\Feature\Stats\StatsTestCase;

class EventStatsTest extends StatsTestCase
{
    /** @test */
    public function an_event_with_no_devices_has_empty_stats()
    {
        $event = Party::factory()->create();
        $expect = \App\Party::getEventStatsArrayKeys();
        $expect['hours_volunteered'] = 21;
        $this->assertEquals($expect, $event->getEventStats());
    }

    /** @test */
    public function event_stats_with_both_powered_and_unpowered_devices()
    {
        $this->_setupCategoriesWithUnpoweredWeights();

        // #1 add a powered non-misc device
        $event = Party::factory()->create();
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event' => $event->idevents,
        ]);
        $expect = \App\Party::getEventStatsArrayKeys();
        $expect['co2_powered'] = 14.4 * $this->_displacementFactor;
        $expect['waste_powered'] = 4;
        $expect['co2_total'] += $expect['co2_powered'];
        $expect['waste_total'] += $expect['waste_powered'];
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['hours_volunteered'] = 21;
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #2 add a powered misc device without estimate
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
                                                                      'event' => $event->idevents,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['no_weight_powered']++;
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #3 add an unpowered non-misc device
        $device = Device::factory()->fixed()->create([
            'category' => 5,
            'category_creation' => 5,
            'event' => $event->idevents,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['co2_unpowered'] = 15.5 * $this->_displacementFactor;
        $expect['waste_unpowered'] = 5;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #4 add an unpowered misc device without estimate
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event->idevents,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['no_weight_unpowered']++;
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #5 add a powered misc device with estimate
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event->idevents,
            'estimate' => 1.23,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_powered']++;
        $expect['devices_powered']++;
        $expect['co2_powered'] = (14.4 + (1.23 * $this->_ratioPowered)) * $this->_displacementFactor;
        $expect['waste_powered'] += 1.23;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #6 add an unpowered misc device with estimate
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event->idevents,
            'estimate' => 4.56,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['co2_unpowered'] = (15.5 + (4.56 * $this->_ratioUnpowered)) * $this->_displacementFactor;
        $expect['waste_unpowered'] += 4.56;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // #7 add an unpowered non-misc device with estimate
        $device = Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event->idevents,
            'estimate' => 7.89,
        ]);
        $expect['fixed_devices']++;
        $expect['fixed_unpowered']++;
        $expect['devices_unpowered']++;
        $expect['co2_unpowered'] = (15.5 + ((4.56 + 7.89) * $this->_ratioUnpowered)) * $this->_displacementFactor;
        $expect['waste_unpowered'] += 7.89;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        $result = $device->deviceEvent->getEventStats();
        $this->assertIsArray($result);
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            $this->assertEquals($v, $result[$k], "Wrong value for $k => $v");
        }

        // Get the stats pages.
        $response = $this->get('/admin/stats/1');
        $response->assertSuccessful();
        $response->assertSee('<span class="title">7</span>');

        $response = $this->get('/admin/stats/2');
        $response->assertSuccessful();
        $response->assertSee('>27 kg<');

        // Get the wide stats page.
        $response = $this->get('/party/stats/' . $event->idevents . '/wide');
        $response->assertSuccessful();
        $response->assertSee('<span id="ewaste-diverted-value">23</span>');

        // Check that the search page loads.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/search');
        $response->assertSuccessful();
        $response->assertSee($event->venue);

        $response = $this->get("/search?fltr=1&parties[]={$event->idevents}");
        $response->assertSee('id="key-stats"');
        $response->assertSee($event->venue);
    }

    /** @test */
    public function event_stats_for_upcoming_event() {
        $this->_setupCategoriesWithUnpoweredWeights();

        $this->host = User::factory()->administrator()->create();
        $this->actingAs($this->host);

        $this->group = Group::factory()->create();
        $this->group->addVolunteer($this->host);
        $this->group->makeMemberAHost($this->host);

        // Create a future event.
        $idevents = $this->createEvent($this->group->idgroups, 'tomorrow');

        // Add a fixed device.
        $device = Device::factory()->fixed()->create([
                                                                      'category' => $this->_idUnpoweredMisc,
                                                                      'category_creation' => $this->_idUnpoweredMisc,
                                                                      'event' => $idevents,
                                                                      'estimate' => 7.89,
                                                                  ]);

        // Fixed device should show in stats on view event page.
        $response = $this->get('/party/view/' . $idevents);
        $props = $this->assertVueProperties($response, [
            [],
            [
                ':idevents' => $idevents
            ]
        ]);

        $stats = json_decode($props[1][':stats'], TRUE);
        self::assertEquals(1, $stats['fixed_devices']);

        // But it shouldn't show on the group stats page.
        $response = $this->get('/group/view/' . $this->group->idgroups);
        $props = $this->assertVueProperties($response, [
            [],
            [
                ':idgroups' => $this->group->idgroups
            ]
        ]);

        $stats = json_decode($props[1][':device-stats'], TRUE);
        self::assertEquals(0, $stats['fixed']);
        $stats = json_decode($props[1][':group-stats'], TRUE);
        self::assertEquals(0, $stats['co2_unpowered']);
    }
}
