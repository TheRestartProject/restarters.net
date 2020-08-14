<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Network;
use App\Party;
use App\User;
use App\UserGroups;
use App\Helpers\Geocoder;
use App\Notifications\NotifyRestartersOfNewEvent;

use DB;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        Group::truncate();
        Party::truncate();
        EventsUsers::truncate();
        UserGroups::truncate();
        DB::delete('delete from group_network');
        DB::delete('delete from user_network');
        DB::statement("SET foreign_key_checks=1");

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
        $this->get('/party/create')->assertRedirect('/user/forbidden');
    }


    /** @test */
    public function a_host_with_a_group_can_create_an_event()
    {
        $this->withoutExceptionHandling();

        // arrange
        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $group = factory(Group::class)->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // act
        $response = $this->get('/party/create');
        $this->get('/party/create')->assertStatus(200);

        $eventAttributes = factory(Party::class)->raw();
        $response = $this->post('/party/create/', $eventAttributes);

        // assert
        $this->get('/party/view/1')->assertSee($eventAttributes['venue']);
        $this->assertDatabaseHas('events', $eventAttributes);
    }


    /** @test */
    public function emails_sent_when_created()
    {
        $this->withoutExceptionHandling();
        Notification::fake();

        $admins = factory(User::class, 5)->states('Administrator')->create();

        // When we create an event
        $event = factory(Party::class)->raw();
        $response = $this->post('/party/create/', $event);
        $response->assertStatus(302);

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
    public function emails_not_sent_when_past_event_approved()
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
        Notification::assertNothingSent();
    }

    /** @test */
    public function emails_sent_to_coordinators_when_event_created()
    {
        $this->withoutExceptionHandling();

        $admin = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin);
        // arrange
        Notification::fake();

        $network = factory(Network::class)->create();
        $group = factory(Group::class)->create();
        $coordinator = factory(User::class)->state('NetworkCoordinator')->create();
        $network->addGroup($group);
        $network->addCoordinator($coordinator);

        $eventData = factory(Party::class)->raw(['group' => $group->idgroups]);

        // act
        $response = $this->post('/party/create/', $eventData);

        // assert
        Notification::assertSentTo(
            [$coordinator], AdminModerationEvent::class
        );
    }

    /** @test */
    public function a_host_can_be_added_later()
    {
        // Disable discourse integration as this doesn't currently work in a test environment.  We are considering
        // a better solution.
        config(['restarters.features.discourse_integration' => false]);

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
        $party = $group->parties()->first();

        // Remove the host from the event
        $this->post('/party/remove-volunteer/', [
            'user_id' => $host->id,
            'event_id' => $party->idevents
        ])->assertStatus(200);

        // Assert that we see the host in the list of volunteers to add to the event.
        $this->get('/party/view/1')->assertSeeInOrder(['Group member', '<option value="' . $host->id . '">', '</div>']);

        // Assert we can add them back in.

        config(['restarters.features.discourse_integration' => true]);
    }
}
