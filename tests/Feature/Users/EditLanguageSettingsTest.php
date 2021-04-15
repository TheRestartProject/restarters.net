<?php

namespace Tests\Feature;

use App\Events\UserLanguageUpdated;
use App\User;

use DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EditLanguageSettingsTests extends TestCase
{
    public function setUp()
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
        // arrange
        config(['restarters.features.discourse_integration' => true]);
        config(['discourse-api.base_url' => 'https://talk.restarters.dev']);
        config(['discourse-api.api_key' => 'XXX']); // change XXX when testing against Discourse instance.
        config(['discourse-api.api_username' => 'neil']);

        $user = factory(User::class)->create(['id' => 67]);

        // act
        $user->language = 'de';
        $user->save();

        // assert
        //Event::assertDispatched(UserLanguageUpdated::class);
    }
}
