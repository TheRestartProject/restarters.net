<?php

namespace Tests\Feature\Notifications;

use App\Group;
use App\Notifications\NewGroupWithinRadius;
use App\Role;
use App\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MarkAsReadTest extends TestCase
{
    private function createUnreadNotification(User $user): DatabaseNotification
    {
        Notification::send($user, new NewGroupWithinRadius([
            'group_name' => 'Test Group',
            'group_url' => url('/group/view/1'),
        ]));

        $this->processQueuedNotifications();

        return $user->fresh()->unreadNotifications()->first();
    }

    public function testMarkSingleNotificationAsRead(): void
    {
        $user = User::factory()->host()->create();
        $notification = $this->createUnreadNotification($user);

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $this->actingAs($user);
        $response = $this->get(route('markAsRead', ['id' => $notification->id]));
        $response->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function testMarkAllNotificationsAsRead(): void
    {
        $user = User::factory()->host()->create();
        $this->createUnreadNotification($user);
        $this->createUnreadNotification($user);

        $this->assertEquals(2, $user->fresh()->unreadNotifications()->count());

        $this->actingAs($user);
        $response = $this->get(route('markAsRead'));
        $response->assertRedirect();

        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
    }

    public function testMarkAsReadDoesNotAffectOtherUsersNotifications(): void
    {
        $user1 = User::factory()->host()->create();
        $user2 = User::factory()->host()->create();

        $notification1 = $this->createUnreadNotification($user1);
        $notification2 = $this->createUnreadNotification($user2);

        $this->actingAs($user1);
        $this->get(route('markAsRead', ['id' => $notification1->id]));

        $this->assertNotNull($notification1->fresh()->read_at);
        $this->assertNull($notification2->fresh()->read_at);
    }
}
