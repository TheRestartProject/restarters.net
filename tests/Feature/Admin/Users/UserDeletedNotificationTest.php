<?php

namespace Tests\Feature;

use App\Events\UserDeleted;
use PHPUnit\Framework\Attributes\Test;
use App\Listeners\DiscourseUserEventSubscriber;
use App\Notifications\AdminUserDeleted;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;

class UserDeletedNotificationTest extends TestCase
{
    #[Test]
    public function a_notification_is_sent_to_admins_when_a_user_is_deleted(): void
    {
        Notification::fake();

        /** @var User[] $admins */
        $admins = User::factory()->count(5)->administrator()->create();

        foreach ($admins as $admin) {
            $admin->addPreference('admin-user-deleted');
        }

        $restarter = User::factory()->restarter()->create();
        $restarter->delete();

        $this->artisan("queue:work --stop-when-empty");

        Notification::assertSentTo(
            [$admins], AdminUserDeleted::class
        );
    }
}
