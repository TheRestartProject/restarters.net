<?php

namespace Tests\Feature;

use App\Listeners\ChangeWikiPassword;
use App\SsoTicket;
use App\User;
use App\WikiSyncStatus;
use DB;
use Addwiki\Mediawiki\Api\Service\UserCreator;
use Mockery;
use Tests\TestCase;

/**
 * Wiki account creation / password sync.
 *
 * Post-Nuxt-cutover the web session is no longer established by a Blade
 * /login POST; it is established by the SSO bridge (GET /auth/bridge), which
 * fires Illuminate\Auth\Events\Login → LogInToWiki (see BridgeController and
 * EventServiceProvider). These tests therefore drive the bridge rather than
 * the removed /login route. Password changes now go through
 * PATCH /api/v2/users/me/password, which fires PasswordChanged →
 * ChangeWikiPassword.
 */
class WikiLoginTests extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /**
     * Establish a web session for $user the way the SPA does: issue a one-time
     * SSO ticket and redeem it at the bridge. This fires the Login event.
     */
    private function bridgeLogin(User $user): void
    {
        $ticket = SsoTicket::issue($user);
        $this->get('/auth/bridge?ticket='.$ticket);
    }

    /** @test */
    public function if_flagged_for_creation_create_when_logging_in(): void
    {
        $this->withoutExceptionHandling();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldReceive('create')->once();
        }));

        // Given we have a user with the flag set to sync them.
        $user = User::factory()->create();
        $user->wiki_sync_status = WikiSyncStatus::CreateAtLogin;
        $user->save();

        // When the user establishes a web session (SSO bridge).
        $this->bridgeLogin($user);

        // Then the user should be created on the wiki.
        $user = User::find($user->id);
        $this->assertEquals($user->mediawiki, $user->username);
        $this->assertEquals(WikiSyncStatus::Created, $user->wiki_sync_status);
    }

    /** @test */
    public function if_not_flagged_for_creation(): void
    {
        $this->withoutExceptionHandling();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldNotReceive('create');
        }));

        // Given we have a user with the flag set to not create.
        $user = User::factory()->create();
        $user->wiki_sync_status = WikiSyncStatus::DoNotCreate;
        $user->save();

        // When the user establishes a web session.
        $this->bridgeLogin($user);

        // Then the user should still be marked as DoNotCreate.
        $user = User::find($user->id);
        $this->assertEquals('', $user->mediawiki);
        $this->assertEquals(WikiSyncStatus::DoNotCreate, $user->wiki_sync_status);
    }

    /** @test */
    public function if_already_created(): void
    {
        $this->withoutExceptionHandling();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldNotReceive('create');
        }));

        // Given we have a user who has already been created in the wiki.
        $user = User::factory()->create();
        $user->wiki_sync_status = WikiSyncStatus::Created;
        $user->save();

        // When the user establishes a web session.
        $this->bridgeLogin($user);

        // Then the user should still be marked as Created.
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

        // Given we have a user who has already been created in the wiki.
        $user = User::factory()->create(); // factory password is 'secret'
        $user->wiki_sync_status = WikiSyncStatus::Created;
        $user->save();
        $this->actingAs($user);

        // When the user changes their password (fires PasswordChanged).
        $this->patch('/api/v2/users/me/password', [
            'current_password' => 'secret',
            'new_password' => 'newSecret1',
            'new_password_confirmation' => 'newSecret1',
        ]);

        // Then the user's wiki password should be changed to match (asserted
        // by the ChangeWikiPassword::handle mock expectation above).
    }

    /** @test */
    public function login_succeeds_when_wiki_unavailable(): void
    {
        $this->withoutExceptionHandling();

        // Bind null for UserCreator to simulate the wiki being unavailable.
        $this->instance(UserCreator::class, null);

        // Given we have a user flagged for wiki creation at login.
        $user = User::factory()->create();
        $user->wiki_sync_status = WikiSyncStatus::CreateAtLogin;
        $user->save();

        // When the user establishes a web session with the wiki down.
        $ticket = SsoTicket::issue($user);
        $response = $this->get('/auth/bridge?ticket='.$ticket);

        // Then the bridge still establishes the session (login is not blocked
        // by the wiki being unavailable).
        $response->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
    }
}
