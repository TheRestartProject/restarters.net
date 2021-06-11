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
        $this->instance(DiscourseUserEventSubscriber::class, Mockery::mock(DiscourseUserEventSubscriber::class, function ($mock) {
            $mock->shouldReceive('onUserRegistered')->once();
        }));

        config(['restarters.features.discourse_integration' => true]);

        $response = $this->post('/user/register/',  $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
    }

    // TODO This would need a test Discourse instance inside the CircleCI environment.
    public function user_registration_discourse_sync_attempt()
    {
        config(['restarters.features.discourse_integration' => true]);
        config(['discourse-api.base_url' => 'https://talk.restarters.dev']);
        config(['discourse-api.sso_secret' => 'XXX']); // change XXX to sso secret when testing against test Discourse instance.
        config(['discourse-api.api_key' => 'XXX']); // change XXX when testing against test Discourse instance.
        config(['discourse-api.api_username' => 'neil']);

        $response = $this->post('/user/register/', $this->userAttributes());
    }
}
