<?php

namespace Tests\Unit;

use App\Models\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Helpers\Fixometer;
use App\Models\Network;
use App\Models\Party;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EventPermissionsTest extends TestCase
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

    #[Test]
    public function it_can_check_if_admin_can_edit_all(): void
    {
        // arrange
        $network = Network::factory()->create();

        $group = Group::factory()->create();
        $network->addGroup($group);

        $event = Party::factory()->create(['group' => $group]);

        $admin = User::factory()->administrator()->create();
        $coordinator = User::factory()->networkCoordinator()->create();
        $host = User::factory()->host()->create();

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

    #[Test]
    public function it_can_check_if_coordinator_can_edit_for_network(): void
    {
        // arrange
        $network = Network::factory()->create();

        $group = Group::factory()->create();
        $network->addGroup($group);

        $event = Party::factory()->create(['group' => $group]);

        $coordinator1 = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator1);
        $coordinator2 = User::factory()->networkCoordinator()->create();
        $host = User::factory()->host()->create();

        // assert
        $this->actingAs($coordinator1);
        $this->assertTrue(Fixometer::userHasEditPartyPermission($event->idevents));

        $this->actingAs($coordinator2);
        $this->assertFalse(Fixometer::userHasEditPartyPermission($event->idevents));

        $this->actingAs($host);
        $this->assertFalse(Fixometer::userHasEditPartyPermission($event->idevents));
    }

    #[Test]
    public function it_can_check_if_host_can_edit_for_group(): void
    {
        // arrange
        $network = Network::factory()->create();

        $group = Group::factory()->create();
        $network->addGroup($group);

        $event = Party::factory()->create(['group' => $group]);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // assert
        $this->actingAs($host);
        $this->assertTrue(Fixometer::userHasEditPartyPermission($event->idevents));
    }
}
