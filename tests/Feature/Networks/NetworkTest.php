<?php

namespace Tests\Feature;

use App\Group;
use App\Network;
use App\Role;
use App\User;
use App\Helpers\RepairNetworkService;

use DB;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NetworkTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Network::truncate();
        Group::truncate();
        DB::statement("SET foreign_key_checks=1");

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

        // Given we're logged in as an admin
        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $group = factory(Group::class)->create();
        $network = factory(Network::class)->create();

        $this->expectException(\Exception::class);
        $this->networkService->addGroupToNetwork($host, $group, $network);

        $this->assertFalse($network->containsGroup($group));
        $this->assertFalse($group->isMemberOf($network));
    }
}
