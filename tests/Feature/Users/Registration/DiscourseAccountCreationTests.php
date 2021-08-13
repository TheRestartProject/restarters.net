<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Listeners\DiscourseUserEventSubscriber;
use App\User;
use DB;
use Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

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

        $response = $this->post('/user/register/', $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
    }

    /** @test */
    public function user_registration_discourse_sync()
    {
        // We might not have Discourse integration enabled, e.g. when running on Circle.  This is a hacky way of
        // checking that.  If we turn off FEATURE__DISCOURSE_INTEGRATION then we get errors when loading the
        // provider, which seem tricky to solve.
        if (! env('CIRCLECI')) {
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
        } else {
            $this->assertTrue((true));
        }
    }

    /** @test */
    public function user_sync() {
        $this->withExceptionHandling();

        if (! env('CIRCLECI')) {
            Log::shouldReceive('info')
                ->with('Failed to Save Venue');
            $this->artisan('sync:discourseusernames')
                ->assertExitCode(0);
        } else {
            $this->assertTrue((true));
        }
    }
}
