<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\Group;
use App\Helpers\Fixometer;
use App\Role;
use App\Skills;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    public function email_address_update_triggers_discourse_sync()
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
    public function test_three_digit_lat_lng()
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

    /** test */
    // Check that we can update the location.
    public function test_location_update()
    {
        $user = User::factory()->create();
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

        $good = Config::get('MAPBOX_TOKEN');
        Config::set('MAPBOX_TOKEN', 'zzz');

        // Supply the id.
        $this->post('/profile/edit-info', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'age' => $user->age,
            'country' => 'GBR',
            'townCity' => 'ZZZZ',
        ]);

        Config::set('MAPBOX_TOKEN', $good);

        $user = $user->fresh();
        $this->assertNull($user->latitude);
        $this->assertNull($user->longitude);
    }

    public function idProvider() {
        return [
            [ TRUE ],
            [ FALSE ]
        ];
    }

    /**
     * @test
     * @dataProvider idProvider
     */
    public function test_tags_update($id) {
        $user = User::factory()->create();
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
        $params = [
            'tags' => [ $skill1->id, $skill2->id ]
        ];

        if ($id) {
            $params['id'] = $user->id;
        };

        $response = $this->post('/profile/edit-tags', $params);

        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('message');

        // Check it shows.
        $response2 = $this->get('/profile/edit');
        $response2->assertSee('selected>UT1</option>', false);
        $response2->assertSee('selected>UT2</option>', false);

        // Should have promoted to host because we have a category 1 skill.
        $user->refresh();
        self::assertEquals(Role::HOST, $user->role);

        // Try to edit someone else's profile - should fail.
        $this->expectException(NotFoundHttpException::class);
        $response = $this->get('/profile/edit/' . ($user->id + 1));
    }

    /**
     * @test
     * @dataProvider idProvider
     */
    public function image_upload($id) {
        Storage::fake('avatars');
        $user = User::factory()->create();
        $this->actingAs($user);

        // Try with no file.
        $response = $this->json('POST', '/profile/edit-photo', []);
        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('error');

        // We don't upload files in a standard Laravel way, so testing upload is a bit of a hack.
        $_FILES = [
            'profilePhoto' => [
                'error'    => "0",
                'name'     => 'avatar.jpg',
                'size'     => 123,
                'tmp_name' => __FILE__,   // use THIS file - a real file
                'type'     => 'image/jpg'
            ]
        ];

        $params = [];

        if ($id) {
            $params['id'] = $id;
        }

        $response = $this->json('POST', '/profile/edit-photo', $params);
        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('message');

        // And again, which will test the case of overwriting.
        $response = $this->json('POST', '/profile/edit-photo', $params);
        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('message');
    }

    /**
     * @test
     */
    public function edit_profile() {
        $user = User::factory()->create();
        $this->actingAs($user);

        $userData = $user->getAttributes();

        $response = $this->json('POST', '/profile/edit-info', []);
        $errors = session('errors')->getMessages();
        $this->assertEquals(4, count($errors));

        $response = $this->json('POST', '/profile/edit-info', [
            'name' => $userData['name'] . '1',
            'age' =>  $userData['age']  + 1,
            'email' => $userData['email'] . '1',
            'country' => 'GBR',
        ]);
        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('message');
    }
}
