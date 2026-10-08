<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\User;
use DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Model-level profile behaviour (Discourse-sync event dispatch, lat/lng
 * precision). The route-driven profile-edit tests that used the removed Blade
 * endpoints (/profile/edit-info, /profile/edit-tags, /profile image upload)
 * are now covered by the API suite:
 *   - update + geocoding: APIv2UserProfileTest::testUpdatePersistsFieldsAndGeocodesLocation,
 *     ::testUpdateClearsLatLongOnGeocodeFailure, ::testUpdateValidatesRequiredFields
 *   - skills/host-promotion: APIv2UserProfileTest::testSkillsPersistsAndPromotesToHost
 *   - photo: APIv2UserPhotoTest
 */
class EditProfileTest extends TestCase
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
    public function email_address_update_triggers_discourse_sync(): void
    {
        $this->withoutExceptionHandling();
        Event::fake();

        // arrange
        config(['restarters.features.discourse_integration' => true]);

        $user = User::factory()->create(['id' => 1111177]);

        // act
        $user->email = 'foo@bario.com';
        $user->save();

        // assert
        Event::assertDispatched(UserUpdated::class);
    }

    /** TODO test */
    // The assertion just tells us that the event is dispatched, not much else.
    // In order to actually check it worked, we need to look at Discourse.
    public function other_updates_dont_trigger_discourse_sync()
    {
        $this->withoutExceptionHandling();
        Event::fake();

        // arrange
        config(['restarters.features.discourse_integration' => true]);

        $user = User::factory()->create();

        // act
        $user->name = 'Joe Bloggs';
        $user->save();

        // assert
        // tested manually, need a mock to test this automatically...
    }

    /** test */
    // Check that we can have three digit lat/lngs.
    public function test_three_digit_lat_lng(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        $userId = $user->id;

        $user->name = 'Joe Bloggs';
        $user->latitude = 123.456;
        $user->longitude = 132.654;

        $user->save();

        $user = User::where('id', $userId)->first();
        $this->assertEquals(123.456, $user->latitude);
        $this->assertEquals(132.654, $user->longitude);
    }
}
