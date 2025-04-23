<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Helpers\Fixometer;
use App\Models\Network;
use App\Models\Party;
use App\Models\Role;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CoordinatorTest extends TestCase
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

    /** @test */
    public function it_can_find_relevant_coordinators(): void
    {
        // arrange
        $network = Network::factory()->create();
        $group = Group::factory()->create();
        $coordinator = User::factory()->networkCoordinator()->create();

        $network->addGroup($group);
        $network->addCoordinator($coordinator);

        $event = Party::factory()->create(['group' => $group]);

        // assert
        $coordinators = $event->associatedNetworkCoordinators();

        $this->assertStringContainsString($coordinator->id, $coordinators->pluck('id'));
    }

    /** @test */
    public function promote_to_coordinator(): void
    {
        // arrange
        $network = Network::factory()->create();
        $group = Group::factory()->create();
        $coordinator = User::factory()->restarter()->create();
        $network->addGroup($group);

        // Check we promote.
        $network->addCoordinator($coordinator);
        $coordinator->refresh();
        self::assertEquals(Role::NETWORK_COORDINATOR, $coordinator->role);

        // Check we don't demote.
        $admin = User::factory()->administrator()->create();
        $network->addCoordinator($admin);
        $admin->refresh();
        self::assertEquals(Role::ADMINISTRATOR, $admin->role);
    }

}
