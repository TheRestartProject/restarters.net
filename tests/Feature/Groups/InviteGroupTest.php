<?php

namespace Tests\Feature;

use App\Models\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\Helpers\Fixometer;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Models\Party;
use App\Models\Role;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Auth;
use Illuminate\Validation\ValidationException;

class InviteGroupTest extends TestCase
{
    public function testInvite(): void
    {
        Notification::fake();
        $this->withoutExceptionHandling();

        $group = Group::factory()->create();
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        // Invite a user.
        $user = User::factory()->restarter()->create();

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

        // Create an event.  Should not generate a notification to users who are invited but not yet accepted.
        $idevents = $this->createEvent($group->idgroups, 'tomorrow');
        Party::find($idevents)->approve();
        $this->artisan("queue:work --stop-when-empty");

        Notification::assertNotSentTo(
            [$user], NotifyRestartersOfNewEvent::class
        );

        // We should see that we have been invited to the group.
        $this->actingAs($user);
        $response2 = $this->get('/group/view/'.$group->idgroups);
        $response2->assertSee('You have an invitation to this group.');

        // Check the counts.
        $props = $this->assertVueProperties($response2, [
            [],
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        $initialGroup = json_decode($props[1][':initial-group'], true);
        $this->assertEquals(1, $initialGroup['all_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_confirmed_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_restarters_count']);
        $this->assertEquals(0, $initialGroup['all_confirmed_restarters_count']);

        // Now accept the invite.
        preg_match('/href="(\/group\/accept-invite.*?)"/', $response2->getContent(), $matches);
        $invitation = $matches[1];
        $response3 = $this->get($invitation);
        $this->assertTrue($response3->isRedirection());
        $redirectTo = $response3->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/group/view/'.$group->idgroups));
        $response3->assertSessionHas('success');

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
        $response4 = $this->get('/group/view/'.$group->idgroups);
        $props = $this->assertVueProperties($response4, [
            [],
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        $initialGroup = json_decode($props[1][':initial-group'], true);
        $this->assertEquals(1, $initialGroup['all_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_confirmed_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_restarters_count']);
        $this->assertEquals(1, $initialGroup['all_confirmed_restarters_count']);

        // Create another event.  Should now generate a notification.
        $this->actingAs($host);
        $idevents = $this->createEvent($group->idgroups, '+7 day');
        Party::find($idevents)->approve();
        $this->artisan("queue:work --stop-when-empty");

        Notification::assertSentTo(
            [$user], NotifyRestartersOfNewEvent::class
        );
    }

    public function testInviteViaLink(): void {
        $group = Group::factory()->create();

        $unique_shareable_code = Fixometer::generateUniqueShareableCode(\App\Models\Group::class, 'shareable_code');
        $group->update([
           'shareable_code' => $unique_shareable_code,
        ]);

        $host = User::factory()->host()->create();
        $this->actingAs($host);

        $this->actingAs($host);
        $response = $this->get('/group/view/'.$group->idgroups);

        // Should see shareable code in there.
        $response->assertSee($group->shareable_link);

        // Now pretend we've received that code.
        Auth::logout();
        $response = $this->get($group->shareable_link);

        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/user/register'));
    }

    #[DataProvider('invalidEmailProvider')]
    public function testInviteInvalidEmail($email, $valid): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $idgroups = $this->createGroup();
        $group = Group::findOrFail($idgroups);

        if (!$valid) {
            $this->expectException(ValidationException::class);
        }

        $this->post('/group/invite', [
            'group_name' => $group->name,
            'group_id' => $group->idgroups,
            'manual_invite_box' => $email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);
    }

    public function invalidEmailProvider(): array
    {
        return [
            ['test@test.com', true],
            ['invalidmail', false],
            ['invalidmail@', false],
            ['test@test.com, invalidmail', false]
        ];
    }

    public function testInviteNearby(): void {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $idgroups = $this->createGroup();
        $group = Group::findOrFail($idgroups);

        // Create a user near the group.
        $user = User::factory()->create();
        $userId = $user->id;
        $user->name = 'Joe Bloggs';
        $user->latitude = $group->latitude;
        $user->longitude = $group->longitude;
        $user->save();

        $rsp = $this->get("/group/nearbyinvite/{$idgroups}/$userId");
        $rsp->assertRedirect();
        $rsp->assertSessionHas('success');

        // Run again for coverage.
        $rsp = $this->get("/group/nearbyinvite/{$idgroups}/$userId");
        $rsp->assertRedirect();
        $rsp->assertSessionHas('success');
    }
}
