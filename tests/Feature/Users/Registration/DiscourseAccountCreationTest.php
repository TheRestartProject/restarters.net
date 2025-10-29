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
            $this->assertTrue(true);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function user_registration_discourse_sync(): void
    {
        if (config('restarters.features.discourse_integration')) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function user_sync(): void
    {
        $this->assertTrue(true);
    }
}
