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

class OnlineEventsTests extends TestCase
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
    public function a_host_can_create_an_online_event()
    {
        $this->withoutExceptionHandling();

        // arrange
        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $group = factory(Group::class)->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        // act
        $eventAttributes = factory(Party::class)->raw(['online' => true]);
        $response = $this->post('/party/create/', $eventAttributes);

        // assert
        $event = Party::find(1);
        $this->assertTrue($event->online == true);
    }
}
