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
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);
    }

    /** @test */
    public function an_admin_can_view_list_of_users()
    {
        // Given we have users in the database
        $users = User::factory()->count(10)->create();

        // When we visit the list of users
        $response = $this->get('/user/all');

        // Then the users should be in the list
        $response->assertSeeText(e($users[0]->name));
    }

    /** @test */
    public function an_admin_can_see_how_many_total_users_in_the_list()
    {
        // Given we have users in the database
        $users = User::factory()->count(41)->create();

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
        $user = User::factory()->create([
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
        $user = User::factory()->create([
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

        $userWithMostRecentLogin = User::factory()->create([
            'last_login_at' => $dateOfMostRecentLogin,
        ]);
        $otherUsers = User::factory()->count(42)->create([
            'last_login_at' => new Carbon('-1 month'),
        ]);
        $userWithLeastRecentLogin = User::factory()->create([
            'last_login_at' => $dateOfLeastRecentLogin,
        ]);

        // Try this a few times because we might be unlucky and hit a second boundary.
        $found = false;

        for ($i = 0; $i < 10; $i++) {
            // When we visit the list of users and sort it by last login descending
            $response = $this->get('/user/all/search?sort=last_login_at&sortdir=desc');
            $datestr = $dateOfMostRecentLogin->diffForHumans(null, true);

            if (stripos($response->getContent(), $datestr) !== false) {
                // Then the first result is the most recent login
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);

        // When we visit the list of users and sort it by last login descending
        $response = $this->get('/user/all/search?sort=last_login_at&sortdir=asc');

        // Then the first result is the most recent login
        $found = false;

        for ($i = 0; $i < 10; $i++) {
            // When we visit the list of users and sort it by last login descending
            $response = $this->get('/user/all/search?sort=last_login_at&sortdir=asc');
            $datestr = $dateOfLeastRecentLogin->diffForHumans(null, true);

            if (stripos($response->getContent(), $datestr) !== false) {
                // Then the first result is the most recent login
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }
}
