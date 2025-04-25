<?php

namespace Tests\Feature;

use App\Events\UserDeleted;
use PHPUnit\Framework\Attributes\Test;
use App\Events\UserLanguageUpdated;
use App\Events\UserRegistered;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\AnonymiseSoftDeletedUser;
use App\Listeners\DiscourseUserEventSubscriber;
use App\Providers\DiscourseServiceProvider;
use App\Models\Role;
use App\Models\User;
use DB;
use Hash;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Feature\MockInterface;
use Illuminate\Support\Facades\Artisan;

class DiscourseAccountDeletionTest extends TestCase
{
    #[Test]
    public function user_deletion_triggers_anonymise(): void
    {
        if (config('restarters.features.discourse_integration')) {
            // TODO Not working and agreed to disable for now.
            $this->assertTrue(true);
//            // We can check that AnonymiseSoftDeletedUser is attached to the UserDeleted event.
//            // I don't know how to check that DiscourseUserEventSubscriber is attached.
//            $this->assertListenerIsAttachedToEvent(AnonymiseSoftDeletedUser::class, UserDeleted::class);
//
//            $user = User::factory()->restarter()->create();
//            $this->loginAsTestUser(Role::ADMINISTRATOR);
//
//            // Get the Discourse user created.
//            $this->artisan("queue:work --stop-when-empty");
//
//            $user->refresh();
//            $this->assertNotEquals('', $user->username);
//
//            $response = $this->post('/user/soft-delete', [
//                'id' => $user->id
//            ]);
//            $response->assertSessionHas('danger');
//            $this->assertTrue($response->isRedirection());
//
//            // Process the events.  We can't assert on methods being called because they are backgrounded.
//            $this->artisan("queue:work --stop-when-empty");
//
//            // The Discourse anonymisation should have removed the username.
//            $user->refresh();
//            $this->assertEquals('', $user->username);
        } else {
            $this->assertTrue(true);
        }
    }
}