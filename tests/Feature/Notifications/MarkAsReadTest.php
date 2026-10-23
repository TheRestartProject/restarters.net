<?php

namespace Tests\Feature\Notifications;

use App\Notifications\NewGroupWithinRadius;
use App\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Marking in-app notifications read, post-cutover via
 * POST /api/v2/users/me/notifications/read (UserController::
 * markMyNotificationsReadv2) — the old GET route('markAsRead') is gone.
 */
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
        $user = User::factory()->host()->create(['api_token' => 'mar-single']);
        $notification = $this->createUnreadNotification($user);

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $this->actingAs($user);
        $response = $this->postJson('/api/v2/users/me/notifications/read?api_token=mar-single', ['id' => $notification->id]);
        $response->assertSuccessful();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function testMarkAllNotificationsAsRead(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'mar-all']);
        $this->createUnreadNotification($user);
        $this->createUnreadNotification($user);

        $this->assertEquals(2, $user->fresh()->unreadNotifications()->count());

        $this->actingAs($user);
        $response = $this->postJson('/api/v2/users/me/notifications/read?api_token=mar-all', []);
        $response->assertSuccessful();

        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
    }

    public function testMarkAsReadDoesNotAffectOtherUsersNotifications(): void
    {
        $user1 = User::factory()->host()->create(['api_token' => 'mar-u1']);
        $user2 = User::factory()->host()->create(['api_token' => 'mar-u2']);

        $notification1 = $this->createUnreadNotification($user1);
        $notification2 = $this->createUnreadNotification($user2);

        $this->actingAs($user1);
        $this->postJson('/api/v2/users/me/notifications/read?api_token=mar-u1', ['id' => $notification1->id]);

        $this->assertNotNull($notification1->fresh()->read_at);
        $this->assertNull($notification2->fresh()->read_at);
    }
}
