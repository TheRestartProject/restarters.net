<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Network;
use App\Notifications\AdminModerationEvent;
use App\Party;
use App\User;
use App\Helpers\Geocoder;
use App\Notifications\NotifyRestartersOfNewEvent;

use DB;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class InviteEventTest extends TestCase
{
    public function testInvite() {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_date' => '2130-01-01',
                                                   'start' => '12:13'
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

        $response->assertSessionHas('success');
        $response = $this->get('/party/view/' . $event->idevents);
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
        $this->post('/party/edit/' . $event->idevents, $eventData);

        // As the user...
        $this->get('/logout');
        $this->actingAs($user);

        // ...join the group.
        $response = $this->get('/group/join/' . $group->idgroups);
        $this->assertTrue($response->isRedirection());

        // We should see that we have been invited.
        $response = $this->get('/party/view/' . $event->idevents);
        $response->assertSee('You&#039;ve been invited to join an event');
        preg_match('/href="(\/party\/accept-invite.*?)"/', $response->getContent(), $matches);
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
        $this->assertNotFalse(strpos($redirectTo, '/party/view/' . $event->idevents));

        // Now should show.
        $response = $this->get('/party');
        $events = $this->getVueProperties($response)[0][':initial-events'];
        $this->assertNotFalse(strpos($events, '"attending":true'));
    }
}
