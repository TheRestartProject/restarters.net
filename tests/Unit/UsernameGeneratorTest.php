<?php

namespace Tests\Unit;

use App\User;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UsernameGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /** @test */
    public function name_is_single_name()
    {
        $user = \App\User::factory()->create();
        $user->name = 'Philip';

        $user->generateAndSetUsername();

        $this->assertEquals('Philip', $user->username);
    }

    /** @test */
    public function name_is_first_and_last_name()
    {
        $user = \App\User::factory()->create();
        $user->name = 'Philip Fry';

        $user->generateAndSetUsername();

        $this->assertEquals('Philip_Fry', $user->username);
    }

    /** @test */
    public function name_is_first_name_initial_and_last_name()
    {
        $user = \App\User::factory()->create();
        $user->name = 'Philip J. Fry';

        $user->generateAndSetUsername();

        $this->assertEquals('Philip_J_Fry', $user->username);
    }

    /** @test */
    public function name_has_special_chars()
    {
        $user = \App\User::factory()->create();
        $user->name = 'Brixton Repair Café';

        $user->generateAndSetUsername();

        $this->assertEquals('Brixton_Repair_Cafe', $user->username);
    }

    /** @test */
    public function name_has_leading_or_trailing_whitespace()
    {
        $user = \App\User::factory()->create();
        $user->name = ' Philip J Fry  ';

        $user->generateAndSetUsername();

        $this->assertEquals('Philip_J_Fry', $user->username);
    }

    /** @test */
    public function username_already_taken()
    {
        $user1 = \App\User::factory()->create();
        $user1->name = 'Philip J Fry';
        $user1->generateAndSetUsername();
        $user1->save();

        $user2 = \App\User::factory()->create();
        $user2->name = 'Philip J Fry';
        $user2->generateAndSetUsername();

        $this->assertEquals('Philip_J_Fry_'.$user2->id, $user2->username);
    }

    /** @test */
    public function username_repeated_special_char() {
        $user = \App\User::factory()->create();
        $user->name = 'M._Someone';

        $user->generateAndSetUsername();

        $this->assertEquals('M_Someone', $user->username);
    }

    // if name is empty?
}
