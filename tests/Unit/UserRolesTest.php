<?php

namespace Tests\Unit;

use App\Role;
use App\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /** @test */
    public function user_can_have_network_coordinator_role()
    {
        // arrange
        $user = factory(User::class)->create();

        $user->role = Role::NETWORK_COORDINATOR;
        $user->save();

        // assert
        $this->assertTrue($user->hasRole('NetworkCoordinator'));
    }

    /** @test */
    public function can_change_restarter_to_host_role()
    {
        // arrange
        $user = factory(User::class)->create();
        $user->role = Role::RESTARTER;

        // act
        $user->convertToHost();

        // assert
        $this->assertTrue($user->hasRole('Host'));
    }

    /** @test */
    public function cannot_change_admin_or_coordinator_to_host_role()
    {
        // arrange
        $user1 = factory(User::class)->state('Administrator')->create();
        $user2 = factory(User::class)->state('NetworkCoordinator')->create();

        // act
        $user1->convertToHost();
        $user2->convertToHost();

        // assert
        $this->assertTrue($user1->hasRole('Administrator'));
        $this->assertTrue($user2->hasRole('NetworkCoordinator'));
    }
}
