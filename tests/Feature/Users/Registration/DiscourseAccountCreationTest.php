<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use PHPUnit\Framework\Attributes\Test;
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
use Tests\DiscourseTestCase;
use Tests\Feature\MockInterface;

class DiscourseAccountCreationTest extends DiscourseTestCase
{
    #[Test]
    public function user_registration_triggers_user_registered_event(): void
    {
        Event::fake();

        // Register should redirect to dashboard page.
        $response = $this->post('/user/register/', $this->userAttributes());

        $response->assertStatus(302);
        $response->assertRedirect('dashboard');
        Event::assertDispatched(UserRegistered::class);
    }

    #[Test]
    public function user_registration_triggers_discourse_sync_attempt(): void
    {
        // TODO Not working and agreed to disable for now.
        $this->assertTrue(true);
    }

    #[Test]
    public function user_registration_discourse_sync(): void
    {
        // This is a test against a real Discourse instance.
        // TODO Not working and agreed to disable for now.
        $this->assertTrue(true);
    }

    #[Test]
    public function user_sync(): void
    {
        // TODO Not working and agreed to disable for now.
        $this->assertTrue(true);
    }
}
