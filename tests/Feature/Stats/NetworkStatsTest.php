<?php

namespace Tests\Feature\Stats;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\Network;
use App\Party;
use App\User;
use Tests\Feature\Stats\StatsTestCase;

/**
 * Regression cover for the network stats over-reporting bug (RES-2062).
 *
 * A January change rewrote Network::stats() as raw aggregate SQL that
 *   - ignored per-device `estimate` values (so misc devices contributed nothing), and
 *   - derived CO2e from weight x emission-ratio instead of the category `footprint`,
 * which significantly over-reported waste and CO2e for networks.
 *
 * Network::stats() now sums the canonical per-event calculation via
 * Group::bulkGroupStats(). These tests assert the aggregate is correct across
 * multiple groups, that misc-device estimates ARE counted, that CO2e uses the
 * footprints, and that the invited tally (loaded via withCount in bulkGroupStats)
 * still matches counting the relation directly.
 */
class NetworkStatsTest extends StatsTestCase
{
    /** @test */
    public function a_network_with_no_groups_has_empty_stats(): void
    {
        $network = Network::factory()->create();

        $expect = \App\Group::getGroupStatsArrayKeys();
        $expect['parties'] = $expect['parties'] ?? 0;

        $this->assertEquals($expect, $network->stats());
    }

    /** @test */
    public function network_stats_aggregate_misc_estimates_and_footprints_across_groups(): void
    {
        $network = Network::factory()->create();

        // Two groups, each with one past event, so we exercise aggregation across
        // groups (and the lazy() chunked event load) rather than a single group.
        $group1 = Group::factory()->create();
        $event1 = Party::factory()->moderated()->create([
            'event_start_utc' => '2000-01-01T10:15:05+05:00',
            'event_end_utc' => '2000-01-0113:45:05+05:00',
            'group' => $group1->idgroups,
        ]);

        $group2 = Group::factory()->create();
        $event2 = Party::factory()->moderated()->create([
            'event_start_utc' => '2000-01-01T10:15:05+05:00',
            'event_end_utc' => '2000-01-0113:45:05+05:00',
            'group' => $group2->idgroups,
        ]);

        $network->addGroup($group1);
        $network->addGroup($group2);

        // Truncates devices/categories then seeds categories with known weights/footprints.
        $this->_setupCategoriesWithUnpoweredWeights();

        // --- group 1 / event 1 ---
        // Powered non-misc fixed device: contributes its footprint (14.4) and weight (4).
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredNonMisc,
            'category_creation' => $this->_idPoweredNonMisc,
            'event' => $event1->idevents,
        ]);
        // Powered MISC fixed device WITH an estimate: the broken SQL ignored this entirely.
        // It must contribute estimate to waste and estimate x ratioPowered to CO2e.
        Device::factory()->fixed()->create([
            'category' => $this->_idPoweredMisc,
            'category_creation' => $this->_idPoweredMisc,
            'event' => $event1->idevents,
            'estimate' => 2.0,
        ]);
        // Unpowered MISC fixed device WITH an estimate.
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredMisc,
            'category_creation' => $this->_idUnpoweredMisc,
            'event' => $event1->idevents,
            'estimate' => 3.0,
        ]);

        // --- group 2 / event 2 ---
        // Unpowered non-misc fixed device: contributes its footprint (15.5) and weight (5).
        Device::factory()->fixed()->create([
            'category' => $this->_idUnpoweredNonMisc,
            'category_creation' => $this->_idUnpoweredNonMisc,
            'event' => $event2->idevents,
        ]);

        // A couple of invitees (status <> 1 counts as invited) so the invited tally,
        // which bulkGroupStats loads via withCount('allInvited'), is non-zero.
        foreach ([$event1, $event2] as $event) {
            EventsUsers::create([
                'event' => $event->idevents,
                'user' => User::factory()->create()->getKey(),
                'status' => 0,
                'role' => 4,
                'full_name' => null,
            ]);
        }

        $expect = \App\Group::getGroupStatsArrayKeys();
        $expect['parties'] = 2;
        $expect['hours_volunteered'] = 42; // 21 per event with no volunteers recorded
        $expect['fixed_devices'] = 4;
        $expect['fixed_powered'] = 2;
        $expect['fixed_unpowered'] = 2;
        $expect['devices_powered'] = 2;
        $expect['devices_unpowered'] = 2;
        // Misc devices carried an estimate, so no_weight_* stays 0.
        $expect['no_weight_powered'] = 0;
        $expect['no_weight_unpowered'] = 0;
        // Waste: footprint-weighted device weight + misc estimates.
        $expect['waste_powered'] = 4 + 2.0;
        $expect['waste_unpowered'] = 5 + 3.0;
        $expect['waste_total'] = $expect['waste_powered'] + $expect['waste_unpowered'];
        // CO2e: footprints for non-misc, estimate x ratio for the misc estimates.
        $expect['co2_powered'] = (14.4 + (2.0 * $this->_ratioPowered)) * $this->_displacementFactor;
        $expect['co2_unpowered'] = (15.5 + (3.0 * $this->_ratioUnpowered)) * $this->_displacementFactor;
        $expect['co2_total'] = $expect['co2_powered'] + $expect['co2_unpowered'];

        $result = $network->stats();

        // Ground-truth invited tally by counting the relation directly, proving the
        // withCount-loaded all_invited_count matches.
        $expectedInvited = 0;
        foreach ($network->groups as $group) {
            foreach (Party::where('group', $group->idgroups)->get() as $event) {
                $expectedInvited += $event->allInvited()->count();
            }
        }
        $this->assertGreaterThan(0, $expectedInvited, 'Test should exercise a non-zero invited count');
        $expect['invited'] = $expectedInvited;

        $floatKeys = ['co2_powered', 'co2_unpowered', 'co2_total', 'waste_powered', 'waste_unpowered', 'waste_total'];
        foreach ($expect as $k => $v) {
            $this->assertArrayHasKey($k, $result, "Missing array key $k");
            if (in_array($k, $floatKeys, true)) {
                $this->assertEqualsWithDelta($v, $result[$k], 0.0001, "Wrong value for $k");
            } else {
                $this->assertEquals($v, $result[$k], "Wrong value for $k");
            }
        }
    }
}
