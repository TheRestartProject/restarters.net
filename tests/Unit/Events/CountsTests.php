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

class CountsTests extends TestCase
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
        $this->assertTrue(FixometerHelper::userHasEditPartyPermission($event->idevents));

        $this->actingAs($coordinator2);
        $this->assertFalse(FixometerHelper::userHasEditPartyPermission($event->idevents));

        $this->actingAs($host);
        $this->assertFalse(FixometerHelper::userHasEditPartyPermission($event->idevents));
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
        $this->assertTrue(FixometerHelper::userHasEditPartyPermission($event->idevents));
    }

    // Event counts should only include events in the past.
    /** @test */
    public function it_can_check_if_event_counts_past()
    {
        // Create a group and an event on it.
        $network = factory(Network::class)->create();
        $group = factory(Group::class)->create();
        $network->addGroup($group);

        $event = factory(Party::class)->create([
            'group' => $group,
            'event_date' => '2130-01-01'
        ]);
        $event->save();

        $pastcount = count($event->ofThisGroup('group', true, false));
        $allcount =  count($event->ofThisGroup('group', false, false));

        // The event we created is in the future, so the count of all should be more than the count in the past.
        $this->assertGreaterThan($pastcount, $allcount);
    }
}
