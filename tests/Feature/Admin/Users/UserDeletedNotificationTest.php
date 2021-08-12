<?php

namespace Tests\Feature;

use App\Notifications\AdminUserDeleted;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserDeletedNotificationTest extends TestCase
{
    /** @test */
    public function a_notification_is_sent_to_admins_when_a_user_is_deleted()
    {
        Notification::fake();

        /** @var User[] $admins */
        $admins = factory(User::class, 5)->states('Administrator')->create();

        foreach ($admins as $admin) {
            $admin->addPreference('admin-user-deleted');
        }

        $restarter = factory(User::class)->states('Restarter')->create();
        $restarter->delete();

        Notification::assertSentTo(
            [$admins], AdminUserDeleted::class
        );
    }
}
