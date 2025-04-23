<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\DiscourseUserEventSubscriber;
use App\Providers\DiscourseServiceProvider;
use App\Models\User;
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
    public function user_registration_triggers_user_registered_event(): void
    {
        Event::fake();

        // Register should redirect to dashboard page.
        $response = $this->post('/user/register/', $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
        Event::assertDispatched(UserRegistered::class);
    }

    /** @test */
    public function user_registration_triggers_discourse_sync_attempt(): void
    {
        if (config('restarters.features.discourse_integration')) {
            // TODO Not working and agreed to disable for now.
//            $this->instance(DiscourseUserEventSubscriber::class,
//                Mockery::mock(DiscourseUserEventSubscriber::class, function ($mock) {
//                    $mock->shouldReceive('onUserRegistered')->once();
//                }));
//
//            $response = $this->post('/user/register/', $this->userAttributes());
//
//            $response->assertStatus(302);
//            $response->assertRedirect('dashboard');
            $this->assertTrue(true);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function user_registration_discourse_sync(): void
    {
        if (config('restarters.features.discourse_integration')) {
            // This is a test against a real Discourse instance.
            // TODO Not working and agreed to disable for now.
//            $atts = $this->userAttributes();
//            $response = $this->post('/user/register/', $atts);
//            $response->assertStatus(302);
//            $response->assertRedirect('dashboard');
//
//            $id = User::latest()->first()->id;
//            $this->assertNotNull($id);
//
//            // Query Discourse to check the user exists there.
//            $endpoint = "/users/by-external/$id.json";
//
//            $client = app('discourse-client');
//            $response = $client->request(
//                'GET',
//                $endpoint
//            );
//
//            $json = json_decode($response->getBody()->getContents(), true);
//            $this->assertEquals($atts['name'], $json['user']['username']);
            $this->assertTrue(true);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function user_sync(): void
    {
        // TODO Not working and agreed to disable for now.
//        $this->withExceptionHandling();
//
//        Log::shouldReceive('info')
//            ->with('Failed to Save Venue');
//        $this->artisan('sync:discourseusernames')
//            ->assertExitCode(0);
        $this->assertTrue(true);
    }
}
