<?php

namespace Tests\Feature;

use App\Events\UserDeleted;
use App\Events\UserRegistered;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\DiscourseUserEventSubscriber;
use App\Providers\DiscourseServiceProvider;
use App\Role;
use App\User;
use DB;
use Hash;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Feature\MockInterface;

class DiscourseAccountDeletionTest extends TestCase
{
    /** @test */
    public function user_deletion_triggers_anonymise()
    {
        // Soft-deleting a user should trigger a call to the method which will anonymise the user on Discourse.
        config('restarters.features.discourse_integration', true);
        $this->instance(DiscourseUserEventSubscriber::class, Mockery::mock(DiscourseUserEventSubscriber::class, function ($mock) {
            $mock->shouldReceive('onUserDeleted')->once();
        }));

        Event::fake();

        $user = User::factory()->restarter()->create();
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $response = $this->post('/user/soft-delete', [
            'id' => $user->id
        ]);
        $response->assertSessionHas('danger');
        $this->assertTrue($response->isRedirection());

        Event::assertDispatched(UserDeleted::class);
    }
}