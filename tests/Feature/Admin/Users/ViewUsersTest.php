<?php

namespace Tests\Feature;

use App\User;

use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewUsersTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        DB::statement("SET foreign_key_checks=1");


        // Given we're logged in as an admin
        $admin = factory(User::class)->states('Administrator')->create();
        $this->actingAs($admin);
    }

    /** @test */
    public function an_admin_can_view_list_of_users()
    {
        // Given we have users in the database
        $users = factory(User::class, 10)->create();

        // When we visit the list of users
        $response = $this->get('/user/all');

        // Then the users should be in the list
        $response->assertSeeText($users[0]->name);
    }

    /** @test */
    public function an_admin_can_see_how_many_total_users_in_the_list()
    {
        // Given we have users in the database
        $users = factory(User::class, 41)->create();

        // When we visit the list of users
        $response = $this->get('/user/all');

        // Then we should see the count of users
        $response->assertSeeText(42);
    }

    /** @test */
    public function admin_can_see_users_last_login_time()
    {
        // Given we have a user who has just logged in
        $lastLogin = new \Carbon\Carbon();
        $user = factory(User::class)->create([
            'updated_at' => $lastLogin
        ]);

        // When we visit the list of users
        $response = $this->get('/user/all');

        // Then we should see the last login date for that user
        $response->assertSeeText($lastLogin->diffForHumans());
    }

    /** @test */
    public function admin_can_see_users_last_login_time_on_filtered_results()
    {
        // Given we have a user who has just logged in
        $lastLogin = new \Carbon\Carbon();
        $user = factory(User::class)->create([
            'updated_at' => $lastLogin
        ]);

        // When we visit the list of users
        $response = $this->get('/user/all/search?name=' . $user->name);

        // Then we should see the last login date for that user
        $response->assertSeeText($lastLogin->diffForHumans());
    }

}
