<?php

namespace Tests\Feature;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\Helpers\Geocoder;
use App\Network;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\User;
use App\UserGroups;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OnlineEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(Geocoder::class, function () {
            return new GeocoderMock();
        });
    }

    /** @test */
    public function a_host_can_create_an_online_event()
    {
        $this->withoutExceptionHandling();

        // arrange
        $host = User::factory()->host()->create();
        $this->actingAs($host);

        $group = Group::factory()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // act
        $eventAttributes = Party::factory()->raw(['online' => true]);
        $response = $this->post('/party/create/', $eventAttributes);
        $idevents = Party::latest()->first()->idevents;

        // assert
        $event = Party::find($idevents);
        $this->assertTrue($event->online == true);
    }
}
