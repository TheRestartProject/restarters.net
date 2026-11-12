<?php

namespace Tests\Feature;

use App\Events\PasswordChanged;
use App\Listeners\ChangeWikiPassword;
use App\Listeners\NoteWikiPasswordChange;
use App\Listeners\LogInToWiki;
use App\User;
use App\WikiSyncStatus;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Addwiki\Mediawiki\Api\Service\UserCreator;
use Addwiki\Mediawiki\Api\Service\UserGetter;
use Addwiki\Mediawiki\DataModel\User as WikiUser;
use Mockery;
use Msurguy\Honeypot\HoneypotFacade as Honeypot;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\Support\FakeWiki;
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

    /**
     * @test
     * @story:LoginController::login
     */
    public function if_flagged_for_creation_create_when_logging_in(): void
    {
        $this->withoutExceptionHandling();
        Honeypot::disable();

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andReturn(true);
        }));
        $this->instance(UserGetter::class, Mockery::mock(UserGetter::class, function ($mock) {
            // No wiki user has the name yet.
            $mock->shouldReceive('getFromUsername')->andReturnUsing(fn ($name) => new WikiUser($name, 0, 0, '', ['groups' => [], 'implicitgroups' => []], [], ''));
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

    /**
     * @test
     * @story:LoginController::login
     */
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

    /**
     * @test
     * @story:LoginController::login
     */
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

    /**
     * @test
     * @story:UserController::postProfilePasswordEdit
     */
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

    /**
     * @test
     * @story:LoginController::login
     */
    public function login_succeeds_when_wiki_unavailable()
    {
        $this->withoutExceptionHandling();
        Honeypot::disable();

        // Bind null for UserCreator to simulate Wiki being unavailable
        $this->instance(UserCreator::class, null);

        // Given we have a user
        $user = User::factory()->create();
        $user->wiki_sync_status = WikiSyncStatus::CreateAtLogin;
        $user->save();

        // When user logs in
        $response = $this->post('/login', ['email' => $user->email, 'password'=> 'secret', 'my_name' => 'foo', 'my_time' => 1]);

        // Then the login should succeed (redirects to dashboard)
        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    }

    private function wikiUser(FakeWiki $wiki, ?string $wikiPassword = null): User
    {
        $user = User::factory()->create(['mediawiki' => 'Wiki-User']);
        $user->wiki_sync_status = WikiSyncStatus::Created;
        $user->save();

        // The wiki password is the password hash here.
        $wiki->passwords['Wiki-User'] = $wikiPassword ?? $user->password;

        return $user;
    }

    private function logIn(User $user): void
    {
        // As logging in does when the wiki integration is on.
        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class));
        app(LogInToWiki::class)->handle(new Login('web', $user, false));
    }

    /**
     * @test
     * @story:LoginController::login
     */
    public function logs_in_to_the_wiki_when_its_rate_limit_turns_the_first_tries_away(): void
    {
        // All our requests come from one address, so the wiki's web server limits them all together.
        $wiki = FakeWiki::install($this->app);
        $user = $this->wikiUser($wiki);
        $wiki->rateLimitNext = 2;
        Log::spy();

        $this->logIn($user);

        $this->assertEquals('Wiki-User', $wiki->loggedInAs);
        Log::shouldNotHaveReceived('error');
        $this->assertEquals(WikiSyncStatus::Created, $user->fresh()->wiki_sync_status);
    }

    /**
     * @test
     * @story:LoginController::login
     */
    public function a_wiki_password_out_of_sync_is_reported_once(): void
    {
        $wiki = FakeWiki::install($this->app);
        $user = $this->wikiUser($wiki, 'an older password hash');
        Log::spy();

        $this->logIn($user);

        $this->assertNull($wiki->loggedInAs);
        $this->assertEquals(WikiSyncStatus::PasswordOutOfSync, $user->fresh()->wiki_sync_status);
        Log::shouldHaveReceived('error')->once()->with(Mockery::pattern('/^Wiki password out of sync/'), Mockery::any());

        // It used to fail, and be reported, at every login.
        $wiki->requests = [];
        $this->logIn($user->fresh());

        $this->assertEmpty($wiki->requests);

        // Finding it again doesn't report it again.
        LogInToWiki::passwordOutOfSync($user->fresh());
        Log::shouldHaveReceived('error')->once();
    }

    private function changePassword(User $user): PasswordChanged
    {
        $old = $user->password;
        $user->password = Hash::make('new password');
        $user->save();

        $event = new PasswordChanged($user, $old);
        app(NoteWikiPasswordChange::class)->handle($event);

        return $event;
    }

    /**
     * @test
     * @story:App\Http\Controllers\UserController::reset
     */
    public function a_login_before_the_password_change_reaches_the_wiki_is_not_out_of_sync(): void
    {
        // After a password reset the user is sent to log in, and can do so before the queued change has run.
        $wiki = FakeWiki::install($this->app);
        $user = $this->wikiUser($wiki);
        $event = $this->changePassword($user);
        Log::spy();

        $this->logIn($user->fresh());

        $this->assertNull($wiki->loggedInAs);
        $this->assertEquals(WikiSyncStatus::Created, $user->fresh()->wiki_sync_status);
        Log::shouldNotHaveReceived('error');

        // Then the change reaches the wiki, and the next login works.
        app(ChangeWikiPassword::class)->handle($event);
        $this->assertFalse(ChangeWikiPassword::isPending($user));
        $this->logIn($user->fresh());
        $this->assertEquals('Wiki-User', $wiki->loggedInAs);
    }

    /**
     * @test
     * @story:UserController::postProfilePasswordEdit
     */
    public function a_wiki_blip_while_checking_the_new_password_is_tried_again_later(): void
    {
        // The old password is refused, and the wiki is then too busy to say whether the new one works: that's not
        // grounds for deciding it's out of sync.
        $wiki = FakeWiki::install($this->app);
        $user = $this->wikiUser($wiki, 'an older password hash');
        $event = $this->changePassword($user);

        $listener = app(ChangeWikiPassword::class);
        $listener->setJob(Mockery::mock(Job::class, function ($job) {
            $job->shouldReceive('attempts')->andReturn(1);
            $job->shouldReceive('release')->once()->with(ChangeWikiPassword::RETRY_DELAYS[0]);
        }));

        // Let the first login attempt (with the old password) through, then turn everything away.
        $handler = $wiki->handle(...);
        $this->app->bind(\App\Services\WikiHttpClient::HANDLER, fn () => \GuzzleHttp\HandlerStack::create(function ($request, $options) use ($wiki, $handler) {
            if (count($wiki->requests) >= 2) {
                $wiki->rateLimitNext = 100;
            }

            return $handler($request);
        }));

        $listener->handle($event);

        $this->assertEquals(WikiSyncStatus::Created, $user->fresh()->wiki_sync_status);
    }

    /**
     * @test
     * @story:UserController::postProfilePasswordEdit
     */
    public function a_password_change_reaches_the_wiki(): void
    {
        $wiki = FakeWiki::install($this->app);
        $user = $this->wikiUser($wiki);
        $event = $this->changePassword($user);

        app(ChangeWikiPassword::class)->handle($event);

        $this->assertEquals($user->fresh()->password, $wiki->passwords['Wiki-User']);
        $this->assertEquals(WikiSyncStatus::Created, $user->fresh()->wiki_sync_status);
    }

    /**
     * @test
     * @story:UserController::postProfilePasswordEdit
     */
    public function a_password_change_the_wiki_turns_away_is_tried_again_later(): void
    {
        // Dropping it left the wiki with the old password for good.
        $wiki = FakeWiki::install($this->app);
        $user = $this->wikiUser($wiki);
        $event = $this->changePassword($user);
        $wiki->rateLimitNext = 100;

        $listener = app(ChangeWikiPassword::class);
        $listener->setJob(Mockery::mock(Job::class, function ($job) {
            $job->shouldReceive('attempts')->andReturn(1);
            $job->shouldReceive('release')->once()->with(ChangeWikiPassword::RETRY_DELAYS[0]);
        }));
        $listener->handle($event);

        $this->assertEquals(WikiSyncStatus::Created, $user->fresh()->wiki_sync_status);

        // On the last try it gives up, and records that the passwords no longer match.
        $listener = app(ChangeWikiPassword::class);
        $listener->setJob(Mockery::mock(Job::class, function ($job) {
            $job->shouldReceive('attempts')->andReturn(5);
            $job->shouldNotReceive('release');
        }));
        $listener->handle($event);

        $this->assertEquals(WikiSyncStatus::PasswordOutOfSync, $user->fresh()->wiki_sync_status);
    }

    /**
     * @test
     * @story:UserController::postProfilePasswordEdit
     */
    public function a_password_change_finds_whether_the_wiki_is_already_out_of_sync(): void
    {
        $wiki = FakeWiki::install($this->app);

        // An earlier try changed it, then failed: nothing wrong.
        $user = $this->wikiUser($wiki);
        $event = $this->changePassword($user);
        $wiki->passwords['Wiki-User'] = $user->fresh()->password;
        app(ChangeWikiPassword::class)->handle($event);
        $this->assertEquals(WikiSyncStatus::Created, $user->fresh()->wiki_sync_status);

        // Neither the old nor the new password works: it was already out of sync.
        $wiki->passwords['Wiki-User'] = 'an older password hash';
        app(ChangeWikiPassword::class)->handle($this->changePassword($user->fresh()));
        $this->assertEquals(WikiSyncStatus::PasswordOutOfSync, $user->fresh()->wiki_sync_status);
    }

    /**
     * Log in a user who is due a wiki account. $taken are the wiki names that exist already; $creates are the
     * names the wiki will be asked to create, with whether it agrees.
     */
    private function createAtLogin(array $taken, array $creates, ?User $user = null, ?FakeWiki $wiki = null): User
    {
        if (! $user) {
            $user = User::factory()->create(['username' => 'matt']);
            $user->wiki_sync_status = WikiSyncStatus::CreateAtLogin;
            $user->save();
        }

        $named = fn ($name) => str_replace('{id}', $user->id, $name);
        $taken = array_map($named, $taken);

        $this->instance(UserGetter::class, Mockery::mock(UserGetter::class, function ($mock) use ($taken) {
            $mock->shouldReceive('getFromUsername')->andReturnUsing(fn ($name) => new WikiUser($name, in_array($name, $taken) ? 42 : 0, 0, '', ['groups' => [], 'implicitgroups' => []], [], ''));
        }));

        $this->instance(UserCreator::class, Mockery::mock(UserCreator::class, function ($mock) use ($creates, $named, $wiki) {
            foreach ($creates as $name => $created) {
                $mock->shouldReceive('create')->once()->with($named($name), Mockery::any(), Mockery::any())
                    ->andReturnUsing(function ($name, $password) use ($created, $wiki) {
                        // The new account then exists on the wiki, for the login that follows.
                        if ($created && $wiki) {
                            $wiki->passwords[$name] = $password;
                        }

                        return $created;
                    });
            }
            if (! $creates) {
                $mock->shouldNotReceive('create');
            }
        }));

        // As logging in does when the wiki integration is on.
        app(LogInToWiki::class)->handle(new Login('web', $user, false));

        return $user->fresh();
    }

    /**
     * @test
     * @story:LoginController::login
     */
    public function a_free_wiki_name_is_created(): void
    {
        $user = $this->createAtLogin([], ['matt' => true]);

        $this->assertEquals('matt', $user->mediawiki);
        $this->assertEquals(WikiSyncStatus::Created, $user->wiki_sync_status);
    }

    /**
     * @test
     * @story:LoginController::login
     */
    public function a_wiki_name_someone_else_has_gets_the_users_id_added(): void
    {
        // The wiki's "Matt" already belonged to someone else; taking it anyway shared the account.
        $wiki = FakeWiki::install($this->app);
        $wiki->passwords['matt'] = 'someone else';
        $user = $this->createAtLogin(['matt'], ['matt-{id}' => true], null, $wiki);

        $this->assertEquals('matt-'.$user->id, $user->mediawiki);
        $this->assertEquals(WikiSyncStatus::Created, $user->wiki_sync_status);
    }

    /**
     * @test
     * @story:LoginController::login
     */
    public function a_wiki_name_that_is_already_the_users_own_is_linked(): void
    {
        // Created on the wiki before, but not recorded here: link it rather than make a second account.
        $wiki = FakeWiki::install($this->app);
        $user = User::factory()->create(['username' => 'matt']);
        $user->wiki_sync_status = WikiSyncStatus::CreateAtLogin;
        $user->save();
        $wiki->passwords['matt'] = $user->password;

        $user = $this->createAtLogin(['matt'], [], $user, $wiki);

        $this->assertEquals('matt', $user->mediawiki);
        $this->assertEquals(WikiSyncStatus::Created, $user->wiki_sync_status);
    }

    /**
     * @test
     * @story:LoginController::login
     */
    public function a_wiki_account_turned_down_for_another_reason_is_tried_again_later(): void
    {
        // A throttle or a block isn't for good: the user stays due an account, and it's reported once a day.
        Log::spy();
        $user = $this->createAtLogin([], ['matt' => false]);

        $this->assertEquals(WikiSyncStatus::CreateAtLogin, $user->wiki_sync_status);
        $this->assertEmpty($user->mediawiki);

        $this->createAtLogin([], ['matt' => false], $user);
        Log::shouldHaveReceived('error')->once()->with('The wiki turned down a new account', Mockery::any());
    }

    /**
     * @test
     * @story:LoginController::login
     */
    public function when_both_wiki_names_belong_to_others_it_is_reported_once(): void
    {
        $wiki = FakeWiki::install($this->app);
        Log::spy();
        $user = $this->createAtLogin(['matt', 'matt-{id}'], [], null, $wiki);

        $this->assertEmpty($user->mediawiki);
        $this->assertEquals(WikiSyncStatus::DoNotCreate, $user->wiki_sync_status);
        Log::shouldHaveReceived('error')->once()->with('Both wiki names for a new account belong to someone else', Mockery::any());
    }
}
