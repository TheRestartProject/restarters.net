<?php

namespace Tests\Feature\Groups;

use App\Device;
use App\Group;
use App\Party;
use App\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

        // View with id.
        $response = $this->get("/group/view/$id");

        $props = $this->assertVueProperties($response, [
            [],
            [
                ':idgroups' => $id,
                ':canedit' => 'true',
                ':can-see-delete' => 'true',
                ':can-perform-delete' => 'false',
                ':top-devices' => json_encode([
                    [
                        'counter' => 1,
                        'name' => 'Desktop computer'
                    ]
                ]),
            ],
        ]);
        $this->assertEquals(1, count(json_decode($props[1][':events'], TRUE)));
    }

    public function testInvalidGroup(): void
    {
        $this->loginAsTestUser(Role::RESTARTER);
        $this->expectException(NotFoundHttpException::class);
        $this->get('/group/view/undefined');
    }

    public function testInvalidGroup2(): void
    {
        $this->loginAsTestUser(Role::RESTARTER);
        $this->expectException(NotFoundHttpException::class);
        $this->get('/group/view/1');
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
        $response = $this->get("/group/view/$id");
        $this->assertVueProperties($response, [
            [],
            [
                ':idgroups' => $id,
                ':can-see-delete' => 'true',
                ':can-perform-delete' => 'true',
            ],
        ]);

        $iddevices = $this->createDevice($event->idevents,
            'misc', null, 1.5, 0, '',
            Device::REPAIR_STATUS_FIXED_STR, null, null, 111);

        $response = $this->get("/group/view/$id");
        $this->assertVueProperties($response, [
            [],
            [
                ':idgroups' => $id,
                ':can-see-delete' => 'true',
                ':can-perform-delete' => 'false',
            ],
        ]);

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
            $response = $this->get("/group/view/$id");
            $this->assertVueProperties($response, [
                [],
                [
                    ':idgroups' => $id,
                    ':can-see-delete' => 'false',
                    ':can-perform-delete' => 'false',
                ],
            ]);
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

        // Event should show in list for group.
        $response = $this->get("/group/view/$id");
        $props = $this->getVueProperties($response);
        $events = json_decode($props[1][':events'], true);
        self::assertEquals(Party::latest()->first()->idevents, $events[0]['idevents']);
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

        // Load the group index and check both groups appear with a next_event.
        $response = $this->get('/group');
        $response->assertSuccessful();

        $props = $this->getVueProperties($response);
        $allGroupsJson = null;
        foreach ($props as $prop) {
            if (isset($prop[':all-groups'])) {
                $allGroupsJson = $prop[':all-groups'];
                break;
            }
        }
        $this->assertNotNull($allGroupsJson, 'Could not find :all-groups prop. Props found: ' . json_encode(array_map('array_keys', $props)));
        $groups = json_decode($allGroupsJson, true);

        $group1 = collect($groups)->firstWhere('idgroups', $id1);
        $group2 = collect($groups)->firstWhere('idgroups', $id2);

        $this->assertNotNull($group1['next_event'], 'Group 1 should have a next_event');
        $this->assertNotNull($group2['next_event'], 'Group 2 should have a next_event');
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

        // Warm up (first request may have extra overhead).
        $this->get('/group');

        DB::enableQueryLog();
        $this->get('/group')->assertSuccessful();
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
        $this->get('/group')->assertSuccessful();
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
