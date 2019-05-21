<?php

namespace Tests\Feature;

use App\User;

use DB;
use Carbon\Carbon;
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
        // $response->assertSeeText($lastLogin->diffForHumans());
        $response->assertSeeText('1 second ago');
    }

    /** @test */
    public function admin_can_see_users_last_login_time_on_filtered_results()
    {
        // $this->withoutExceptionHandling();

        // Given we have a user who has just logged in
        $lastLogin = new \Carbon\Carbon();
        $user = factory(User::class)->create([
            'updated_at' => $lastLogin
        ]);

        // When we visit the list of users and filter by that user
        $response = $this->get('/user/all/search?name=' . $user->name);

        // Then we should see the last login date for that user
        $response->assertSeeText('1 second ago');
    }

    /** @test */
    public function admin_can_sort_user_list_by_last_login()
    {
        $this->withoutExceptionHandling();

        // Given we have users with various login times
        $dateOfMostRecentLogin = new Carbon();

        $dateOfLeastRecentLogin = new Carbon('-1 year');

        $userWithMostRecentLogin = factory(User::class)->create([
            'last_login_at' => $dateOfMostRecentLogin,
            'name' => 'Test User'
        ]);

        $otherUsers = factory(User::class, 10)->create([
            'last_login_at' => new Carbon('-1 month')
        ]);

        $userWithLeastRecentLogin = factory(User::class)->create([
            'last_login_at' => $dateOfLeastRecentLogin
        ]);

        // When we visit the list of users and sort it by last login descending
        $response = $this->get('/user/all/search?sort=last_login_at&sortdir=desc');

        // Then the first result is the most recent login
        // This doesn't work as diffForHumans diffs the date against now()
        // which is not the same time $dateOfMostRecentLogin was created.
        // The test takes approx 4 - 7 seconds to run...
        // $response->assertSeeText($dateOfMostRecentLogin->diffForHumans());
        $response->assertSeeText('1 second ago');

        // When we visit the list of users and sort it by last login descending
        $response = $this->get('/user/all/search?sort=last_login_at&sortdir=asc');

        // Then the first result is the most recent login
        // $response->assertSeeText($dateOfLeastRecentLogin->diffForHumans());
        $response->assertSeeText('1 year ago');
    }
}
