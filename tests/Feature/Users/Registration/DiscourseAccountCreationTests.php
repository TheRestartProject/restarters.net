<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Listeners\DiscourseUserEventSubscriber;
use App\User;

use DB;
use Hash;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;

class DiscourseAccountCreationTests extends TestCase
{
    /** @test */
    public function user_registration_triggers_user_registered_event()
    {
        $this->setDiscourseTestEnvironment();

        Event::fake();

        // Register should redirect to dashboard page.
        $response = $this->post('/user/register/', $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
        Event::assertDispatched(UserRegistered::class);
    }

    /** @test */
    public function user_registration_triggers_discourse_sync_attempt()
    {
        $this->setDiscourseTestEnvironment();

        $this->instance(DiscourseUserEventSubscriber::class, Mockery::mock(DiscourseUserEventSubscriber::class, function ($mock) {
            $mock->shouldReceive('onUserRegistered')->once();
        }));

        $response = $this->post('/user/register/',  $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
    }

    /** @test */
    public function user_registration_discourse_sync()
    {
        // We might not have Discourse integration enabled, e.g. when running on Circle.
        if (env('FEATURE__DISCOURSE_INTEGRATION')) {
            $this->setDiscourseTestEnvironment();

            $atts = $this->userAttributes();
            $response = $this->post('/user/register/', $atts);
            $response->assertStatus(302);
            $response->assertRedirect('dashboard');

            $id = User::latest()->first()->id;
            $this->assertNotNull($id);

            // Query Discourse to check the user exists there.
            $endpoint = "/users/by-external/$id.json";

            $client = app('discourse-client');
            $response = $client->request(
                'GET',
                $endpoint
            );

            $json = json_decode($response->getBody()->getContents(), true);
            $this->assertEquals($atts['name'], $json['user']['username']);
        }
    }
}
