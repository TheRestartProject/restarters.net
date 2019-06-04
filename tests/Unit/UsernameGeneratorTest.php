<?php

namespace Tests\Unit;

use App\User;

use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsernameGenerationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        DB::statement("SET foreign_key_checks=1");
    }


    /** @test */
    public function name_is_single_name()
    {
        $user = factory('App\User')->create();
        $user->name = "Philip";

        $user->generateAndSetUsername();

        $this->assertEquals("Philip", $user->username);
    }


    /** @test */
    public function name_is_first_and_last_name()
    {
        $user = factory('App\User')->create();
        $user->name = "Philip Fry";

        $user->generateAndSetUsername();

        $this->assertEquals("Philip_Fry", $user->username);
    }

    /** @test */
    public function name_is_first_name_initial_and_last_name()
    {
        $user = factory('App\User')->create();
        $user->name = "Philip J. Fry";

        $user->generateAndSetUsername();

        $this->assertEquals("Philip_J._Fry", $user->username);
    }

    /** @test */
    public function name_has_special_chars()
    {
        $user = factory('App\User')->create();
        $user->name = "Brixton Repair Café";

        $user->generateAndSetUsername();

        $this->assertEquals("Brixton_Repair_Cafe", $user->username);
    }

    /** @test */
    public function name_has_leading_or_trailing_whitespace()
    {
        $user = factory('App\User')->create();
        $user->name = " Philip J Fry  ";

        $user->generateAndSetUsername();

        $this->assertEquals("Philip_J_Fry", $user->username);
    }

    /** @test */
    public function username_already_taken()
    {
        $user1 = factory('App\User')->create();
        $user1->name = "Philip J Fry";
        $user1->generateAndSetUsername();
        $user1->save();

        $user2 = factory('App\User')->create();
        $user2->name = "Philip J Fry";
        $user2->generateAndSetUsername();

        $this->assertEquals("Philip_J_Fry_".$user2->id, $user2->username);
    }

    // if name is empty?
}
