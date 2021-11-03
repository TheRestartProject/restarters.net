<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Geocoder;
use App\Helpers\RepairNetworkService;
use App\Network;
use App\Notifications\AdminModerationEvent;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\Role;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GeocoderMock extends Geocoder
{
    public function __construct()
    {
    }

    public function geocode($location)
    {
        return [
            'latitude' => '1',
            'longitude' => '1',
        ];
    }
}

class CreateEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(Geocoder::class, function () {
            return new GeocoderMock();
        });
    }

    /** @test */
    public function a_host_without_a_group_cant_create_an_event()
    {
        $this->withoutExceptionHandling();

        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $response = $this->get('/party/create');
        $response->assertSee('You need to be a host of a group in order to create a new event listing');
    }

    /**
     * @test
     * @dataProvider roles
     */
    public function a_host_with_a_group_can_create_an_event($data)
    {
        $this->withoutExceptionHandling();

        // arrange
        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $group = factory(Group::class)->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // Fetch the event create page.
        $response = $this->get('/party/create');
        $this->get('/party/create')->assertStatus(200);

        // Create a party for the specific group.
        $eventAttributes = factory(Party::class)->raw();
        $eventAttributes['group'] = $group->idgroups;
        $eventAttributes['link'] = 'https://therestartproject.org/';

        // We want an upcoming event so that we can check it appears in various places.
        $eventAttributes['event_date'] = date('Y-m-d', strtotime('tomorrow'));

        $this->post('/party/create/', $eventAttributes);
        $this->assertDatabaseHas('events', $eventAttributes);

        // Check that we can view the event, and that it shows the creation success message.
        $event = Party::latest()->first();
        $this->get('/party/view/'.$event->idevents)->
            assertSee($eventAttributes['venue'])->
            assertSee(__('events.created_success_message'));

        // Now check whether the event shows/doesn't show correctly for different user roles.
        list($role, $seeEvent, $canModerate) = $data;

        if ($role != 'Host') {
            // Need to act as someone else.
            $this->actingAs(factory(User::class)->states($role)->create());
        }

        // Check the group page.
        $response = $this->get('/group/view/'.$group->idgroups);

        $props = $this->assertVueProperties($response, [
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        $events = json_decode($props[0][':events'], TRUE);

        if ($seeEvent) {
            // We should be able to see this upcoming event in the Vue properties.
            $this->assertEquals(true, $events[0]['requiresModeration']);
            $this->assertEquals($canModerate, $events[0]['canModerate']);
            $this->assertEquals(true, $events[0]['attending']);
            $this->assertEquals(true, $events[0]['isVolunteer']);
        } else {
            $this->assertEquals(true, $events[0]['requiresModeration']);
        }

        // Check the top-level events page.
        $response = $this->get('/party');

        $props = $this->assertVueProperties($response, [
            [
                'heading-level' => 'h2',
            ],
        ]);

        $events = json_decode($props[0][':initial-events'], TRUE);

        if ($seeEvent) {
            // We should be able to see this upcoming event in the Vue properties.
            $this->assertEquals(true, $events[0]['requiresModeration']);
            $this->assertEquals($canModerate, $events[0]['canModerate']);
            $this->assertEquals(true, $events[0]['attending']);
            $this->assertEquals(true, $events[0]['isVolunteer']);
        } else {
            $this->assertEquals(true, $events[0]['requiresModeration']);
        }

        // Approve the event.
        $event->wordpress_post_id = 100;
        $event->save();

        // Check that the event shows for a restarter.
        $this->loginAsTestUser(Role::RESTARTER);

        $response = $this->get('/party');

        $props = $this->assertVueProperties($response, [
            [
                'heading-level' => 'h2',
            ],
        ]);

        $events = json_decode($props[0][':initial-events'], TRUE);
        $this->assertEquals(1, count($events));

        // Should have the 'all' property set because we've not joined the group.
        $this->assertEquals(true, $events[0]['all']);
        $this->assertEquals(false, array_key_exists('nearby', $events[0]));

        // Now join the group.
        $response = $this->get('/group/join/' . $group->idgroups);
        $this->assertTrue($response->isRedirection());

        $response = $this->get('/party');

        $props = $this->assertVueProperties($response, [
            [
                'heading-level' => 'h2',
            ],
        ]);

        $events = json_decode($props[0][':initial-events'], TRUE);
        $this->assertEquals(1, count($events));

        // Should not have 'all' or 'nearby' flag - those go in the "Other events" section.
        $this->assertEquals(false, array_key_exists('all', $events[0]));
        $this->assertEquals(false, array_key_exists('nearby', $events[0]));
    }

    public function roles()
    {
        return [
            // Hosts can see but not moderate.
            [['Host', true, false]],

            // Nobody else can see the event in the list of events.
            //
            // Administrators and NetworkCoordinators arguably should be able to, but that's the current function.
            // They will see it in the lists of events to moderate, but that's not what we are testing here.
            [['Restarter', false, false]],
            [['Administrator', false, false]],
            [['NetworkCoordinator', false, false]],
        ];
    }

    /** @test */
    public function a_host_can_duplicate_an_event()
    {
        $this->withoutExceptionHandling();

        // arrange
        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $group = factory(Group::class)->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // act
        $party = factory(Party::class)->create([
           'group' => $group->idgroups,
           'latitude'=>'1',
           'longitude'=>'1',
       ]);

        // Duplicate it - should bring up the page to add a new event, with some info from the first one.
        $response = $this->get('/party/duplicate/'.$party->idevents);
        $response->assertSee('duplicate-from');
        $response->assertSee($party->description);
    }

    /** @test */
    public function emails_sent_when_created()
    {
        $this->withoutExceptionHandling();
        Notification::fake();

        // Create some admins.
        $admins = factory(User::class, 5)->states('Administrator')->create();

        // Create a network with a group.
        $network = factory(Network::class)->create();
        $group = factory(Group::class)->create();
        $network->addGroup($group);

        // Make these admins coordinators of the network, so that they should get notified.
        foreach ($admins as $admin) {
            $network->addCoordinator($admin);
        }

        // Log in so that we can create an event.
        $this->actingAs($admins[0]);

        // Create an event.
        $event = factory(Party::class)->raw();
        $event['group'] = $group->idgroups;
        $response = $this->post('/party/create/', $event);
        $response->assertStatus(302);

        // Should have been sent to the admins.
        Notification::assertSentTo(
           [$admins], AdminModerationEvent::class
        );
    }

    /** @test */
    public function emails_sent_to_restarters_when_upcoming_event_approved()
    {
        $this->withoutExceptionHandling();
        $admin = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin);
        // arrange
        Notification::fake();

        $group = factory(Group::class)->create();
        $host = factory(User::class)->state('Host')->create();
        $restarter = factory(User::class)->state('Restarter')->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $group->addVolunteer($restarter);

        $eventData = factory(Party::class)->raw(['group' => $group->idgroups, 'event_date' => '2030-01-01', 'latitude'=>'1', 'longitude'=>'1']);

        // act
        $response = $this->post('/party/create/', $eventData);
        $event = Party::where('event_date', '2030-01-01')->first();
        $eventData['wordpress_post_id'] = 100;
        $eventData['id'] = $event->idevents;
        $eventData['moderate'] = 'approve';
        $response = $this->post('/party/edit/'.$event->idevents, $eventData);

        // assert
        Notification::assertSentTo(
            [$restarter], NotifyRestartersOfNewEvent::class
        );
        Notification::assertNotSentTo(
            [$host], NotifyRestartersOfNewEvent::class
        );
    }

    /** @test */
    public function emails_not_sent_to_volunteers_when_past_event_approved()
    {
        $this->withoutExceptionHandling();

        $admin = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin);
        // arrange
        Notification::fake();

        $group = factory(Group::class)->create();
        $host = factory(User::class)->state('Host')->create();
        $restarter = factory(User::class)->state('Restarter')->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $group->addVolunteer($restarter);

        $eventData = factory(Party::class)->raw(['group' => $group->idgroups, 'event_date' => '1930-01-01', 'latitude'=>'1', 'longitude'=>'1']);

        // act
        $response = $this->post('/party/create/', $eventData);
        $event = Party::where('event_date', '1930-01-01')->first();
        $eventData['wordpress_post_id'] = 100;
        $eventData['id'] = $event->idevents;
        $eventData['moderate'] = 'approve';
        $response = $this->post('/party/edit/'.$event->idevents, $eventData);

        // assert
        Notification::assertNotSentTo(
            [$restarter], NotifyRestartersOfNewEvent::class
        );
    }

    /** @test */
    public function emails_sent_to_coordinators_when_event_created()
    {
        $this->withoutExceptionHandling();
        Notification::fake();

        $network = factory(Network::class)->create();
        $group = factory(Group::class)->create();
        $network->addGroup($group);

        // Make an admin who is also a network controller.
        $admin = factory(User::class)->state('Administrator')->create();
        $admin->addPreference('admin-moderate-event');
        $network->addCoordinator($admin);

        // Make a separate network controller.
        $coordinator = factory(User::class)->state('NetworkCoordinator')->create();
        $network->addCoordinator($coordinator);

        $eventData = factory(Party::class)->raw(['group' => $group->idgroups]);

        $this->actingAs($admin);
        $response = $this->post('/party/create/', $eventData);

        // assert that the notification was sent to both the network coordinator, and the admin, and only once to each.
        Notification::assertSentToTimes(
            $coordinator, AdminModerationEvent::class, 1
        );
        Notification::assertSentToTimes(
            $admin, AdminModerationEvent::class, 1
        );
    }

    /** @test */
    public function a_host_can_be_added_later()
    {
        $this->withoutExceptionHandling();

        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $group = factory(Group::class)->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // Create the event
        $response = $this->get('/party/create');
        $this->get('/party/create')->assertStatus(200);

        $eventAttributes = factory(Party::class)->raw(['group' => $group->idgroups, 'event_date' => '2000-01-01', 'wordpress_post_id' => '99999']);
        $response = $this->post('/party/create/', $eventAttributes);

        // Find the event id
        $party = $group->parties()->latest()->first();

        // Remove the host from the event
        $volunteer = EventsUsers::where('user', $host->id)->first();
        $this->post('/party/remove-volunteer/', [
            'id' => $volunteer->idevents_users,
        ])->assertSee('true');

        // Assert that we see the host in the list of volunteers to add to the event.
        $this->get('/party/view/'.$party->idevents)->assertSeeInOrder(['Group member', '<option value="'.$host->id.'">', '</div>']);

        // Assert we can add them back in.
        $response = $this->post('/party/add-volunteer', [
            'event' => $party->idevents,
            'volunteer_email_address' => $host->email,
            'full_name' => $host->name,
            'user' => $host->id
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($response->isRedirection());
    }

    public function provider() {
        return [
            // Check the event has been approved (using the magic value of the WordPress post id used when WordPress is
            // not being used.
            [ true, 99999 ],

            // Check the event is not auto-approved by mistake.
            [ false, null]
        ];
    }
    /**
     * @test
     **@dataProvider provider
     */
    public function an_event_can_be_auto_approved($autoApprove, $wordpress_post_id) {
        $network = factory(Network::class)->create([
            'auto_approve_events' => $autoApprove
        ]);

        $host = factory(User::class)->states('Administrator')->create();
        $this->actingAs($host);

        $group = factory(Group::class)->create();
        $this->networkService = new RepairNetworkService();
        $this->networkService->addGroupToNetwork($host, $group, $network);
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // Create the event
        $idevents = $this->createEvent($group->idgroups, '2000-01-01');

        $party = $group->parties()->latest()->first();
        $this->assertEquals($idevents, $party->idevents);
        $this->assertEquals($wordpress_post_id, $party->wordpress_post_id);
    }
}
