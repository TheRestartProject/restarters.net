<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\DiscourseUserEventSubscriber;
use App\Providers\DiscourseServiceProvider;
use App\User;
use DB;
use Hash;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Feature\MockInterface;

class DiscourseAccountCreationTest extends TestCase
{
    /** @test */
    public function user_registration_triggers_user_registered_event()
    {
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
        if (config('restarters.features.discourse_integration')) {
            $this->instance(DiscourseUserEventSubscriber::class,
                Mockery::mock(DiscourseUserEventSubscriber::class, function ($mock) {
                    $mock->shouldReceive('onUserRegistered')->once();
                }));

            $response = $this->post('/user/register/', $this->userAttributes());

            $response->assertStatus(302);
            $response->assertRedirect('dashboard');
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function user_registration_discourse_sync()
    {
        if (config('restarters.features.discourse_integration')) {
            // This is a test against a real Discourse instance.
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
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function user_sync()
    {
        $this->withExceptionHandling();

        Log::shouldReceive('info')
            ->with('Failed to Save Venue');
        $this->artisan('sync:discourseusernames')
            ->assertExitCode(0);
    }
}
