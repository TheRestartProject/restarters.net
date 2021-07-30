<?php

namespace Tests\Unit;

use App\Group;
use App\Network;
use App\Party;
use App\User;
use DB;
use App\Helpers\Fixometer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventPermissionsTests extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        Group::truncate();
        Network::truncate();
        Party::truncate();
        DB::delete('delete from users_groups');
        DB::delete('delete from group_network');
        DB::delete('delete from user_network');
        DB::statement('SET foreign_key_checks=1');
    }

    // edit event permissions
    // Admins - can edit all events
    // Network Coords - can edit all events from groups in their network
    // Hosts - can edit all events in their groups

    /** @test */
    public function it_can_check_if_admin_can_edit_all()
    {
        // arrange
        $network = factory(Network::class)->create();

        $group = factory(Group::class)->create();
        $network->addGroup($group);

        $event = factory(Party::class)->create(['group' => $group]);

        $admin = factory(User::class)->state('Administrator')->create();
        $coordinator = factory(User::class)->state('NetworkCoordinator')->create();
        $host = factory(User::class)->state('Host')->create();

        // assert
        $this->actingAs($admin);
        $this->assertTrue(Fixometer::userHasEditPartyPermission($event->idevents));

        // assert
        $this->actingAs($coordinator);
        $this->assertFalse(Fixometer::userHasEditPartyPermission($event->idevents));

        // assert
        $this->actingAs($host);
        $this->assertFalse(Fixometer::userHasEditPartyPermission($event->idevents));
    }

    /** @test */
    public function it_can_check_if_coordinator_can_edit_for_network()
    {
        // arrange
        $network = factory(Network::class)->create();

        $group = factory(Group::class)->create();
        $network->addGroup($group);

        $event = factory(Party::class)->create(['group' => $group]);

        $coordinator1 = factory(User::class)->state('NetworkCoordinator')->create();
        $network->addCoordinator($coordinator1);
        $coordinator2 = factory(User::class)->state('NetworkCoordinator')->create();
        $host = factory(User::class)->state('Host')->create();

        // assert
        $this->actingAs($coordinator1);
        $this->assertTrue(Fixometer::userHasEditPartyPermission($event->idevents));

        $this->actingAs($coordinator2);
        $this->assertFalse(Fixometer::userHasEditPartyPermission($event->idevents));

        $this->actingAs($host);
        $this->assertFalse(Fixometer::userHasEditPartyPermission($event->idevents));
    }

    /** @test */
    public function it_can_check_if_host_can_edit_for_group()
    {
        // arrange
        $network = factory(Network::class)->create();

        $group = factory(Group::class)->create();
        $network->addGroup($group);

        $event = factory(Party::class)->create(['group' => $group]);

        $host = factory(User::class)->state('Host')->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // assert
        $this->actingAs($host);
        $this->assertTrue(Fixometer::userHasEditPartyPermission($event->idevents));
    }
}
