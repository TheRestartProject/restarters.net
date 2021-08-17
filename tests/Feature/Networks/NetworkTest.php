<?php

namespace Tests\Feature;

use App\Group;
use App\Helpers\RepairNetworkService;
use App\Network;
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

        $group = factory(Group::class)->create();
        $network = factory(Network::class)->create();

        $this->networkService->addGroupToNetwork($admin, $group, $network);

        $this->assertTrue($network->containsGroup($group));
        $this->assertTrue($group->isMemberOf($network));
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
        $expectedStats = [
            'pax' => 0,
            'hours' => 0,
            'parties' => 0,
            'co2' => 0,
            'waste' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 0,
            'fixed_devices' => 0,
            'fixed_powered' => 0,
            'fixed_unpowered' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
            'devices_powered' => 0,
            'devices_unpowered' => 0,
        ];
        $this->assertEquals($expectedStats, $stats);
    }
}
