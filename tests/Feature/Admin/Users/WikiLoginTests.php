<?php

namespace Tests\Feature;

use App\Listeners\LogInToWiki;
use App\Listeners\ChangeWikiPassword;
use App\User;
use App\WikiSyncStatus;

use DB;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Msurguy\Honeypot\HoneypotFacade as Honeypot;
use Mockery;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mediawiki\Api\Service\UserCreator;

class WikiLoginTests extends TestCase
{
    //use WithoutMiddleware;

    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function if_flagged_for_creation_create_when_logging_in()
    {
        $this->withoutExceptionHandling();
        Honeypot::disable();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldReceive('create')->once();
        }));

        // Given we have a user with the flag set to sync them.
        $user = factory(User::class)->create();
        $user->wiki_sync_status = WikiSyncStatus::CreateAtLogin;
        $user->save();

        // When user logs in
        $response = $this->post('/login', ['email' => $user->email, 'password'=> 'secret', 'my_name' => 'foo', 'my_time' => 1]);

        // Then the user should be created on the wiki
        $user = User::find($user->id);
        $this->assertEquals($user->mediawiki, $user->username);
        $this->assertEquals(WikiSyncStatus::Created, $user->wiki_sync_status);
    }

    /** @test */
    public function if_not_flagged_for_creation()
    {
        $this->withoutExceptionHandling();
        Honeypot::disable();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldNotReceive('create');
        }));

        // Given we have a user with the flag set to not create
        $user = factory(User::class)->create();
        $user->wiki_sync_status = WikiSyncStatus::DoNotCreate;
        $user->save();

        // When user logs in
        $response = $this->post('/login', ['email' => $user->email, 'password'=> 'secret', 'my_name' => 'foo', 'my_time' => 1]);

        // Then the user should still be marked as DoNotCreate
        $user = User::find($user->id);
        $this->assertEquals('', $user->mediawiki);
        $this->assertEquals(WikiSyncStatus::DoNotCreate, $user->wiki_sync_status);
    }

    /** @test */
    public function if_already_created()
    {
        $this->withoutExceptionHandling();
        Honeypot::disable();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldNotReceive('create');
        }));

        // Given we have a user who has already been created in the wiki
        $user = factory(User::class)->create();
        $user->wiki_sync_status = WikiSyncStatus::Created;
        $user->save();

        // When user logs in
        $response = $this->post('/login', ['email' => $user->email, 'password'=> 'secret', 'my_name' => 'foo', 'my_time' => 1]);

        // Then the user should still be marked as Created
        $user = User::find($user->id);
        $this->assertEquals('', $user->mediawiki);
        $this->assertEquals(WikiSyncStatus::Created, $user->wiki_sync_status);
    }


    /** @test */
    public function if_wiki_user_changes_password()
    {
        $this->withoutExceptionHandling();

        $this->instance(ChangeWikiPassword::class, Mockery::mock(ChangeWikiPassword::class, function ($mock) {
            $mock->shouldReceive('handle')->once();
        }));

        // Given we have a user who has already been created in the wiki
        $user = factory(User::class)->create();
        $user->wiki_sync_status = WikiSyncStatus::Created;
        $user->save();
        $this->actingAs($user);

        // When user changes password
        $response = $this->post('/profile/edit-password', ['current-password' => 'secret', 'new-password' => 'f00', 'new-password-repeat' => 'f00']);

        // Then the user's wiki password should be changed to match
    }
}
