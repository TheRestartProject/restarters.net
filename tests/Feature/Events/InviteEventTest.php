<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Listeners\AddUserToDiscourseThreadForEvent;
use App\Notifications\RSVPEvent;
use App\Party;
use App\Role;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Notifications\JoinEvent;
use Illuminate\Support\Facades\Queue;
use function PHPUnit\Framework\assertEquals;
use Illuminate\Validation\ValidationException;

class InviteEventTest extends TestCase
{
    /**
     * Test notification content.
     */
    public function testInvite(): void
    {
        Notification::fake();

        $this->withoutExceptionHandling();

        $group = Group::factory()->create([
                                              'approved' => true
                                          ]);
        $event = Party::factory()->create([
                                              'group' => $group,
                                              'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                              'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                          ]);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        // Invite a user.
        $user = User::factory()->restarter()->create();

        $response = $this->post('/api/v2/events/'.$event->idevents.'/invites?api_token='.$host->api_token, [
            'emails' => [$user->email],
            'message' => 'Join us, but not in a creepy zombie way',
        ]);
        $response->assertSuccessful();

        Notification::assertSentTo(
            [$user],
            JoinEvent::class,
            function ($notification, $channels, $user) use ($group, $event, $host) {
                $mailData = $notification->toMail($user)->toArray();
                self::assertEquals(__('notifications.join_event_subject', [
                    'groupname' => $group->name
                ], $user->language), $mailData['subject']);

                // Mail should mention the host, message, location and timezone.
                self::assertStringContainsString($host->name, $mailData['introLines'][0]);
                self::assertStringContainsString('creepy', $mailData['introLines'][2]);
                self::assertStringContainsString($event->location, $mailData['introLines'][4]);
                self::assertStringContainsString($event->timezone,  $mailData['introLines'][4]);
                self::assertStringContainsString('/view/', $mailData['introLines'][4]);
                return true;
            }
        );

        // Invited volunteers shouldn't update the count.
        $event->refresh();
        assertEquals(0, $event->volunteers);
    }

    public function testInviteReal(): void
    {
        // Registration itself goes through the token API now (see
        // AuthEndpointsTest); we just need a real user here to drive the
        // invite flow, so create one directly rather than round-tripping
        // through the dead POST /user/register/ Blade route.
        $userAttributes = $this->userAttributes();
        $host = User::factory()->create([
            'name' => $userAttributes['name'],
            'email' => $userAttributes['email'],
        ]);
        $this->actingAs($host);

        // Create group.
        $idgroups = $this->createGroup();
        $group = Group::findOrFail($idgroups);
        $idevents = $this->createEvent($idgroups, 'tomorrow');
        $event = Party::findOrFail($idevents);
        $event->refresh();
        assertEquals(1, $event->volunteers);

        // We want the event handler to kick in and synchronise to Discourse.
        $this->artisan("queue:work --stop-when-empty");

        // Invite a user.
        $user = User::factory()->restarter()->create();

        $response = $this->post('/api/v2/events/'.$event->idevents.'/invites?api_token='.$host->api_token, [
            'emails' => [$user->email],
            'message' => 'Join us, but not in a creepy zombie way',
        ]);
        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.invites_sent'));

        // Check it's in the DB.
        $this->assertDatabaseHas('events_users', [
            'user' => $user->id,
            'event' => $event->idevents,
            'role' => Role::RESTARTER,
        ]);

        // Invited volunteers shouldn't update the count.
        $event->refresh();
        assertEquals(1, $event->volunteers);

        // Admin approves the event.
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);
        $eventData = $event->getAttributes();
        $eventData['id'] = $event->idevents;
        $eventData['moderate'] = 'approve';
        $this->patch('/api/v2/events/'.$event->idevents, $this->eventAttributesToAPI($eventData));

        // As the user...
        $this->actingAs($user);

        // ...join the group.
        $response = $this->post('/api/v2/groups/'.$group->idgroups.'/members/me?api_token='.$user->api_token);
        $response->assertSuccessful();

        // We should see that we have been invited (a hash status, not yet confirmed).
        $eventUser = EventsUsers::where('event', $event->idevents)->where('user', $user->id)->first();
        $this->assertNotNull($eventUser);
        $this->assertNotEquals('1', $eventUser->status);
        $invitation = '/party/accept-invite/'.$event->idevents.'/'.$eventUser->status;

        // Now accept the invitation. GET /party/accept-invite/{id}/{hash} keeps its status-hash
        // DB update server-side (F2-5) but now redirects into the SPA's party page with a
        // ?joined=1 flag instead of Blade - see App\Http\Controllers\PartyController::confirmInvite.
        $response4 = $this->get($invitation);
        $this->assertTrue($response4->isRedirection());
        $redirectTo = $response4->getTargetUrl();
        $frontend = rtrim(config('restarters.frontend_url'), '/');
        $this->assertEquals($frontend.'/party/view/'.$event->idevents.'?joined=1', $redirectTo);

        // Count should now include them.
        $event->refresh();
        assertEquals(2, $event->volunteers);
        $eventUser->refresh();
        $this->assertEquals('1', $eventUser->status);

        // Invite again - different code path when they're already there: the invite is
        // accepted (a confirmed 'status' of 1) so invitesv2 must leave it alone rather than
        // resetting it to a fresh hash. Only the host/coordinator/admin can invite, so switch
        // back to the admin for this call.
        $this->actingAs($admin);
        $response = $this->post('/api/v2/events/'.$event->idevents.'/invites?api_token='.$admin->api_token, [
            'emails' => [$user->email],
            'message' => 'Join us, but not in a creepy zombie way',
        ]);
        $response->assertSuccessful();

        $eventUser->refresh();
        $this->assertEquals('1', $eventUser->status);
    }

    public function testInvitableNotifications(): void
    {
        Queue::fake();
        Notification::fake();
        $this->withoutExceptionHandling();

        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup('Test Group', 'https://therestartproject.org', 'London', 'Some text.', true, true);
        $idevents = $this->createEvent($idgroups, 'tomorrow');

        // Joining should trigger adding to the Discourse thread.  Fake one.
        $event = \App\Party::find($idevents);
        $event->discourse_thread = 123;
        $event->save();

        $group = Group::find($idgroups);
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $event = Party::find($idevents);
        EventsUsers::create([
                                'event' => $event->getKey(),
                                'user' => $host->getKey(),
                                'status' => 1,
                                'role' => 3,
                                'full_name' => null,
                            ]);
        $this->actingAs($host);

        // User joins the group.
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);
        $response2 = $this->post('/api/v2/groups/'.$group->idgroups.'/members/me?api_token='.$user->api_token);
        $response2->assertSuccessful();

        // Invite the user to the event.
        $this->actingAs($host);
        $response5 = $this->post('/api/v2/events/'.$event->idevents.'/invites?api_token='.$host->api_token, [
            'emails' => [$user->email],
            'message' => 'Join us, but not in a creepy zombie way',
        ]);

        $response5->assertSuccessful();

        // As the user...
        $this->actingAs($user);

        // Now accept the invitation, which should trigger adding to the Discourse thread.
        $eu = EventsUsers::where('user', '=', $user->id)->first();
        $invitation = '/party/accept-invite/' . $event->idevents . '/' . $eu->status;

        $response8 = $this->get($invitation);
        $this->assertTrue($response8->isRedirection());
        $redirectTo = $response8->getTargetUrl();
        $frontend = rtrim(config('restarters.frontend_url'), '/');
        $this->assertEquals($frontend.'/party/view/'.$event->idevents.'?joined=1', $redirectTo);

        // This should generate a notification to the host.
        Notification::assertSentTo(
            [$host],
            RSVPEvent::class,
            function ($notification, $channels, $host) use ($user, $event) {
                $mailData = $notification->toMail($host)->toArray();
                self::assertEquals(__('notifications.rsvp_subject', [
                    'name' => $user->name
                ], $host->language), $mailData['subject']);

                // Mail should mention the venue.
                self::assertMatchesRegularExpression ('/' . $event->venue . '/', $mailData['introLines'][0]);

                return true;
            }
        );

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) use ($event, $user) {
            if ($job->class == AddUserToDiscourseThreadForEvent::class) {
                return true;
            }
        });
    }

    public function testInviteViaLink(): void {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $user = User::factory()->restarter()->create([
                                                                          'api_token' => '1234',
                                                                      ]);

        $idgroups = $this->createGroup();
        $group = Group::findOrFail($idgroups);
        $idevents = $this->createEvent($idgroups, 'tomorrow');
        $event = Party::findOrFail($idevents);
        assertEquals(1, $event->volunteers);

        $unique_shareable_code = Fixometer::generateUniqueShareableCode(\App\Party::class, 'shareable_code');
        $event->update([
                           'shareable_code' => $unique_shareable_code,
                       ]);

        // GET /party/invite/{code} is now a thin redirector into the SPA (F2-5): the SPA claims
        // the code statelessly via POST /api/v2/invites/claim
        // (AuthEndpointsTest::testClaimEventShareableCode), which is what actually joins the
        // event now, so this no longer touches the DB or needs the code to exist.
        $this->actingAs($user);
        $frontend = rtrim(config('restarters.frontend_url'), '/');

        $rsp = $this->get('/party/invite/' . $unique_shareable_code);
        $rsp->assertRedirect($frontend.'/party/invite/'.$unique_shareable_code);

        // Invited volunteers shouldn't update the count - nothing joined server-side.
        $event->refresh();
        assertEquals(1, $event->volunteers);

        // An unknown code redirects the same way - no DB lookup happens here any more, so there's
        // nothing to 404 on; the SPA's claim call is what surfaces the unknown-code error.
        $rsp = $this->get('/party/invite/' . $unique_shareable_code . '1');
        $rsp->assertRedirect($frontend.'/party/invite/'.$unique_shareable_code.'1');
    }

    public function testInviteNonUsers(): void {
        Notification::fake();

        $this->withoutExceptionHandling();

        $group = Group::factory()->create([
                                              'approved' => true
                                          ]);
        $event = Party::factory()->create([
                                              'group' => $group,
                                              'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                              'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                          ]);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        // Invite a user.
        $user = User::factory()->restarter()->create();

        $response = $this->post('/api/v2/events/'.$event->idevents.'/invites?api_token='.$host->api_token, [
            'emails' => ['test@test.com'],
            'message' => 'Join us, but not in a creepy zombie way',
        ]);

        $response->assertSuccessful();
    }

    public function testInviteNoUsers(): void {
        Notification::fake();

        $this->withoutExceptionHandling();

        $group = Group::factory()->create([
                                              'approved' => true
                                          ]);
        $event = Party::factory()->create([
                                              'group' => $group,
                                              'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                              'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                          ]);

        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        // Invite a user.
        $user = User::factory()->restarter()->create();

        $this->expectException(ValidationException::class);

        $response = $this->post('/api/v2/events/'.$event->idevents.'/invites?api_token='.$host->api_token, [
            'emails' => [],
            'message' => 'Join us, but not in a creepy zombie way',
        ]);
    }

    /**
     * @dataProvider invalidEmailProvider
     *
     * Note: the dead /party/invite route used a Delimited('email') validation rule that threw a
     * ValidationException for any malformed address. The live invitesv2 endpoint (EventAttendanceController)
     * instead accepts the request and reports malformed addresses back in the 'invalid' array of the
     * response (see APIv2EventVolunteersTest::testInviteReportsMalformedAddressesAsInvalid) - so this now
     * asserts that graceful behaviour instead of an exception.
     */
    public function testInviteInvalidEmail($email, $valid): void
    {
        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $idgroups = $this->createGroup();
        $group = Group::findOrFail($idgroups);
        $idevents = $this->createEvent($idgroups, 'tomorrow');
        $event = Party::findOrFail($idevents);

        $response = $this->post('/api/v2/events/'.$event->idevents.'/invites?api_token='.$admin->api_token, [
            'emails' => [$email],
            'message' => 'Join us, but not in a creepy zombie way',
        ]);

        $response->assertSuccessful();

        if ($valid) {
            $this->assertEquals([], $response->json('data.invalid'));
        } else {
            $this->assertEquals([$email], $response->json('data.invalid'));
        }
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
