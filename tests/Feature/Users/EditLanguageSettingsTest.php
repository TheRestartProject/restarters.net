<?php

namespace Tests\Feature;

use App\Events\UserLanguageUpdated;
use App\Events\UserUpdated;
use App\Listeners\SyncUserProperties;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EditLanguageSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /** @test */
    // The assertion just tells us that the event is dispatched, not much else.
    // In order to actually check it worked, we need to look at Discourse.
    public function user_language_update_triggers_language_sync()
    {
        Event::fake([
                        UserLanguageUpdated::class,
                    ]);

        // arrange
        config(['restarters.features.discourse_integration' => true]);
        config(['discourse-api.base_url' => 'https://talk.restarters.dev']);
        config(['discourse-api.api_key' => 'XXX']); // change XXX when testing against Discourse instance.
        config(['discourse-api.api_username' => 'neil']);

        $user = User::factory()->create(['id' => 67]);

        // act
        $user->language = 'de';
        $user->save();
        $this->artisan("queue:work --stop-when-empty");

        // assert
        Event::assertDispatched(UserLanguageUpdated::class);
    }

    /** @test */
    // Added these to try (and fail) to reproduce a Sentry error.
    public function user_sets_language()
    {
        $this->loginAsTestUser();

        $this->followingRedirects();
        $response = $this->from('/')->get('/set-lang/en');
        $response->assertSuccessful();
        $this->assertEquals('en', session('locale'));

        $this->followingRedirects();
        $response = $this->from('/')->get('/set-lang/zz');
        $response->assertSuccessful();
        $this->assertEquals('zz', session('locale'));
    }
}
