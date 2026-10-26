<?php

namespace Tests\Feature\Users;

use App\Group;
use App\Notifications\NewGroupWithinRadius;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * GET /api/v2/users/me/notifications + POST .../read — the API behind the
 * /notifications page (the old /profile/notifications).
 */
class APIv2UserNotificationsTest extends TestCase
{
    private function userWithNotifications(int $count = 2): User
    {
        $user = User::factory()->restarter()->create(['api_token' => 'notif-tok']);
        $group = Group::factory()->create();

        for ($i = 0; $i < $count; $i++) {
            $user->notify(new NewGroupWithinRadius([
                'group_name' => $group->name,
                'group_url' => url('/group/view/'.$group->idgroups),
            ]));
        }
        $this->processQueuedNotifications();

        return $user->fresh();
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->withExceptionHandling()->getJson('/api/v2/users/me/notifications');
        $response->assertStatus(401);
    }

    public function testListReturnsTheUsersNotifications(): void
    {
        $user = $this->userWithNotifications(2);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users/me/notifications?api_token=notif-tok');

        $response->assertSuccessful();
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.total'));
        $this->assertEquals(2, $response->json('meta.unread'));
        $this->assertFalse($response->json('data.0.read'));
    }

    public function testMarkAllAsRead(): void
    {
        $user = $this->userWithNotifications(2);
        $this->actingAs($user);

        $response = $this->postJson('/api/v2/users/me/notifications/read?api_token=notif-tok', []);

        $response->assertSuccessful();
        $this->assertEquals(0, $response->json('data.unread'));
        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
    }

    public function testMarkSingleAsRead(): void
    {
        $user = $this->userWithNotifications(2);
        $this->actingAs($user);
        $firstId = $user->notifications()->first()->id;

        $response = $this->postJson('/api/v2/users/me/notifications/read?api_token=notif-tok', ['id' => $firstId]);

        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.unread'));
        $this->assertNotNull($user->fresh()->notifications()->where('id', $firstId)->first()->read_at);
    }
}
