<?php

namespace Tests\Feature\Groups;

use App\Device;
use App\Group;
use App\Party;
use App\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GroupViewTest extends TestCase
{
    public function testBasic(): void
    {
        // Check we can create a group and view it.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        // Create an event and a fixed device, as this will be used in stats info returned from GroupController.
        // We want the fixed device to be in a category with a cluster.
        $event = Party::factory()->create([
            'group' => $id,
            'event_start_utc' => Carbon::parse('1pm tomorrow')->toIso8601String(),
            'event_end_utc' => Carbon::parse('3pm tomorrow')->toIso8601String()
        ]);
        $device = Device::factory()->fixed()->create([
            'category' => 11,
            'category_creation' => 11,
            'event' => $event->idevents,
        ]);

        // The page itself no longer carries the per-user UI permission flags (canedit, can-see-delete,
        // can-perform-delete, ...) as Blade props - GroupPage.vue fetches those, along with the rest of
        // the group data, from GET /api/v2/groups/{id}. See APIv2GroupPermissionsTest for thorough
        // coverage of that flag matrix; here we just check the API endpoint reports full permissions
        // for this administrator.
        $apiResponse = $this->get("/api/v2/groups/$id");
        $apiResponse->assertSuccessful();
        $permissions = json_decode($apiResponse->getContent(), true)['data']['permissions'];
        $this->assertTrue($permissions['can_edit']);
        $this->assertTrue($permissions['can_see_delete']);
        $this->assertFalse($permissions['can_perform_delete']);
    }

    private function assertGroupPermissions($id, $canSeeDelete, $canPerformDelete): void
    {
        // The page's Blade props no longer carry these flags - GroupPage.vue fetches them from this endpoint.
        $response = $this->get("/api/v2/groups/$id");
        $response->assertSuccessful();
        $permissions = json_decode($response->getContent(), true)['data']['permissions'];
        $this->assertEquals($canSeeDelete, $permissions['can_see_delete']);
        $this->assertEquals($canPerformDelete, $permissions['can_perform_delete']);
    }

    public function testCanDelete(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        // Create a past event
        $event = Party::factory()->moderated()->create([
                                                                        'event_start_utc' => '2000-01-01T10:15:05+05:00',
                                                                        'event_end_utc' => '2000-01-0113:45:05+05:00',
                                                                        'group' => $id,
                                                                    ]);

        // Groups are deletable unless they have an event with a device.
        $this->assertGroupPermissions($id, true, true);

        $iddevices = $this->createDevice($event->idevents,
            'misc', null, 1.5, 0, '',
            Device::REPAIR_STATUS_FIXED_STR, null, null, 111);

        $this->assertGroupPermissions($id, true, false);

        # Check the device shows in the API.
        $rsp2 = $this->get('/api/devices/1/10?sortBy=iddevices&sortDesc=asc&powered=false');
        $ret = json_decode($rsp2->getContent(), true);
        self::assertEquals(1, $ret['count']);
        self::assertEquals(1, count($ret['items']));
        self::assertEquals($event->idevents, $ret['items'][0]['eventid']);

        // Only administrators can delete.
        foreach (['Restarter', 'Host', 'NetworkCoordinator'] as $role) {
            $user = \App\User::factory()->{lcfirst($role)}()->create();
            $this->actingAs($user);
            $this->assertGroupPermissions($id, false, false);
        }

        // Test stats API.
        foreach (['fixometer', 'consume', 'manufacture'] as $format) {
            $response = $this->get("/api/outbound/info/party/{$event->idevents}/$format");
            $stats = json_decode($response->getContent(), TRUE);
            self::assertEquals(1, $stats['co2']);

            $response = $this->get("/api/outbound/info/group/{$id}/$format");
            $stats = json_decode($response->getContent(), TRUE);
            self::assertEquals(1, $stats['co2']);
        }
    }

    public function testInProgressVisible(): void {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        Party::factory()->moderated()->create([
                                                                    'event_start_utc' => Carbon::parse('1 hour ago')->toIso8601String(),
                                                                    'event_end_utc' => Carbon::parse('4pm tomorrow')->toIso8601String(),
                                                                    'group' => $id,
                                                                ]);

        // Event should show in the list for the group. GroupPage.vue now fetches this from
        // GET /api/v2/groups/{id}/events (GroupController::getEventsForGroupv2) rather than
        // Blade :events props.
        $response = $this->get("/api/v2/groups/$id/events");
        $response->assertSuccessful();
        $events = json_decode($response->getContent(), true)['data'];
        self::assertEquals(Party::latest()->first()->idevents, $events[0]['id']);
    }

    public function testGroupIndexNextEventIsEagerLoaded(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Create two groups, each with a different upcoming event.
        $id1 = $this->createGroup('Group Alpha');
        $id2 = $this->createGroup('Group Beta');

        $tomorrow = Carbon::parse('1pm tomorrow');
        $nextWeek = Carbon::parse('1pm next week');

        Party::factory()->create([
            'group' => $id1,
            'approved' => true,
            'event_start_utc' => $tomorrow->toIso8601String(),
            'event_end_utc' => $tomorrow->addHours(2)->toIso8601String(),
        ]);
        Party::factory()->create([
            'group' => $id2,
            'approved' => true,
            'event_start_utc' => $nextWeek->toIso8601String(),
            'event_end_utc' => $nextWeek->addHours(2)->toIso8601String(),
        ]);

        // Groups are fetched via API (not server-rendered) — test that next_event is returned.
        $response = $this->get('/api/v2/groups/summary?includeNextEvent=true');
        $response->assertSuccessful();

        $groups = $response->json('data');
        $group1 = collect($groups)->firstWhere('id', $id1);
        $group2 = collect($groups)->firstWhere('id', $id2);

        $this->assertNotNull($group1, "Group Alpha (id=$id1) not found in API response");
        $this->assertNotNull($group2, "Group Beta (id=$id2) not found in API response");
        $this->assertNotNull($group1['next_event'], 'Group Alpha should have a next_event');
        $this->assertNotNull($group2['next_event'], 'Group Beta should have a next_event');
    }

    public function testGroupIndexQueryCountScalesWithO1NotN(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Create 3 groups with upcoming events, tags, etc.
        for ($i = 0; $i < 3; $i++) {
            $id = $this->createGroup("Perf Group $i");
            $event = Party::factory()->create([
                'group' => $id,
                'approved' => true,
                'event_start_utc' => Carbon::parse('1pm tomorrow')->toIso8601String(),
                'event_end_utc' => Carbon::parse('3pm tomorrow')->toIso8601String(),
            ]);
        }

        // The group list page is now rendered by the SPA, which fetches it from
        // GET /api/v2/groups/summary (see testGroupIndexNextEventIsEagerLoaded above) rather than
        // the old Blade /group listing - point the N+1 regression check at that live endpoint.

        // Warm up (first request may have extra overhead).
        $this->get('/api/v2/groups/summary?includeNextEvent=true');

        DB::enableQueryLog();
        $this->get('/api/v2/groups/summary?includeNextEvent=true')->assertSuccessful();
        $queriesFor3 = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        // Add 3 more groups.
        for ($i = 3; $i < 6; $i++) {
            $id = $this->createGroup("Perf Group $i");
            Party::factory()->create([
                'group' => $id,
                'approved' => true,
                'event_start_utc' => Carbon::parse('1pm tomorrow')->toIso8601String(),
                'event_end_utc' => Carbon::parse('3pm tomorrow')->toIso8601String(),
            ]);
        }

        DB::enableQueryLog();
        $this->get('/api/v2/groups/summary?includeNextEvent=true')->assertSuccessful();
        $queriesFor6 = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        // With N+1 fixed, doubling the groups should not double the queries.
        // Allow some small variance but reject obvious N+1 growth.
        $this->assertLessThan(
            $queriesFor3 * 1.5,
            $queriesFor6,
            "Query count grew too much when adding more groups — possible N+1. 3 groups: $queriesFor3, 6 groups: $queriesFor6"
        );
    }

    public function testNextUpcomingPartyRelationship(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $group = Group::find($id);

        // No events yet.
        $this->assertNull($group->nextUpcomingParty);

        // Create a past event — should not appear.
        Party::factory()->create([
            'group' => $id,
            'approved' => true,
            'event_start_utc' => Carbon::parse('1pm yesterday')->toIso8601String(),
            'event_end_utc' => Carbon::parse('3pm yesterday')->toIso8601String(),
        ]);
        $group->refresh();
        $this->assertNull($group->nextUpcomingParty);

        // Create two future events — should return the earlier one.
        $sooner = Party::factory()->create([
            'group' => $id,
            'approved' => true,
            'event_start_utc' => Carbon::parse('1pm tomorrow')->toIso8601String(),
            'event_end_utc' => Carbon::parse('3pm tomorrow')->toIso8601String(),
        ]);
        Party::factory()->create([
            'group' => $id,
            'approved' => true,
            'event_start_utc' => Carbon::parse('1pm next week')->toIso8601String(),
            'event_end_utc' => Carbon::parse('3pm next week')->toIso8601String(),
        ]);

        $group->refresh();
        $this->assertEquals($sooner->idevents, $group->nextUpcomingParty->idevents);
    }
}
