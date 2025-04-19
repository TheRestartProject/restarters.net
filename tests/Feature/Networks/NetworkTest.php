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
use Symfony\Component\DomCrawler\Crawler;
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
        $network = Network::factory()->create([
                                                       'shortname' => 'restarters'
                                                   ]);

        $coordinator = User::factory()->networkCoordinator()->create();
        $this->actingAs($coordinator);

        $group = Group::factory()->create();
        $group->save();

        // Not a coordinator yet.
        $response = $this->get('/networks');
        $response->assertSee(__('networks.index.your_networks_no_networks'));
        $response->assertDontSee(__('networks.index.all_networks_explainer'));

        // Make a coordinator.
        $network->addCoordinator($coordinator);
        $coordinator->refresh();
        $response = $this->get('/networks');
        $response->assertDontSee(__('networks.index.your_networks_no_networks'));
        $response->assertDontSee(__('networks.index.all_networks_explainer'));
        $response->assertSee($network->name);

        // Coordinator should show on network page.
        $response = $this->get('/networks/' . $network->id);
        $response->assertSee($coordinator->name);

        // Group should not show on network page yet.
        $response = $this->get('/group/network/' . $network->id);
        $response->assertDontSee('&quot;networks&quot;:[' . $network->id . ']');

        // Add the group.
        $response = $this->get('/networks/' . $network->id);
        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i)
        {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $response = $this->post('/networks/' . $network->id . '/groups', [
            '_token' => $tokenValue,
            'groups' => [$group->idgroups]
        ]);
        $response->assertRedirect();

        // Group should now show on network page and in encoded list of networks for a group.
        $response = $this->get('/group/network/' . $network->id);
        $response->assertSee($group->name);
        $response->assertSee('&quot;networks&quot;:[' . $network->id . ']', false);

        // All networks list visible to admin.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/networks');
        $response->assertSee(__('networks.index.all_networks_explainer'));
    }

    /** @test */
    public function admins_can_edit(): void
    {
        $this->withoutExceptionHandling();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $network = Network::where('shortname', 'restarters')->first();

        $response = $this->get('/networks/' . $network->id . '/edit', $network->attributesToArray());
        $response->assertSuccessful();
        $response->assertSee('Editing');

        $response = $this->put('/networks/' . $network->id, $network->attributesToArray());
        $response->assertRedirect();

        // Associate a group.
        $group = Group::factory()->create();
        $group->name = 'Hackney Fixers';
        $group->save();

        $response = $this->post('/networks/' . $network->id . '/groups', [
            'groups' => [ $group->idgroups ]
        ]);

        $response->assertRedirect();
        $this->assertTrue($network->containsGroup($group));
        $this->assertTrue($group->isMemberOf($network));
    }

    public function testRemoveNetworkCoordinatorByRole(): void {
        $this->withoutExceptionHandling();

        $network = Network::factory()->create();

        $admin = User::factory()->administrator()->create();
        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);

        $this->actingAs($admin);

        $response = $this->get('/user/edit/' . $coordinator->id);
        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $response = $this->post('/profile/edit-admin-settings', [
            '_token' => $tokenValue,
            'id' => $coordinator->id,
            'assigned_groups' => [],
            'user_role' => Role::HOST,
        ]);
        $response->assertSessionHas('message');
        $this->assertTrue($response->isRedirection());

        // Demoting to host should remove as a network coordinator.
        $this->assertFalse($network->coordinators->contains($coordinator));
    }
}
