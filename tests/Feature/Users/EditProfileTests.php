<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EditProfileTests extends TestCase
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
    public function email_address_update_triggers_discourse_sync()
    {
        $this->withoutExceptionHandling();
        Event::fake();

        // arrange
        config(['restarters.features.discourse_integration' => true]);

        $user = factory(User::class)->create(['id' => 1111177]);

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

        $user = factory(User::class)->create();

        // act
        $user->name = 'Joe Bloggs';
        $user->save();

        // assert
        // tested manually, need a mock to test this automatically...
    }

    /** test */
    // Check that we can have three digit lat/lngs.
    public function test_three_digit_lat_lng()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $userId = $user->id;

        $user->name = 'Joe Bloggs';
        $user->latitude = 123.456;
        $user->longitude = 132.654;

        $user->save();

        $user = User::where('id', $userId)->first();
        $this->assertEquals(123.456, $user->latitude);
        $this->assertEquals(132.654, $user->longitude);
    }

    /** test */
    // Check that we can update the location.
    public function test_location_update()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $this->post('/profile/edit-info', [
            'name' => $user->name,
            'email' => $user->email,
            'age' => $user->age,
            'country' => 'GBR',
            'townCity' => 'London',
        ]);

        $user = $user->fresh();
        $this->assertEquals(51.5073509, $user->latitude);
        $this->assertEquals(-0.1277583, $user->longitude);

        $this->post('/profile/edit-info', [
            'name' => $user->name,
            'email' => $user->email,
            'age' => $user->age,
            'country' => 'GBR',
            'townCity' => 'zzzzzzz',
        ]);

        $user = $user->fresh();
        $this->assertNull($user->latitude);
        $this->assertNull($user->longitude);
    }
}
