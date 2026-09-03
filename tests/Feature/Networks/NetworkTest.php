<?php

namespace Tests\Feature;

use App\Group;
use App\Helpers\RepairNetworkService;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->networkService = new RepairNetworkService();
    }

    /** @test */
    public function network_can_be_created(): void
    {
        $this->withoutExceptionHandling();

        $network = Network::factory()->create();

        $this->assertNotNull($network);
    }

    /** @test */
    public function network_can_be_edited(): void
    {
        $this->withoutExceptionHandling();

        $network = Network::factory()->create();
        $network->name = 'Restart';
        $network->save();

        $this->assertEquals('Restart', $network->name);
    }

    /** @test */
    public function networks_can_be_queried(): void
    {
        $this->withoutExceptionHandling();

        $restart = Network::factory()->create();
        $restart->name = 'Restart';
        $restart->save();

        $repairTogether = Network::factory()->create();
        $repairTogether->name = 'Repair Together';
        $repairTogether->save();

        $result = Network::where('name', 'Restart')->first();

        $this->assertEquals('Restart', $result->name);
    }

    /** @test */
    public function groups_can_be_associated_to_network(): void
    {
        $this->withoutExceptionHandling();

        $network = Network::factory()->create();
        $network->name = 'Restart';
        $network->save();

        $group = Group::factory()->create();
        $group->name = 'Hackney Fixers';
        $group->save();

        $network->addGroup($group);

        $this->assertTrue($network->containsGroup($group));
        $this->assertTrue($group->isMemberOf($network));
    }

    /** @test */
    public function admins_can_associate_group_to_network(): void
    {
        $this->withoutExceptionHandling();

        // Given we're logged in as an admin
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $group = Group::factory()->create([
                                                   'latitude' => 51.5074,
                                                   'longitude' => -0.1278,
                                                   'country_code' => 'GB',
                                                   'country' => 'United Kingdom',
                                               ]);

        $network = Network::factory()->create();

        $this->networkService->addGroupToNetwork($admin, $group, $network);

        $this->assertTrue($network->containsGroup($group));
        $this->assertTrue($group->isMemberOf($network));

        $event1 = Party::factory()->create([
                                                    'group' => $group->idgroups,
                                                    'online' => 1,
                                                    'event_start_utc' => Carbon::parse(
                                                        '1pm yesterday'
                                                    )->toIso8601String(),
                                                    'event_end_utc' => Carbon::parse('3pm yesterday')->toIso8601String()
                                                ]);

        $event2 = Party::factory()->create([
                                                    'group' => $group->idgroups,
                                                    'online' => 1,
                                                    'event_start_utc' => Carbon::parse('1pm tomorrow')->toIso8601String(
                                                    ),
                                                    'event_end_utc' => Carbon::parse('3pm tomorrow')->toIso8601String()
                                                ]);

        // Check the group shows up in the list of groups for this network.
        $coordinator = User::factory()->networkCoordinator()->create([
                                                                                      'api_token' => '1234',
                                                                                  ]);
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $response = $this->get('/api/groups/network?api_token=1234');
        $groups = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($groups));
        $this->assertEquals($group->idgroups, $groups[0]['id']);
        $this->assertEquals($group->name, $groups[0]['name']);
        $this->assertEquals($group->country, $groups[0]['location']['country']);
        $this->assertEquals($group->country_code, $groups[0]['location']['country_code']);

        // Check that the event is listed.
        $this->assertEquals(1, count($groups[0]['past_parties']));
        $this->assertEquals($event1->idevents, $groups[0]['past_parties'][0]['event_id']);
        $this->assertEquals($event1->free_text, $groups[0]['past_parties'][0]['description']);
        $this->assertEquals(1, $groups[0]['past_parties'][0]['online']);

        $this->assertEquals(1, count($groups[0]['upcoming_parties']));
        $this->assertEquals($event2->idevents, $groups[0]['upcoming_parties'][0]['event_id']);
        $this->assertEquals($event2->free_text, $groups[0]['upcoming_parties'][0]['description']);
        $this->assertEquals(1, $groups[0]['past_parties'][0]['online']);

        // Get again with a bounding box which the group is inside.
        $response = $this->get('/api/groups/network?api_token=1234&bbox=' . urlencode('51.5,-0.13,51.51,-0.12'));
        $groups = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($groups));
        $this->assertEquals($group->idgroups, $groups[0]['id']);
        $this->assertEquals($group->name, $groups[0]['name']);

        // Get again with a bounding box which the group is outside.
        $response = $this->get('/api/groups/network?api_token=1234&bbox=' . urlencode('51.5,-0.13,51.505,-0.12'));
        $groups = json_decode($response->getContent(), true);
        $this->assertEquals(0, count($groups));

        // Check the event shows in the events network API call.
        $response = $this->get('/api/events/network?api_token=1234');
        $events = json_decode($response->getContent(), true);
        $this->assertEquals(2, count($events));
        $this->assertEquals($event1->idevents, $events[0]['id']);
        $this->assertEquals($event1->free_text, $events[0]['description']);
        $this->assertEquals(1, $events[0]['online']);
        $this->assertEquals($event2->idevents, $events[1]['id']);
        $this->assertEquals($event2->free_text, $events[1]['description']);
        $this->assertEquals(1, $events[1]['online']);

        // Basic check on date format.
        $this->assertStringContainsString('T', (new Carbon($events[1]['updated_at']))->toIso8601String());
    }

    /** @test */
    public function non_admins_cant_associate_group_to_network(): void
    {
        $this->withoutExceptionHandling();

        $host = User::factory()->host()->create();
        $this->actingAs($host);

        $group = Group::factory()->create();
        $network = Network::factory()->create();

        $this->expectException(\Exception::class);
        $this->networkService->addGroupToNetwork($host, $group, $network);

        $this->assertFalse($network->containsGroup($group));
        $this->assertFalse($group->isMemberOf($network));
    }

    /** @test */
    public function admin_can_associate_groups_via_the_api(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'assoc-admin']);
        $network = Network::factory()->create();
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        $this->actingAs($admin);
        $response = $this->postJson('/api/v2/networks/'.$network->id.'/groups?api_token=assoc-admin', [
            'groups' => [$group1->idgroups, $group2->idgroups],
        ]);

        $response->assertSuccessful();
        $this->assertEquals(2, $response->json('data.associated'));
        $this->assertTrue($network->fresh()->containsGroup($group1));
        $this->assertTrue($network->fresh()->containsGroup($group2));
    }

    /** @test */
    public function coordinator_can_associate_groups_via_the_api(): void
    {
        $network = Network::factory()->create();
        $coordinator = User::factory()->networkCoordinator()->create(['api_token' => 'assoc-coord']);
        $network->addCoordinator($coordinator);
        $group = Group::factory()->create();

        $this->actingAs($coordinator);
        $response = $this->postJson('/api/v2/networks/'.$network->id.'/groups?api_token=assoc-coord', [
            'groups' => [$group->idgroups],
        ]);

        $response->assertSuccessful();
        $this->assertTrue($network->fresh()->containsGroup($group));
    }

    /** @test */
    public function non_admin_cannot_associate_groups_via_the_api(): void
    {
        $this->withExceptionHandling();

        $network = Network::factory()->create();
        $host = User::factory()->host()->create(['api_token' => 'assoc-host']);
        $group = Group::factory()->create();

        $this->actingAs($host);
        $response = $this->postJson('/api/v2/networks/'.$network->id.'/groups?api_token=assoc-host', [
            'groups' => [$group->idgroups],
        ]);

        $response->assertStatus(403);
        $this->assertFalse($network->fresh()->containsGroup($group));
    }

    /** @test */
    public function user_can_be_set_as_coordinator_of_network(): void
    {
        $this->withoutExceptionHandling();

        // Given we're logged in as an admin
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $network = Network::factory()->create();
        $coordinator = User::factory()->networkCoordinator()->create();

        // act
        $network->addCoordinator($coordinator);

        // assert
        $this->assertTrue($coordinator->isCoordinatorOf($network));
    }

    /** @test */
    public function network_stats_can_be_queried(): void
    {
        $network = Network::factory()->create();
        $coordinator = User::factory()->networkCoordinator()->create([
                                                                                      'api_token' => '1234',
                                                                                  ]);

        $group = Group::factory()->create();
        $group->name = 'Hackney Fixers';
        $group->save();

        $network->addGroup($group);

        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $response = $this->get("/api/networks/{$network->id}/stats?api_token=1234");
        $stats = json_decode($response->getContent(), true);
        $expectedStats = \App\Group::getGroupStatsArrayKeys();
        $this->assertEquals($expectedStats, $stats);
    }

    /** @test */
    public function network_page(): void
    {
        // The /networks and /networks/{id} Blade pages, and the
        // /networks/{id}/groups form post, are retired under the Nuxt
        // cutover (the SPA renders the network page itself, backed by
        // /api/v2/networks/* and /api/v2/groups/summary). What remains
        // worth covering here is the underlying data: a coordinator can be
        // attached to a network, a group can be associated to it, and the
        // group then carries that network in the API data the SPA fetches.
        $network = Network::factory()->create([
                                                       'shortname' => 'restarters'
                                                   ]);

        $coordinator = User::factory()->networkCoordinator()->create();
        $this->actingAs($coordinator);

        $group = Group::factory()->create();
        $group->save();

        // Make a coordinator.
        $network->addCoordinator($coordinator);
        $coordinator->refresh();
        $this->assertTrue($coordinator->isCoordinatorOf($network));

        // Add the group (the /networks/{id}/groups web form post was
        // deleted; there's no API v2 replacement for this association, so
        // we go via the model relation directly, as other tests in this
        // file do).
        $network->addGroup($group);
        $this->assertTrue($network->containsGroup($group));

        // And the group must be in the API data the page will fetch.
        $response = $this->get('/api/v2/groups/summary');
        $summary = collect($response->json('data'))->firstWhere('id', $group->idgroups);
        $this->assertNotNull($summary);
        $this->assertEquals($network->id, collect($summary['networks'])->first()['id']);
    }

    /** @test */
    public function admins_can_edit(): void
    {
        // The /networks/{id}/edit and /networks/{id} (PUT) Blade routes,
        // and the /networks/{id}/groups form post, are retired under the
        // Nuxt cutover. Editing a network's own fields isn't exercised
        // elsewhere by an API route, so what's left worth covering here is
        // that an admin can associate a group to a network — via the model
        // relation directly, since there's no API v2 replacement for that
        // association (see NetworkController).
        $this->withoutExceptionHandling();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $network = Network::where('shortname', 'restarters')->first();

        // Associate a group.
        $group = Group::factory()->create();
        $group->name = 'Hackney Fixers';
        $group->save();

        $network->addGroup($group);

        $this->assertTrue($network->containsGroup($group));
        $this->assertTrue($group->isMemberOf($network));
    }

    public function testRemoveNetworkCoordinatorByRole(): void {
        $this->withoutExceptionHandling();

        $network = Network::factory()->create();

        $admin = User::factory()->administrator()->create();
        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);

        $admin->api_token = 'nctok';
        $admin->save();
        $this->actingAs($admin);

        // The admin-settings form on /user/edit is now the AdminSettingsTab Vue
        // component, which PATCHes this endpoint.
        $response = $this->patchJson('/api/v2/users/' . $coordinator->id . '/admin-settings?api_token=nctok', [
            'user_role' => Role::HOST,
            'assigned_groups' => [],
            'preferences' => [],
            'permissions' => [],
        ]);
        $response->assertStatus(200);

        // Demoting to host should remove as a network coordinator.
        $this->assertFalse($network->coordinators->contains($coordinator));
    }
}
