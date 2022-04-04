<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Notifications\RSVPEvent;
use App\Party;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Notifications\JoinEvent;

class InviteEventTest extends TestCase
{
    public function testInvite()
    {
        Notification::fake();

        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create([
                                                   'wordpress_post_id' => '99999'
                                               ]);
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                                   'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                               ]);

        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        // Invite a user.
        $user = factory(User::class)->states('Restarter')->create();

        $response = $this->post('/party/invite', [
            'group_name' => $group->name,
            'event_id' => $event->idevents,
            'manual_invite_box' => $user->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        Notification::assertSentTo(
            [$user],
            JoinEvent::class,
            function ($notification, $channels, $user) use ($group, $event, $host) {
                $mailData = $notification->toMail($user)->toArray();
                self::assertEquals(__('notifications.join_event_subject', [
                    'groupname' => $group->name
                ], $user->language), $mailData['subject']);

                // Mail should mention the host, message and location.
                self::assertRegexp('/' . $host->name . '/', $mailData['introLines'][0]);
                self::assertRegexp('/creepy/', $mailData['introLines'][2]);
                self::assertRegexp('/' . $event->location  . '/', $mailData['introLines'][4]);

                // Render to HTML to check the footer which is inserted by email.blade.php isn't accidentally
                // escaped.
                $html = $notification->toMail($user)->render();
                self::assertGreaterThan(0, strpos($html, 'contact <a href'));

                return true;
            }
        );

        $response->assertSessionHas('success');
        $response = $this->get('/party/view/'.$event->idevents);
        $response->assertSee('Invites Sent!');

        // Check it's in the DB.
        $this->assertDatabaseHas('events_users', [
            'user' => $user->id,
            'event' => $event->idevents,
            'role' => 4,
        ]);

        // Admin approves the event.
        $admin = factory(User::class)->state('Administrator')->create();
        $this->get('/logout');
        $this->actingAs($admin);
        $eventData = $event->getAttributes();
        $eventData['wordpress_post_id'] = 100;
        $eventData['id'] = $event->idevents;
        $eventData['moderate'] = 'approve';
        $this->post('/party/edit/'.$event->idevents, $eventData);

        // As the user...
        $this->get('/logout');
        $this->actingAs($user);

        // ...join the group.
        $response = $this->get('/group/join/'.$group->idgroups);
        $this->assertTrue($response->isRedirection());

        // We should see that we have been invited.
        $response2 = $this->get('/party/view/'.$event->idevents);
        $response2->assertSee(__('events.pending_rsvp_message'));
        preg_match('/href="(\/party\/accept-invite.*?)"/', $response2->getContent(), $matches);
        $this->assertGreaterThan(0, count($matches));
        $invitation = $matches[1];

        // ...should show up in the list of events with an invitation as we have not yet accepted.
        $response3 = $this->get('/party');
        $events = $this->getVueProperties($response3)[1][':initial-events'];
        $this->assertNotFalse(strpos($events, '"attending":false'));
        $this->assertNotFalse(strpos($events, '"invitation"'));

        // Now accept the invitation.
        $response4 = $this->get($invitation);
        $this->assertTrue($response4->isRedirection());
        $redirectTo = $response4->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

        // Now should show.
        $response5 = $this->get('/party');
        $events = $this->getVueProperties($response5)[1][':initial-events'];
        $this->assertNotFalse(strpos($events, '"attending":true'));
    }

    public function testInvitableUserPOV()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create([
                                                   'wordpress_post_id' => '99999'
                                               ]);
        $host = factory(User::class)->states('Host')->create();
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                                   'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                                   'user_id' => $host->id
                                               ]);
        EventsUsers::create([
                                'event' => $event->getKey(),
                                'user' => $host->getKey(),
                                'status' => 1,
                                'role' => 3,
                                'full_name' => null,
                           ]);
        $this->actingAs($host);

        // Should have no group members and therefore no invitable members.
        $response = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response->getContent());
        $this->assertEquals([], $members);

        // User joins the group.
        $user = factory(User::class)->states('Restarter')->create();
        $this->get('/logout');
        $this->actingAs($user);
        $response2 = $this->get('/group/join/'.$group->idgroups);
        $this->assertTrue($response2->isRedirection());

        // Shouldn't show up as invitable when we are logged in.
        $response3 = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response3->getContent());
        $this->assertEquals([], $members);

        // Now should show as invitable to the event.
        $this->get('/logout');
        $this->actingAs($host);
        $response4 = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response4->getContent());
        $this->assertEquals(1, count($members));

        // Invite the user to the event.
        $response5 = $this->post('/party/invite', [
            'group_name' => $group->name,
            'event_id' => $event->idevents,
            'manual_invite_box' => $user->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response5->assertSessionHas('success');

        // Invited member should not show up as invitable.
        $response6 = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response6->getContent());
        $this->assertEquals([], $members);

        // As the user...
        $this->get('/logout');
        $this->actingAs($user);

        // Now accept the invitation.
        $response7 = $this->get('/party/view/'.$event->idevents);
        $response7->assertSee('You&#039;ve been invited to join an event');
        preg_match('/href="(\/party\/accept-invite.*?)"/', $response7->getContent(), $matches);
        if (count($matches) <= 0) {
            error_log("Invite failed " . $response7->getContent());
        }
        $this->assertGreaterThan(0, count($matches));
        $invitation = $matches[1];

        $response8 = $this->get($invitation);
        $this->assertTrue($response8->isRedirection());
        $redirectTo = $response8->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

        // Now a group member and confirmed so should not show as invitable.
        $this->get('/logout');
        $this->actingAs($host);
        $response9 = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response9->getContent());
        $this->assertEquals([], $members);
    }

    public function testInvitableNotifications()
    {
        Notification::fake();
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $host = factory(User::class)->states('Host')->create();
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                                   'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                                   'user_id' => $host->id
                                               ]);
        EventsUsers::create([
                                'event' => $event->getKey(),
                                'user' => $host->getKey(),
                                'status' => 1,
                                'role' => 3,
                                'full_name' => null,
                            ]);
        $this->actingAs($host);

        // Should have no group members and therefore no invitable members.
        $response = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response->getContent());
        $this->assertEquals([], $members);

        // User joins the group.
        $user = factory(User::class)->states('Restarter')->create();
        $this->get('/logout');
        $this->actingAs($user);
        $response2 = $this->get('/group/join/'.$group->idgroups);
        $this->assertTrue($response2->isRedirection());

        // Shouldn't show up as invitable when we are logged in.
        $response3 = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response3->getContent());
        $this->assertEquals([], $members);

        // Now should show as invitable to the event.
        $this->get('/logout');
        $this->actingAs($host);
        $response4 = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response4->getContent());
        $this->assertEquals(1, count($members));

        // Invite the user to the event.
        $response5 = $this->post('/party/invite', [
            'group_name' => $group->name,
            'event_id' => $event->idevents,
            'manual_invite_box' => $user->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response5->assertSessionHas('success');

        // Invited member should not show up as invitable.
        $response6 = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response6->getContent());
        $this->assertEquals([], $members);

        // As the user...
        $this->get('/logout');
        $this->actingAs($user);

        // Now accept the invitation.
        $eu = EventsUsers::where('user', '=', $user->id)->first();
        $invitation = '/party/accept-invite/' . $event->idevents . '/' . $eu->status;

        $response8 = $this->get($invitation);
        $this->assertTrue($response8->isRedirection());
        $redirectTo = $response8->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

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
                self::assertRegexp('/' . $event->venue . '/', $mailData['introLines'][0]);

                return true;
            }
        );

        // Now a group member and confirmed so should not show as invitable.
        $this->get('/logout');
        $this->actingAs($host);
        $response9 = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response9->getContent());
        $this->assertEquals([], $members);
    }
}
