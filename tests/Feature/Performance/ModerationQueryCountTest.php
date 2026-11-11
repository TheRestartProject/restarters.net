<?php

namespace Tests\Feature\Performance;

use App\Group;
use App\Network;
use App\Party;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sentry flagged N+1 queries on the moderation and network stats endpoints.  These tests pin that the number
 * of queries does not grow with the number of groups/events.
 */
class ModerationQueryCountTest extends TestCase
{
    private int $eventSeq = 0;

    private function countQueries(callable $fn): int
    {
        // Warm caches (emission ratios etc.) so the measured run reflects steady state.
        $fn();

        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });
        $fn();

        return $count;
    }

    private function addGroupsWithEvents(Network $network, int $groups, int $eventsPerGroup, bool $approvedGroups = true, bool $past = false): array
    {
        $ids = [];
        for ($i = 0; $i < $groups; $i++) {
            $group = Group::factory()->create(['approved' => $approvedGroups]);
            $network->addGroup($group);
            for ($j = 0; $j < $eventsPerGroup; $j++) {
                $ids[] = Party::factory()->create([
                    'group' => $group->idgroups,
                    'approved' => $past,
                    'event_start_utc' => now()->{$past ? 'subDays' : 'addDays'}(10 + $this->eventSeq)->toIso8601String(),
                    'event_end_utc' => now()->{$past ? 'subDays' : 'addDays'}(10 + $this->eventSeq++)->addHours(2)->toIso8601String(),
                ])->idevents;
            }
        }

        return $ids;
    }

    /**
     * @story:EventController::moderateEventsv2
     */
    public function testModerateEventsQueryCountDoesNotGrow(): void
    {
        $user = User::factory()->networkCoordinator()->create(['api_token' => 'n1evt']);
        $network = Network::factory()->create();
        $network->addCoordinator($user);

        $ids = $this->addGroupsWithEvents($network, 2, 1);
        $small = $this->countQueries(function () use ($ids) {
            $json = $this->get('/api/v2/moderate/events?api_token=n1evt')->assertSuccessful()->json();
            $this->assertEquals($ids, array_column($json, 'id'));
        });

        $ids = array_merge($ids, $this->addGroupsWithEvents($network, 4, 1));
        $large = $this->countQueries(function () use ($ids) {
            $json = $this->get('/api/v2/moderate/events?api_token=n1evt')->assertSuccessful()->json();
            $this->assertEquals($ids, array_column($json, 'id'));
            $this->assertCount(6, $json);
            $this->assertFalse($json[0]['approved']);
            $this->assertArrayHasKey('group', $json[0]);
        });

        fwrite(STDERR, "moderate/events queries: N=2 -> $small, N=6 -> $large\n");
        $this->assertEquals($small, $large, 'moderate/events query count grows with the number of events');
    }

    /**
     * @story:GroupController::moderateGroupsv2
     */
    public function testModerateGroupsQueryCountDoesNotGrow(): void
    {
        $user = User::factory()->networkCoordinator()->create(['api_token' => 'n1grp']);
        $network = Network::factory()->create();
        $network->addCoordinator($user);

        $made = [];
        $make = function (int $n) use ($network, &$made) {
            for ($i = 0; $i < $n; $i++) {
                $group = Group::factory()->create(['approved' => false]);
                $network->addGroup($group);
                $made[] = $group->idgroups;
            }
        };

        $make(2);
        $small = $this->countQueries(function () use (&$made) {
            $json = $this->get('/api/v2/moderate/groups?api_token=n1grp')->assertSuccessful()->json();
            $this->assertEqualsCanonicalizing($made, array_column($json, 'id'));
        });

        $make(4);
        $large = $this->countQueries(function () use (&$made) {
            $json = $this->get('/api/v2/moderate/groups?api_token=n1grp')->assertSuccessful()->json();
            $this->assertEqualsCanonicalizing($made, array_column($json, 'id'));
            $this->assertCount(6, $json);
            $this->assertFalse($json[0]['approved']);
            $this->assertArrayHasKey('hosts', $json[0]);
            $this->assertArrayHasKey('networks', $json[0]);
        });

        fwrite(STDERR, "moderate/groups queries: N=2 -> $small, N=6 -> $large\n");
        $this->assertEquals($small, $large, 'moderate/groups query count grows with the number of groups');
    }

    /**
     * @story:NetworkController::stats
     */
    public function testNetworkStatsQueryCountDoesNotGrow(): void
    {
        $user = User::factory()->networkCoordinator()->create(['api_token' => 'n1net']);
        $network = Network::factory()->create();
        $network->addCoordinator($user);

        $this->addGroupsWithEvents($network, 2, 2, true, true);
        $small = $this->countQueries(function () use ($network) {
            $json = $this->get("/api/networks/{$network->id}/stats?api_token=n1net")->assertSuccessful()->json();
            $this->assertEquals(2, $json['groups']);
            $this->assertEquals(4, $json['parties']);
        });

        $this->addGroupsWithEvents($network, 4, 2, true, true);
        $large = $this->countQueries(function () use ($network) {
            $json = $this->get("/api/networks/{$network->id}/stats?api_token=n1net")->assertSuccessful()->json();
            $this->assertEquals(6, $json['groups']);
            $this->assertEquals(12, $json['parties']);
        });

        fwrite(STDERR, "network stats queries: N=2 -> $small, N=6 -> $large\n");
        $this->assertEquals($small, $large, 'network stats query count grows with the number of groups');
    }
}
