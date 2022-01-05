<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Geocoder;
use App\Network;
use App\Notifications\AdminModerationEvent;
use App\Notifications\NotifyRestartersOfNewEvent;
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

        $group = factory(Group::class)->create();
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_date' => '2130-01-01',
                                                   'start' => '12:13',
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
        $response = $this->get('/party/view/'.$event->idevents);
        $response->assertSee(__('events.pending_rsvp_message'));
        preg_match('/href="(\/party\/accept-invite.*?)"/', $response->getContent(), $matches);
        $this->assertGreaterThan(0, count($matches));
        $invitation = $matches[1];

        // ...should show up in the list of events with an invitation as we have not yet accepted.
        $response = $this->get('/party');
        $events = $this->getVueProperties($response)[0][':initial-events'];
        $this->assertNotFalse(strpos($events, '"attending":false'));
        $this->assertNotFalse(strpos($events, '"invitation"'));

        // Now accept the invitation.
        $response = $this->get($invitation);
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

        // Now should show.
        $response = $this->get('/party');
        $events = $this->getVueProperties($response)[0][':initial-events'];
        $this->assertNotFalse(strpos($events, '"attending":true'));
    }

    public function testInvitable()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_date' => '2130-01-01',
                                                   'start' => '12:13',
                                               ]);

        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        // Should have no group members and therefore no invitable members.
        $response = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response->getContent());
        $this->assertEquals([], $members);

        // User joins the group.
        $user = factory(User::class)->states('Restarter')->create();
        $this->get('/logout');
        $this->actingAs($user);
        $response = $this->get('/group/join/'.$group->idgroups);
        $this->assertTrue($response->isRedirection());

        // Shouldn't show up as invitable when we are logged in.
        $response = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response->getContent());
        $this->assertEquals([], $members);

        // Now should show as invitable to the event.
        $this->get('/logout');
        $this->actingAs($host);
        $response = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response->getContent());
        $this->assertEquals(1, count($members));

        // Invite the user to the event.
        $response = $this->post('/party/invite', [
            'group_name' => $group->name,
            'event_id' => $event->idevents,
            'manual_invite_box' => $user->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response->assertSessionHas('success');

        // Invited member should not show up as invitable.
        $response = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response->getContent());
        $this->assertEquals([], $members);

        // As the user...
        $this->get('/logout');
        $this->actingAs($user);

        // Now accept the invitation.
        $response = $this->get('/party/view/'.$event->idevents);
        $response->assertSee('You&#039;ve been invited to join an event');
        preg_match('/href="(\/party\/accept-invite.*?)"/', $response->getContent(), $matches);
        if (!count($matches)) {
            error_log($response->getContent());
        }
        $this->assertGreaterThan(0, count($matches));
        $invitation = $matches[1];

        $response = $this->get($invitation);
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

        // Now a group member and confirmed so should not show as invitable.
        $this->get('/logout');
        $this->actingAs($host);
        $response = $this->get('/party/get-group-emails-with-names/'.$event->idevents);
        $members = json_decode($response->getContent());
        $this->assertEquals([], $members);
    }
}
