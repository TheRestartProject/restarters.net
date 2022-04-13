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
        DB::statement('SET foreign_key_checks=0');
        Network::truncate();
        DB::delete('delete from user_network');
        DB::statement('SET foreign_key_checks=1');

        $this->networkService = new RepairNetworkService();
    }

    /** @test */
    public function network_can_be_created()
    {
        $this->withoutExceptionHandling();

        $network = factory(Network::class)->create();

        $this->assertNotNull($network);
    }

    /** @test */
    public function network_can_be_edited()
    {
        $this->withoutExceptionHandling();

        $network = factory(Network::class)->create();
        $network->name = 'Restart';
        $network->save();

        $this->assertEquals('Restart', $network->name);
    }

    /** @test */
    public function networks_can_be_queried()
    {
        $this->withoutExceptionHandling();

        $restart = factory(Network::class)->create();
        $restart->name = 'Restart';
        $restart->save();

        $repairTogether = factory(Network::class)->create();
        $repairTogether->name = 'Repair Together';
        $repairTogether->save();

        $result = Network::where('name', 'Restart')->first();

        $this->assertEquals('Restart', $result->name);
    }

    /** @test */
    public function groups_can_be_associated_to_network()
    {
        $this->withoutExceptionHandling();

        $network = factory(Network::class)->create();
        $network->name = 'Restart';
        $network->save();

        $group = factory(Group::class)->create();
        $group->name = 'Hackney Fixers';
        $group->save();

        $network->addGroup($group);

        $this->assertTrue($network->containsGroup($group));
        $this->assertTrue($group->isMemberOf($network));
    }

    /** @test */
    public function admins_can_associate_group_to_network()
    {
        $this->withoutExceptionHandling();

        // Given we're logged in as an admin
        $admin = factory(User::class)->states('Administrator')->create();
        $this->actingAs($admin);

        $group = factory(Group::class)->create([
           'latitude' => 51.5074,
           'longitude' => -0.1278,
        ]);

        $network = factory(Network::class)->create();

        $this->networkService->addGroupToNetwork($admin, $group, $network);

        $this->assertTrue($network->containsGroup($group));
        $this->assertTrue($group->isMemberOf($network));

        $event = factory(Party::class)->create([
            'group' => $group->idgroups,
            'online' => 1,
        ]);

        // Check the group shows up in the list of groups for this network.
        $coordinator = factory(User::class)->states('NetworkCoordinator')->create([
                                                                                      'api_token' => '1234',
        ]);
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $response = $this->get('/api/groups/network?api_token=1234');
        $groups = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($groups));
        $this->assertEquals($group->idgroups, $groups[0]['id']);
        $this->assertEquals($group->name, $groups[0]['name']);

        // Check that the event is listed.
        $this->assertEquals(1, count($groups[0]['past_parties']));
        $this->assertEquals($event->idevents, $groups[0]['past_parties'][0]['event_id']);
        $this->assertEquals($event->free_text, $groups[0]['past_parties'][0]['description']);
        $this->assertEquals(1, $groups[0]['past_parties'][0]['online']);

        // Get again with a bounding box which the group is inside.
        $response = $this->get('/api/groups/network?api_token=1234&bbox='.urlencode('51.5,-0.13,51.51,-0.12'));
        $groups = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($groups));
        $this->assertEquals($group->idgroups, $groups[0]['id']);
        $this->assertEquals($group->name, $groups[0]['name']);

        // Get again with a bounding box which the group is outside.
        $response = $this->get('/api/groups/network?api_token=1234&bbox='.urlencode('51.5,-0.13,51.505,-0.12'));
        $groups = json_decode($response->getContent(), true);
        $this->assertEquals(0, count($groups));

        // Check the event shows in the events network API call.
        $response = $this->get('/api/events/network?api_token=1234');
        $events = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($events));
        $this->assertEquals($event->idevents, $events[0]['id']);
        $this->assertEquals($event->free_text, $events[0]['description']);
        $this->assertEquals(1, $events[0]['online']);
    }

    /** @test */
    public function non_admins_cant_associate_group_to_network()
    {
        $this->withoutExceptionHandling();

        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $group = factory(Group::class)->create();
        $network = factory(Network::class)->create();

        $this->expectException(\Exception::class);
        $this->networkService->addGroupToNetwork($host, $group, $network);

        $this->assertFalse($network->containsGroup($group));
        $this->assertFalse($group->isMemberOf($network));
    }

    /** @test */
    public function user_can_be_set_as_coordinator_of_network()
    {
        $this->withoutExceptionHandling();

        // Given we're logged in as an admin
        $admin = factory(User::class)->states('Administrator')->create();
        $this->actingAs($admin);

        $network = factory(Network::class)->create();
        $coordinator = factory(User::class)->states('NetworkCoordinator')->create();

        // act
        $network->addCoordinator($coordinator);

        // assert
        $this->assertTrue($coordinator->isCoordinatorOf($network));
    }

    /** @test */
    public function network_stats_can_be_queried()
    {
        $network = factory(Network::class)->create();
        $coordinator = factory(User::class)->states('NetworkCoordinator')->create([
                                                                                      'api_token' => '1234',
                                                                                  ]);

        $group = factory(Group::class)->create();
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
    public function network_page()
    {
        $network = factory(Network::class)->create([
            'shortname' => 'restarters'
                                                   ]);

        $coordinator = factory(User::class)->states('NetworkCoordinator')->create();
        $this->actingAs($coordinator);

        $group = factory(Group::class)->create();
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
        $response->assertSee(e($network->name));

        // Coordinator should show on network page.
        $response = $this->get('/networks/' . $network->id);
        $response->assertSee(e($coordinator->name));

        // Group should not show on network page yet.
        $response = $this->get('/group/network/' . $network->id);
        $response->assertDontSee('&quot;networks&quot;:[' . $network->id . ']');

        // Add the group.
        $response = $this->get('/networks/' . $network->id);
        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $response = $this->post('/networks/' . $network->id . '/groups', [
            '_token' => $tokenValue,
            'groups' => [ $group->idgroups ]
        ]);
        $response->assertRedirect();

        // Group should now show on network page and in encoded list of networks for a groiup.
        $response = $this->get('/group/network/' . $network->id);
        $response->assertSee($group->name);
        $response->assertSee('&quot;networks&quot;:[' . $network->id . ']');

        // All networks list visible to admin.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->get('/networks');
        $response->assertSee(__('networks.index.all_networks_explainer'));
    }
}
