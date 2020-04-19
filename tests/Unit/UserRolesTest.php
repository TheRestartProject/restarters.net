<?php

namespace Tests\Unit;

use App\Role;
use App\User;

use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRolesTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        DB::statement("SET foreign_key_checks=1");
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

}
