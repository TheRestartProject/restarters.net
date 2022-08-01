<?php

namespace Tests\Feature;

use App\Group;
use App\Notifications\NewGroupWithinRadius;
use App\Party;
use App\Role;
use App\User;
use DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testNotificationsPage() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $idgroups = $this->createGroup();
        $group = Group::findOrFail($idgroups);

        $host = factory(User::class)->state('Host')->create();

        Notification::send($host, new NewGroupWithinRadius([
                                                                            'group_name' => $group->name,
                                                                            'group_url' => url('/group/view/'.$idgroups),
                                                                        ]));

        $this->processQueuedNotifications();

        // Should have generated a notification for the approval.
        $this->actingAs($host);
        $rsp = $this->get('/profile/notifications');
        $rsp->assertSee(__('notifications.new_group_title'));
    }
}
