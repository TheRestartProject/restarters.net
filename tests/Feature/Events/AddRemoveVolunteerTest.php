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
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AddRemoveVolunteerTest extends TestCase
{
    public function testAddRemove()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_date' => '2130-01-01',
                                                   'start' => '12:13',
                                               ]);

        $host = factory(User::class)->states('Administrator')->create();
        $this->actingAs($host);

        $restarter = factory(User::class)->states('Restarter')->create();

        // Add an existing user
        $response = $this->post('/party/add-volunteer', [
            'event' => $event->idevents,
            'volunteer_email_address' => $restarter->email,
            'full_name' => $restarter->name,
            'user' => $restarter->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

        // Remove them
        $volunteer = EventsUsers::where('user', $restarter->id)->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');

        // Add an invited user
        $restarter = factory(User::class)->states('Restarter')->create();
        $response = $this->post('/party/invite', [
            'group_name' => $group->name,
            'event_id' => $event->idevents,
            'manual_invite_box' => $restarter->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response->assertSessionHas('success');
        $response = $this->get('/party/view/'.$event->idevents);
        $response->assertSee('Invites Sent!');

        $response = $this->post('/party/add-volunteer', [
            'event' => $event->idevents,
            'volunteer_email_address' => $restarter->email,
            'full_name' => $restarter->name,
            'user' => $restarter->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

        $volunteer = EventsUsers::where('user', $restarter->id)->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');

        // Add by name only
        $response = $this->post('/party/add-volunteer', [
            'event' => $event->idevents,
            'full_name' => 'Jo Bloggins',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

        $volunteer = EventsUsers::where('full_name', 'Jo Bloggins')->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');

        // Add anonymous.
        $response = $this->post('/party/add-volunteer', [
            'event' => $event->idevents,
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/party/view/'.$event->idevents));

        $volunteer = EventsUsers::where('event', $event->idevents)->whereNull('user')->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');
    }
}
