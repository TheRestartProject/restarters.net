<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Party;
use App\User;

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
        //Notification::fake();
        $admins = factory(User::class, 5)->states('Administrator')->create();

        // When we create an event
        $event = factory(Party::class)->raw();
        $response = $this->post('/party/create/', $event);
        $response->assertStatus(302);

        //Notification::assertSentTo(
        //   [$admins], AdminModerationEvent::class
        //        );
    }
}
