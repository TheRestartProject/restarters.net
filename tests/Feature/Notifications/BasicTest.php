<?php

namespace Tests\Feature;

use App\Group;
use App\Notifications\NewGroupWithinRadius;
use App\Role;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testNotificationsPage(): void {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $idgroups = $this->createGroup();
        $group = Group::findOrFail($idgroups);

        $host = User::factory()->host()->create();

        Notification::send($host, new NewGroupWithinRadius([
                                                                            'group_name' => $group->name,
                                                                            'group_url' => url('/group/view/'.$idgroups),
                                                                        ]));

        $this->processQueuedNotifications();

        // Should have generated a notification for the approval.
        $this->assertEquals(1, $host->unreadNotifications()->count());
        $notification = $host->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals(NewGroupWithinRadius::class, $notification->type);
        $this->assertEquals(__('notifications.new_group_title'), $notification->data['title']);

        // /profile/notifications is dead (Nuxt cutover); GET /api/users/{id}/notifications
        // is the live equivalent, though it only exposes counts (not the notification body) -
        // confirm it reflects the unread notification we just generated.
        $this->actingAs($host);
        $rsp = $this->get('/api/users/'.$host->id.'/notifications');
        $rsp->assertSuccessful();
        $json = json_decode($rsp->getContent(), true);
        $this->assertEquals(1, $json['restarters']);

        // There is no live mark-as-read API endpoint (route:list has none), so assert the
        // mark-as-read behaviour directly on the model instead of via a dead HTTP route.
        $notification->markAsRead();
        $this->assertEquals(0, $host->unreadNotifications()->count());
    }
}
