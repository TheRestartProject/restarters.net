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
        $admins = User::factory()->count(5)->administrator()->create();

        foreach ($admins as $admin) {
            $admin->addPreference('admin-user-deleted');
        }

        $restarter = User::factory()->restarter()->create();
        $restarter->delete();

        Notification::assertSentTo(
            [$admins], AdminUserDeleted::class
        );
    }
}
