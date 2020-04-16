<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Party;
use App\User;
use App\UserGroups;
use App\Notifications\NotifyRestartersOfNewEvent;

use DB;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        DB::statement("SET foreign_key_checks=1");
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
}
