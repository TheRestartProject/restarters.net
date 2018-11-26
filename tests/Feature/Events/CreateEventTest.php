<?php

namespace Tests\Feature;

use App\User;
use App\Group;
use App\Party;

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
        Group::truncate();
        Party::truncate();
        DB::statement("SET foreign_key_checks=1");


        // Given we're logged in as an admin
        $admin = factory(User::class)->states('Administrator')->create();
        $this->actingAs($admin);
    }

    /** @test */
    public function a_host_can_create_an_event()
    {
        // When we create an event
        $event = factory(Party::class)->raw();
        $response = $this->post('/party/create/', $event);

        // Then it should be...
        $this->assertDatabaseHas('events', $event);
    }

    /** @test */
    public function emails_sent_when_created()
    {
        //Notification::fake();
        $admins = factory(User::class, 5)->states('Administrator')->create();

        // When we create an event
        $event = factory(Party::class)->raw();
        $response = $this->post('/party/create/', $event);
        $response->assertSuccessful();

        //Notification::assertSentTo(
        //   [$admins], AdminModerationEvent::class
        //        );
    }
}
