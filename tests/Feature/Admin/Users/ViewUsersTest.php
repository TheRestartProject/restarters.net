<?php

namespace Tests\Feature;

use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewUsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        DB::statement('SET foreign_key_checks=1');

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
            'updated_at' => $lastLogin,
        ]);

        // When we visit the list of users
        $response = $this->get('/user/all');

        // Then we should see the last login date for that user
        $response->assertSeeText($lastLogin->diffForHumans(null, true));
    }

    /** @test */
    public function admin_can_see_users_last_login_time_on_filtered_results()
    {
        // Given we have a user who has just logged in
        $lastLogin = new \Carbon\Carbon();
        $user = factory(User::class)->create([
            'updated_at' => $lastLogin,
        ]);

        // When we visit the list of users and filter by that user
        $response = $this->get('/user/all/search?name='.$user->name);

        // Then we should see the last login date for that user
        $response->assertSeeText($lastLogin->diffForHumans(null, true));
    }

    /** @test */
    public function admin_can_sort_user_list_by_last_login()
    {
        // Given we have users with various login times
        $dateOfMostRecentLogin = new Carbon();
        $dateOfLeastRecentLogin = new Carbon('-1 year');

        $userWithMostRecentLogin = factory(User::class)->create([
            'last_login_at' => $dateOfMostRecentLogin,
        ]);
        $otherUsers = factory(User::class, 42)->create([
            'last_login_at' => new Carbon('-1 month'),
        ]);
        $userWithLeastRecentLogin = factory(User::class)->create([
            'last_login_at' => $dateOfLeastRecentLogin,
        ]);

        // When we visit the list of users and sort it by last login descending
        $response = $this->get('/user/all/search?sort=last_login_at&sortdir=desc');

        // Then the first result is the most recent login
        $response->assertSeeText($dateOfMostRecentLogin->diffForHumans(null, true));

        // When we visit the list of users and sort it by last login descending
        $response = $this->get('/user/all/search?sort=last_login_at&sortdir=asc');

        // Then the first result is the most recent login
        $response->assertSeeText($dateOfLeastRecentLogin->diffForHumans(null, true));
    }
}
