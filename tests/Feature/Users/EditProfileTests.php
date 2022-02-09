<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\Helpers\Fixometer;
use App\Role;
use App\Skills;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
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
        $this->assertEquals(51.507, round($user->latitude, 3));
        $this->assertEquals(-0.128, round($user->longitude, 3));

        $good = Config::get('GOOGLE_API_CONSOLE_KEY');
        Config::set('GOOGLE_API_CONSOLE_KEY', 'zzz');

        $this->post('/profile/edit-info', [
            'name' => $user->name,
            'email' => $user->email,
            'age' => $user->age,
            'country' => 'GBR',
            'townCity' => 'ZZZZ',
        ]);

        Config::set('GOOGLE_API_CONSOLE_KEY', $good);

        $user = $user->fresh();
        $this->assertNull($user->latitude);
        $this->assertNull($user->longitude);
    }

    /** test */
    public function test_tags_update() {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $skill1 = Skills::create([
                                     'skill_name'  => 'UT1',
                                     'description' => 'Planning',
                                     'category' => 1
                                 ]);
        $skill2 = Skills::create([
                                    'skill_name'  => 'UT2',
                                    'description' => 'Unit Testing',
                                    'category' => 2
                                ]);

        // Add this skill.
        $response = $this->post('/profile/edit-tags', [
            'tags' => [ $skill1->id, $skill2->id ]
        ]);

        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('message');

        // Check it shows.
        $response2 = $this->get('/profile/edit');
        $response2->assertSee('selected>UT1</option>');
        $response2->assertSee('selected>UT2</option>');

        // Should have promoted to host because we have a category 1 skill.
        $user->refresh();
        self::assertEquals(Role::HOST, $user->role);
    }
}
