<?php

namespace Tests\Feature\Security;

use App\Device;
use App\Group;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use Tests\TestCase;

/**
 * Missing per-object authorization checks.
 *
 * Two families here:
 *
 *  1. The three image-upload endpoints, where the sibling delete-image endpoint
 *     got an ownership check and the upload endpoint did not.
 *  2. updateDevicev2, which authorises against an event id taken from the request
 *     body but mutates a device identified by the URL path — the two were never
 *     reconciled. Plus createEventv2, which only checks that the caller hosts
 *     *some* group (a standing TODO in the code).
 *
 * Written before the fixes; these should fail on the unpatched code.
 */
class AuthorizationHardeningTest extends TestCase
{
    /** Make $user a host of a newly created, approved group. */
    private function makeHostOfNewGroup(User $user): Group
    {
        $group = Group::factory()->create(['approved' => true]);
        UserGroups::create([
            'user' => $user->id,
            'group' => $group->idgroups,
            'status' => 1,
            'role' => Role::HOST,
        ]);

        return $group;
    }

    // -------------------------------------------------------------------------
    // Image uploads
    // -------------------------------------------------------------------------

    /** @test */
    public function a_stranger_cannot_upload_an_image_to_a_group(): void
    {
        $this->withExceptionHandling();

        $host = User::factory()->host()->create();
        $group = $this->makeHostOfNewGroup($host);

        $stranger = User::factory()->restarter()->create();
        $this->actingAs($stranger);

        $response = $this->post('/group/image-upload/' . $group->idgroups);

        $response->assertStatus(403);
    }

    /** @test */
    public function a_group_host_can_still_upload_an_image_to_their_own_group(): void
    {
        $this->withExceptionHandling();

        $host = User::factory()->host()->create();
        $group = $this->makeHostOfNewGroup($host);

        $this->actingAs($host);

        // No file is attached, so nothing is stored — we are asserting only that
        // the authorization guard lets a legitimate host through.
        $response = $this->post('/group/image-upload/' . $group->idgroups);

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function a_stranger_cannot_upload_an_image_to_an_event(): void
    {
        $this->withExceptionHandling();

        $host = User::factory()->host()->create();
        $group = $this->makeHostOfNewGroup($host);
        $event = Party::factory()->create(['group' => $group->idgroups]);

        $stranger = User::factory()->restarter()->create();
        $this->actingAs($stranger);

        $response = $this->post('/party/image-upload/' . $event->idevents);

        $response->assertStatus(403);
    }

    /** @test */
    public function a_stranger_cannot_upload_an_image_to_a_device(): void
    {
        $this->withExceptionHandling();

        $host = User::factory()->host()->create();
        $group = $this->makeHostOfNewGroup($host);
        $event = Party::factory()->create(['group' => $group->idgroups]);
        $device = Device::factory()->fixed()->create(['event' => $event->idevents]);

        $stranger = User::factory()->restarter()->create();
        $this->actingAs($stranger);

        $response = $this->post('/device/image-upload/' . $device->iddevices);

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // updateDevicev2: guard object must match the mutated object
    // -------------------------------------------------------------------------

    /** @test */
    public function a_host_cannot_hijack_a_device_belonging_to_another_groups_event(): void
    {
        $this->withExceptionHandling();

        // Victim group, with an event and a device on it.
        $victimHost = User::factory()->host()->create();
        $victimGroup = $this->makeHostOfNewGroup($victimHost);
        $victimEvent = Party::factory()->create(['group' => $victimGroup->idgroups]);
        $victimDevice = Device::factory()->fixed()->create([
            'event' => $victimEvent->idevents,
            'brand' => 'VictimBrand',
        ]);

        // Attacker hosts their own unrelated group and event.
        $attacker = User::factory()->host()->create();
        $attackerGroup = $this->makeHostOfNewGroup($attacker);
        $attackerEvent = Party::factory()->create(['group' => $attackerGroup->idgroups]);

        $this->actingAs($attacker);

        // The permission check inspects `eventid` (the attacker's own event) while
        // the device actually mutated comes from the path.
        $response = $this->json('PATCH', '/api/v2/devices/' . $victimDevice->iddevices, [
            'eventid' => $attackerEvent->idevents,
            'category' => 111,
            'item_type' => 'Hijacked',
            'brand' => 'AttackerBrand',
            'model' => 'AttackerModel',
            'problem' => 'Hijacked',
            'repair_status' => Device::REPAIR_STATUS_FIXED_STR,
        ]);

        $response->assertStatus(403);

        $fresh = $victimDevice->fresh();
        $this->assertEquals($victimEvent->idevents, $fresh->event, 'The device must not be reassigned to another event.');
        $this->assertEquals('VictimBrand', $fresh->brand, 'The device must not be overwritten.');
    }

    /** @test */
    public function a_host_can_still_update_a_device_on_their_own_event(): void
    {
        $this->withExceptionHandling();

        $host = User::factory()->host()->create();
        $group = $this->makeHostOfNewGroup($host);
        $event = Party::factory()->create(['group' => $group->idgroups]);
        $device = Device::factory()->fixed()->create([
            'event' => $event->idevents,
            'brand' => 'OriginalBrand',
        ]);

        $this->actingAs($host);

        $response = $this->json('PATCH', '/api/v2/devices/' . $device->iddevices, [
            'eventid' => $event->idevents,
            'category' => 111,
            'item_type' => 'Kettle',
            'brand' => 'UpdatedBrand',
            'model' => 'SomeModel',
            'problem' => 'Broken',
            'repair_status' => Device::REPAIR_STATUS_FIXED_STR,
        ]);

        $response->assertSuccessful();
        $this->assertEquals('UpdatedBrand', $device->fresh()->brand);
    }

    // -------------------------------------------------------------------------
    // createEventv2: must host *that* group
    // -------------------------------------------------------------------------

    /** @test */
    public function a_host_cannot_create_an_event_on_a_group_they_do_not_host(): void
    {
        $this->withExceptionHandling();

        $victimHost = User::factory()->host()->create();
        $victimGroup = $this->makeHostOfNewGroup($victimHost);

        // Attacker is a host, but only of their own group.
        $attacker = User::factory()->host()->create();
        $this->makeHostOfNewGroup($attacker);

        $this->actingAs($attacker);

        $response = $this->json('POST', '/api/v2/events', [
            'groupid' => $victimGroup->idgroups,
            'start' => '2130-01-01T10:00:00+00:00',
            'end' => '2130-01-01T12:00:00+00:00',
            'title' => 'Spam event',
            'description' => 'Spam',
            'location' => 'London',
            'timezone' => 'Europe/London',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(0, Party::where('group', $victimGroup->idgroups)->count());
    }

    /** @test */
    public function a_host_can_still_create_an_event_on_their_own_group(): void
    {
        $this->withExceptionHandling();

        $host = User::factory()->host()->create();
        $group = $this->makeHostOfNewGroup($host);

        $this->actingAs($host);

        $response = $this->json('POST', '/api/v2/events', [
            'groupid' => $group->idgroups,
            'start' => '2130-01-01T10:00:00+00:00',
            'end' => '2130-01-01T12:00:00+00:00',
            'title' => 'Legitimate event',
            'description' => 'Repair away',
            'location' => 'London',
            'timezone' => 'Europe/London',
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, Party::where('group', $group->idgroups)->count());
    }

    // -------------------------------------------------------------------------
    // Stale debug scaffolding
    // -------------------------------------------------------------------------

    /** @test */
    public function the_check_auth_debug_endpoint_is_gone(): void
    {
        $this->withExceptionHandling();

        $response = $this->get('/test/check-auth');

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Destructive actions must not be reachable by GET
    // -------------------------------------------------------------------------

    /**
     * Laravel's CSRF middleware only covers POST/PUT/PATCH/DELETE, so a destructive action
     * on a GET route can be triggered by any page an admin happens to load. These are all
     * POST now; a GET must not reach the controller at all.
     *
     * @test
     */
    public function destructive_actions_are_not_reachable_by_get(): void
    {
        $this->withExceptionHandling();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $group = Group::factory()->create(['approved' => true]);
        $skill = \App\Skills::create(['skill_name' => 'Soldering', 'description' => 'x', 'category' => 2]);
        $tag = \App\GroupTags::create(['tag_name' => 'Tagged', 'description' => 'x']);

        foreach ([
            '/group/delete/' . $group->idgroups,
            '/skills/delete/' . $skill->id,
            '/tags/delete/' . $tag->id,
        ] as $url) {
            $this->get($url)->assertStatus(405, "GET $url should not be routable");
        }

        // ...and the objects are all still there.
        $this->assertNotNull(Group::find($group->idgroups));
        $this->assertNotNull(\App\Skills::find($skill->id));
        $this->assertNotNull(\App\GroupTags::find($tag->id));
    }
}
