<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\Geocoder;
use App\Listeners\RemoveUserFromDiscourseThreadForEvent;
use App\Network;
use App\Notifications\AdminModerationEvent;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\Role;
use App\User;
use DB;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use function PHPUnit\Framework\assertEquals;

class AddRemoveVolunteerTest extends TestCase
{
    /**
     * @dataProvider roleProvider
     */

    public function testAddRemove($role, $addrole, $shouldBeHost)
    {
        $this->withoutExceptionHandling();
        Queue::fake();

        $group = Group::factory()->create();
        $network = Network::factory()->create();
        $network->addGroup($group);
        $event = Party::factory()->create([
                                                   'group' => $group,
                                                   'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                                   'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                               ]);

        switch ($role) {
            case 'Administrator':
                $host = User::factory()->administrator()->create();
                break;
            case 'NetworkCoordinator':
                $host = User::factory()->networkCoordinator()->create();
                break;
            default:
                self::assertTrue(false, "Unknown role $role");
        }

        if ($role == 'NetworkCoordinator') {
            $network->addCoordinator($host);
        }

        $this->actingAs($host);

        switch ($addrole) {
            case 'Administrator':
                $restarter = User::factory()->administrator()->create();
                break;
            case 'NetworkCoordinator':
                $restarter = User::factory()->networkCoordinator()->create();
                break;
            case 'HostThis':
                $restarter = User::factory()->host()->create();
                $group->addVolunteer($restarter);
                $group->makeMemberAHost($restarter);
                break;
            case 'HostOther':
                $restarter = User::factory()->host()->create();
                $group2 = Group::factory()->create();
                $group2->addVolunteer($restarter);
                $group2->makeMemberAHost($restarter);
                break;
            case 'Restarter':
                $restarter = User::factory()->restarter()->create();
        }


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

        // Check they show in the list of volunteers.
        $response = $this->get('/api/events/' . $event->idevents . '/volunteers?api_token='  . $host->api_token);
        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'volunteers' => [
                [
                    'user' => $restarter->id
                ]
            ]
        ]);

        // Check they are/are not a host.
        $hostFor = Party::hostFor([$restarter->id])->get();

        if ($shouldBeHost) {
            $this->assertTrue($hostFor->contains($event));
        } else {
            $this->assertFalse($hostFor->contains($event));
        }

        // Remove them
        $volunteer = EventsUsers::where('user', $restarter->id)->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');

        // Check they no longer show in the list of volunteers.
        $response = $this->get('/api/events/' . $event->idevents . '/volunteers?api_token='  . $host->api_token);
        $response->assertSuccessful();
        $response->assertJsonMissing([
                                  'success' => true,
                                  'volunteers' => [
                                      [
                                          'user' => $restarter->id
                                      ]
                                  ]
                              ]);

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) use ($event, $restarter) {
            if ($job->class == RemoveUserFromDiscourseThreadForEvent::class) {
                return true;
            }
        });

        // Add an invited user
        $restarter = User::factory()->restarter()->create();
        $response = $this->post('/party/invite', [
            'group_name' => $group->name,
            'event_id' => $event->idevents,
            'manual_invite_box' => $restarter->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response->assertSessionHas('success');
        $response = $this->get('/party/view/'.$event->idevents);
        $response->assertSee('Invites sent!');

        // Invited volunteers shouldn't affect the count.
        $event->refresh();
        assertEquals(0, $event->volunteers);

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

        // Invited volunteers shouldn't affect the count.
        $event->refresh();
        assertEquals(0, $event->volunteers);

        // Add by name only
        $response = $this->put('/api/events/' . $event->idevents . '/volunteers', [
            'full_name' => 'Jo Bloggins',
            'volunteer_email_address' => NULL,
        ]);

        $response->assertSuccessful();
        $rsp = json_decode($response->getContent(), TRUE);
        $this->assertEquals('success', $rsp['success']);

        $volunteer = EventsUsers::where('full_name', 'Jo Bloggins')->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');

        // Invited volunteers shouldn't affect the count.
        $event->refresh();
        assertEquals(0, $event->volunteers);

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

    public function roleProvider() {
        return [
            [ 'Administrator', 'Restarter', false ],
            [ 'NetworkCoordinator', 'HostThis', true ],
            [ 'NetworkCoordinator', 'HostOther', false ],
            [ 'NetworkCoordinator', 'Administrator', false ],
        ];
    }

    public function testAdminRemoveReaddHost() {
        $this->withoutExceptionHandling();

        $host = User::factory()->administrator()->create([
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
        $admin = User::factory()->administrator()->create();
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
            'user_role' => Role::ADMINISTRATOR,
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
        $response->assertSee('<option value="' . $idgroups . '" selected>Test Group0</option>', false);
    }
}
