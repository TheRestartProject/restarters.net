<?php

namespace Tests\Feature;

use App\Listeners\ChangeWikiPassword;
use App\Listeners\LogInToWiki;
use App\User;
use App\WikiSyncStatus;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Addwiki\Mediawiki\Api\Service\UserCreator;
use Mockery;
use Msurguy\Honeypot\HoneypotFacade as Honeypot;
use Tests\TestCase;

class WikiLoginTests extends TestCase
{
    //use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /** @test */
    public function if_flagged_for_creation_create_when_logging_in(): void
    {
        $this->withoutExceptionHandling();
        Honeypot::disable();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldReceive('create')->once();
        }));

        // Given we have a user with the flag set to sync them.
        $user = User::factory()->create();
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
    public function if_not_flagged_for_creation(): void
    {
        $this->withoutExceptionHandling();
        Honeypot::disable();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldNotReceive('create');
        }));

        // Given we have a user with the flag set to not create
        $user = User::factory()->create();
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
    public function if_already_created(): void
    {
        $this->withoutExceptionHandling();
        Honeypot::disable();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldNotReceive('create');
        }));

        // Given we have a user who has already been created in the wiki
        $user = User::factory()->create();
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
    public function if_wiki_user_changes_password(): void
    {
        $this->withoutExceptionHandling();

        $this->instance(ChangeWikiPassword::class, Mockery::mock(ChangeWikiPassword::class, function ($mock) {
            $mock->shouldReceive('handle')->once();
        }));

        // Given we have a user who has already been created in the wiki
        $user = User::factory()->create();
        $user->wiki_sync_status = WikiSyncStatus::Created;
        $user->save();
        $this->actingAs($user);

        // When user changes password
        $response = $this->post('/profile/edit-password', ['current-password' => 'secret', 'new-password' => 'f00', 'new-password-repeat' => 'f00']);

        // Then the user's wiki password should be changed to match
    }
}
