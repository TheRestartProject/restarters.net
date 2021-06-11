<?php

namespace Tests\Unit;

use App\Group;
use App\Network;
use App\Party;
use App\User;
use FixometerHelper;

use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionsTests extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        Group::truncate();
        Network::truncate();
        Party::truncate();
        DB::delete('delete from users_groups');
        DB::delete('delete from group_network');
        DB::delete('delete from user_network');
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function it_can_find_relevant_coordinators()
    {
        // arrange
        $network = factory(Network::class)->create();
        $group = factory(Group::class)->create();
        $coordinator = factory(User::class)->state('NetworkCoordinator')->create();

        $network->addGroup($group);
        $network->addCoordinator($coordinator);

        $event = factory(Party::class)->create(['group' => $group]);

        // assert
        $coordinators = $event->associatedNetworkCoordinators();

        $this->assertContains($coordinator->id, $coordinators->pluck('id'));
    }
}
