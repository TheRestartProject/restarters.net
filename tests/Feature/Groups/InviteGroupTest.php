<?php

namespace Tests\Feature;

use App\Group;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\Helpers\Fixometer;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\Role;
use App\User;
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
        // Small delay to ensure any database commits or cache invalidations are complete
        usleep(100000); // 100ms

        $this->assertVueProperties($response2, [
            [],
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        // Check the group membership counts directly on the model (these are no longer exposed to the Blade
        // view/Vue props - GroupPage.vue now gets group data, including confirmed hosts/restarters counts, from
        // GET /api/v2/groups/{id} - but the underlying counting logic being tested here (an invited-but-not-yet-
        // accepted restarter shouldn't count as confirmed) is independent of how it's surfaced to the page).
        $freshGroup = Group::find($group->idgroups);

        // Debug info for CI failures - collect group membership state
        $groupMembers = DB::table('users_groups')
            ->where('group', $group->idgroups)
            ->get()
            ->map(function($m) {
                return "user={$m->user}, role={$m->role}, status={$m->status}";
            })
            ->implode('; ');
        $debugInfo = sprintf(
            "Group ID: %d, Host ID: %d, User ID: %d, Members: [%s]",
            $group->idgroups,
            $host->id,
            $user->id,
            $groupMembers
        );

        $this->assertEquals(1, $freshGroup->all_hosts_count, "all_hosts_count mismatch. Debug: $debugInfo");
        $this->assertEquals(1, $freshGroup->all_confirmed_hosts_count, "all_confirmed_hosts_count mismatch. Debug: $debugInfo");
        $this->assertEquals(1, $freshGroup->all_restarters_count, "all_restarters_count mismatch. Debug: $debugInfo");
        $this->assertEquals(0, $freshGroup->all_confirmed_restarters_count, "all_confirmed_restarters_count mismatch. Debug: $debugInfo");

        // The API endpoint GroupPage.vue fetches from should also reflect the confirmed counts (unconfirmed
        // members are not exposed there, matching what the page displays to end users).
        $apiResponse = $this->get("/api/v2/groups/{$group->idgroups}");
        $apiResponse->assertSuccessful();
        $apiGroup = json_decode($apiResponse->getContent(), true)['data'];
        $this->assertEquals(1, $apiGroup['hosts'], "API hosts mismatch. Debug: $debugInfo");
        $this->assertEquals(0, $apiGroup['restarters'], "API restarters mismatch. Debug: $debugInfo");

        // Now accept the invite. GET /group/accept-invite/{id}/{hash} keeps its status-hash DB
        // update server-side (F2-5) but now redirects into the SPA's group page with a ?joined=1
        // flag instead of Blade + session flash - see App\Http\Controllers\GroupController::confirmInvite.
        preg_match('/href="(\/group\/accept-invite.*?)"/', $response2->getContent(), $matches);
        $invitation = $matches[1];
        $response3 = $this->get($invitation);
        $this->assertTrue($response3->isRedirection());
        $redirectTo = $response3->getTargetUrl();
        $frontend = rtrim(config('restarters.frontend_url'), '/');
        $this->assertEquals($frontend.'/group/view/'.$group->idgroups.'?joined=1', $redirectTo);

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
        $this->assertVueProperties($response4, [
            [],
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        $freshGroup = Group::find($group->idgroups);
        $this->assertEquals(1, $freshGroup->all_hosts_count);
        $this->assertEquals(1, $freshGroup->all_confirmed_hosts_count);
        $this->assertEquals(1, $freshGroup->all_restarters_count);
        $this->assertEquals(1, $freshGroup->all_confirmed_restarters_count);

        $apiResponse = $this->get("/api/v2/groups/{$group->idgroups}");
        $apiResponse->assertSuccessful();
        $apiGroup = json_decode($apiResponse->getContent(), true)['data'];
        $this->assertEquals(1, $apiGroup['hosts']);
        $this->assertEquals(1, $apiGroup['restarters']);

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

        $unique_shareable_code = Fixometer::generateUniqueShareableCode(\App\Group::class, 'shareable_code');
        $group->update([
           'shareable_code' => $unique_shareable_code,
        ]);

        $host = User::factory()->host()->create();
        $this->actingAs($host);

        $this->actingAs($host);
        $response = $this->get('/group/view/'.$group->idgroups);

        // Should see shareable code in there.
        $response->assertSee($group->shareable_link);

        // Now pretend we've received that code, via the legacy backend URL some older-style
        // links may still use (as opposed to $group->shareable_link, which already points
        // straight at the SPA - see APIv2GroupResourceFieldsTest). GET /group/invite/{code} is
        // now a thin redirector into the SPA (F2-5): the SPA claims the code statelessly via
        // POST /api/v2/invites/claim (AuthEndpointsTest::testClaimInviteEndpoint), so this no
        // longer needs to be authenticated, look the code up, or create session state.
        Auth::logout();
        $frontend = rtrim(config('restarters.frontend_url'), '/');
        $response = $this->get('/group/invite/'.$unique_shareable_code);

        $this->assertTrue($response->isRedirection());
        $response->assertRedirect($frontend.'/group/invite/'.$unique_shareable_code);
    }

    /**
     * @dataProvider invalidEmailProvider
     */
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
}
