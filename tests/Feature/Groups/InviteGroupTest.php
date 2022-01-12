<?php

namespace Tests\Feature;

use App\Group;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InviteGroupTest extends TestCase
{
    public function testInvite()
    {
        Notification::fake();
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $host = factory(User::class)->states('Host')->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        // Invite a user.
        $user = factory(User::class)->states('Restarter')->create();

        $response = $this->post('/group/invite', [
            'group_name' => $group->name,
            'group_id' => $group->idgroups,
            'manual_invite_box' => $user->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response->assertSessionHas('success');

        // Invitation should generate a notification.
        Notification::assertSentTo(
            [$user],
            JoinGroup::class,
            function ($notification, $channels, $user) use ($group, $host) {
                $mailData = $notification->toMail($host)->toArray();
                self::assertEquals(__('notifications.join_group_title', [
                    'name' => $host->name,
                    'group' => $group->name
                ], $user->language), $mailData['subject']);
                return true;
            }
        );

        // We should see that we have been invited.
        $this->actingAs($user);
        $response = $this->get('/group/view/'.$group->idgroups);
        $response->assertSee('You have an invitation to this group.');

        // Check the counts.
        $props = $this->assertVueProperties($response, [
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        $initialGroup = json_decode($props[0][':initial-group'], true);
        $this->assertEquals(1, $initialGroup['all_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_confirmed_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_restarters_count']);
        $this->assertEquals(0, $initialGroup['all_confirmed_restarters_count']);

        // Now accept the invite.
        preg_match('/href="(\/group\/accept-invite.*?)"/', $response->getContent(), $matches);
        $invitation = $matches[1];
        $response = $this->get($invitation);
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/group/view/'.$group->idgroups));
        $response->assertSessionHas('success');

        // Acceptance should notify the host.
        Notification::assertSentTo(
            [$host],
            NewGroupMember::class,
            function ($notification, $channels, $host) use ($group, $user) {
                $mailData = $notification->toMail($host)->toArray();
                self::assertEquals(__('notifications.new_member_subject', [
                    'name' => $group->name
                ], $host->language), $mailData['subject']);
                return true;
            }
        );

        // Check the counts have changed.
        $response = $this->get('/group/view/'.$group->idgroups);
        $props = $this->assertVueProperties($response, [
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        $initialGroup = json_decode($props[0][':initial-group'], true);
        $this->assertEquals(1, $initialGroup['all_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_confirmed_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_restarters_count']);
        $this->assertEquals(1, $initialGroup['all_confirmed_restarters_count']);
    }
}
