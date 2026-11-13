<?php

namespace Tests\Feature\Performance;

use App\Group;
use App\GroupTags;
use App\Network;
use App\EventsUsers;
use App\Party;
use App\UserGroups;
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

        $this->assertEquals($small, $large, 'network stats query count grows with the number of groups');
    }

    /**
     * bulkGroupStats pages past events in chunks of 200.  Each chunk must not also load the events' groups
     * (and their networks/tags/member counts), which Party eager-loads by default.
     *
     * @story:NetworkController::stats
     */
    public function testNetworkStatsDoesNotLoadGroupsPerChunk(): void
    {
        $user = User::factory()->networkCoordinator()->create(['api_token' => 'n1chunk']);
        $network = Network::factory()->create();
        $network->addCoordinator($user);

        $groups = [];
        for ($i = 0; $i < 3; $i++) {
            $groups[$i] = Group::factory()->create();
            $network->addGroup($groups[$i]);
        }

        $rows = [];
        $start = now()->subDays(30)->toDateTimeString();
        $end = now()->subDays(30)->addHours(2)->toDateTimeString();
        for ($i = 0; $i < 450; $i++) {
            $rows[] = [
                'group' => $groups[$i % 3]->idgroups,
                'approved' => true,
                'location' => 'London',
                'venue' => 'Venue',
                'timezone' => 'Europe/London',
                'event_start_utc' => $start,
                'event_end_utc' => $end,
                'free_text' => 'x',
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('events')->insert($chunk);
        }

        $tables = [];
        $count = 0;
        DB::listen(function ($q) use (&$tables, &$count) {
            $count++;
            if (preg_match('/from `(groups|group_network|grouptags_groups)`/', $q->sql, $m)) {
                $tables[] = $m[1];
            }
        });

        $json = $this->get("/api/networks/{$network->id}/stats?api_token=n1chunk")->assertSuccessful()->json();

        $this->assertEquals(450, $json['parties']);
        $this->assertEquals(3, $json['groups']);
        // At most the single up-front query for the network's groups (via group_network).
        $this->assertLessThanOrEqual(1, count($tables), 'groups loaded per chunk: ' . implode(',', $tables));
    }

    /**
     * @story:GroupController::moderateGroupsv2
     */
    public function testModeratedGroupsShowTheirNextApprovedEvent(): void
    {
        // The next events are now fetched for all the groups at once; each group must still get its own.
        $user = User::factory()->networkCoordinator()->create(['api_token' => 'n1next']);
        $network = Network::factory()->create();
        $network->addCoordinator($user);

        $withEvents = Group::factory()->create(['approved' => false]);
        $without = Group::factory()->create(['approved' => false]);
        $network->addGroup($withEvents);
        $network->addGroup($without);

        $event = function (int $days, bool $approved) use ($withEvents) {
            return Party::factory()->create([
                'group' => $withEvents->idgroups,
                'approved' => $approved,
                'event_start_utc' => now()->addDays($days)->toIso8601String(),
                'event_end_utc' => now()->addDays($days)->addHours(2)->toIso8601String(),
            ]);
        };

        $event(-3, true);                     // past
        $event(1, false);                     // sooner, but not approved
        $event(2, true)->delete();            // sooner, but deleted
        $next = $event(5, true);
        $event(10, true);                     // later

        $json = collect($this->get('/api/v2/moderate/groups?api_token=n1next')->assertSuccessful()->json())->keyBy('id');

        $this->assertEquals($next->idevents, $json[$withEvents->idgroups]['next_event']['id']);
        $this->assertArrayNotHasKey('next_event', $json[$without->idgroups]);
    }

    /**
     * @story:NetworkController::stats
     */
    public function testTagFilteredNetworkStats(): void
    {
        $user = User::factory()->networkCoordinator()->create(['api_token' => 'n1tag']);
        $network = Network::factory()->create();
        $network->addCoordinator($user);
        $tag = GroupTags::factory()->create();

        $tagged = $this->addGroupsWithEvents($network, 1, 2, true, true);
        $archived = $this->addGroupsWithEvents($network, 1, 1, true, true);
        $this->addGroupsWithEvents($network, 1, 3, true, true);

        $taggedGroup = Party::find($tagged[0])->group;
        $archivedGroup = Party::find($archived[0])->group;
        Group::find($taggedGroup)->group_tags()->attach($tag->id);
        Group::find($archivedGroup)->group_tags()->attach($tag->id);
        Group::where('idgroups', $archivedGroup)->update(['archived_at' => now()]);

        $json = $this->get("/api/networks/{$network->id}/stats?api_token=n1tag&group_tag={$tag->id}")->assertSuccessful()->json();

        // An archived group's past events still count; the group itself doesn't.
        $this->assertEquals(3, $json['parties']);
        $this->assertEquals(1, $json['groups']);
    }

    /**
     * The moderation lists fetch the viewer's own state for every item at once; it must match what the single
     * event and group endpoints say.
     *
     * @story:EventController::moderateEventsv2
     * @story:GroupController::moderateGroupsv2
     */
    public function testModerationListsShowTheViewersOwnState(): void
    {
        $user = User::factory()->networkCoordinator()->create(['api_token' => 'n1own']);
        $network = Network::factory()->create();
        $network->addCoordinator($user);
        [$going, $invited, $neither] = $this->addGroupsWithEvents($network, 3, 1);
        EventsUsers::create(['event' => $going, 'user' => $user->id, 'status' => '1', 'role' => 4]);
        // An invite hash that starts with 1, which MySQL would take for 1 if compared as a number.
        EventsUsers::create(['event' => $invited, 'user' => $user->id, 'status' => '1a3f9c0invitehash', 'role' => 4]);
        // Rows from before invitations were stored have no status: neither going nor invited.
        EventsUsers::create(['event' => $neither, 'user' => $user->id, 'status' => null, 'role' => 4]);

        $member = Group::factory()->create(['approved' => false]);
        $asked = Group::factory()->create(['approved' => false]);
        $other = Group::factory()->create(['approved' => false]);
        foreach ([$member, $asked, $other] as $group) {
            $network->addGroup($group);
        }
        UserGroups::create(['group' => $member->idgroups, 'user' => $user->id, 'status' => '1', 'role' => 4]);
        UserGroups::create(['group' => $asked->idgroups, 'user' => $user->id, 'status' => 'group-hash', 'role' => 4]);

        $events = collect($this->get('/api/v2/moderate/events?api_token=n1own')->assertSuccessful()->json())->keyBy('id');
        foreach ([$going, $invited, $neither] as $id) {
            $single = $this->get("/api/v2/events/$id?api_token=n1own")->json('data');
            $this->assertSame($single['attending'], $events[$id]['attending'], "attending, event $id");
            $this->assertSame($single['invitation_pending'], $events[$id]['invitation_pending'], "invitation_pending, event $id");
            $this->assertSame($single['shareable_link'] ?? null, $events[$id]['shareable_link'] ?? null, "shareable_link, event $id");
        }
        $this->assertSame([true, false, false], [$events[$going]['attending'], $events[$invited]['attending'], $events[$neither]['attending']]);
        $this->assertSame([false, true, false], [$events[$going]['invitation_pending'], $events[$invited]['invitation_pending'], $events[$neither]['invitation_pending']]);

        $groups = collect($this->get('/api/v2/moderate/groups?api_token=n1own')->assertSuccessful()->json())->keyBy('id');
        foreach ([$member, $asked, $other] as $group) {
            $single = $this->get("/api/v2/groups/{$group->idgroups}?api_token=n1own")->json('data');
            $this->assertSame($single['is_member'], $groups[$group->idgroups]['is_member'], "is_member, group {$group->idgroups}");
            $this->assertSame($single['has_pending_invite'], $groups[$group->idgroups]['has_pending_invite'], "has_pending_invite, group {$group->idgroups}");
        }
        $this->assertTrue($groups[$member->idgroups]['is_member']);
        $this->assertSame('group-hash', $groups[$asked->idgroups]['has_pending_invite']);
    }
}
