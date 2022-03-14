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
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class AddRemoveVolunteerTest extends TestCase
{
    public function testAddRemove()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                                   'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                               ]);

        $host = factory(User::class)->states('Administrator')->create();
        $this->actingAs($host);

        $restarter = factory(User::class)->states('Restarter')->create();

        // Add an existing user
        $response = $this->put('/api/events/' . $event->idevents . '/volunteers', [
            'api_token' => $host->api_token,
            'volunteer_email_address' => $restarter->email,
            'full_name' => $restarter->name,
            'user' => $restarter->id,
        ]);

        $response->assertJson([
            'success' => 'success'
        ]);

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

        $response = $this->put('/api/events/' . $event->idevents . '/volunteers', [
            'volunteer_email_address' => $restarter->email,
            'full_name' => $restarter->name,
            'user' => $restarter->id,
        ]);

        $response->assertJson([
            'success' => 'success'
        ]);

        $volunteer = EventsUsers::where('user', $restarter->id)->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');

        // Add by name only
        $response = $this->put('/api/events/' . $event->idevents . '/volunteers', [
            'full_name' => 'Jo Bloggins',
        ]);

        $response->assertSuccessful();
        $rsp = json_decode($response->getContent(), TRUE);
        $this->assertEquals('success', $rsp['success']);

        $volunteer = EventsUsers::where('full_name', 'Jo Bloggins')->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');

        // Add anonymous.
        $response = $this->put('/api/events/' . $event->idevents . '/volunteers', []);

        $response->assertJson([
            'success' => 'success'
        ]);

        $volunteer = EventsUsers::where('event', $event->idevents)->whereNull('user')->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');
    }

    public function testAdminRemoveReaddHost() {
        $this->withoutExceptionHandling();

        $host = factory(User::class)->states('Administrator')->create([
          'api_token' => '1234',
        ]);

        $this->actingAs($host);

        // Create group.
        $idgroups = $this->createGroup();

        // Host remove themselves.
        $this->followingRedirects();
        $response = $this->post('/api/usersgroups/' . $idgroups, [
            'api_token' => '1234',
            '_method' => 'delete',
        ]);

        $ret = json_decode($response->getContent(), true);
        $this->assertTrue($ret['success']);

        // Admin re-add from user account page.
        $admin = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin);

        $response = $this->get('/user/edit/' . $host->id);
        $response->assertStatus(200);

        $crawler = new Crawler($response->getContent());

        $tokens = $crawler->filter('input[name=_token]')->each(function (Crawler $node, $i) {
            return $node;
        });

        $tokenValue = $tokens[0]->attr('value');

        $response = $this->post('/profile/edit-admin-settings', [
            '_token' => $tokenValue,
            'id' => $host->id,
            'user_role' => 2,
            'assigned_groups' => [
                $idgroups
            ],
            'preferences' => [
                3, 12
            ]
        ]);
        $response->assertSessionHas('message');
        $this->assertTrue($response->isRedirection());

        // Should now see the group.
        $response = $this->get('/user/edit/' . $host->id);
        $response->assertStatus(200);
        $response->assertSee('<option value="' . $idgroups . '" selected>Test Group0</option>');
    }
}
