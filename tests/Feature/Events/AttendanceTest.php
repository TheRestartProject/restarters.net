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
use App\User;
use DB;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::factory()->administrator()->create();
        $this->actingAs($this->host);

        $this->group = Group::factory()->create();
        $this->group->addVolunteer($this->host);
        $this->group->makeMemberAHost($this->host);

        // Create the event
        $this->idevents = $this->createEvent($this->group->idgroups, '2000-01-01');
        self::assertNotNull($this->idevents);

        $this->party = $this->group->parties()->latest()->first();
    }

    public function testParticipants(): void {
        // Initial count will be 0.
        self::assertEquals(0, $this->party->pax);

        // The old POST /party/update-quantity route is gone post-cutover; the headcount
        // counters were folded into PATCH /api/v2/events/{id} (see the "participants"
        // handling in EventController::updateEventv2).
        $atts = $this->eventAttributesToAPI(Party::find($this->idevents)->getAttributes());
        $atts['participants'] = 3;

        $rsp = $this->patch('/api/v2/events/' . $this->idevents, $atts);

        $rsp->assertSuccessful();
        $this->party->refresh();
        self::assertEquals(3, $this->party->pax);
    }


    public function testVolunteers(): void {
        // Initial count will be 1, for the host.
        self::assertEquals(1, $this->party->volunteers);

        // The old POST /party/update-volunteerquantity route is gone post-cutover; the
        // headcount counters were folded into PATCH /api/v2/events/{id} (see the
        // "volunteers" handling in EventController::updateEventv2).
        $atts = $this->eventAttributesToAPI(Party::find($this->idevents)->getAttributes());
        $atts['volunteers'] = 4;

        $rsp = $this->patch('/api/v2/events/' . $this->idevents, $atts);

        $rsp->assertSuccessful();
        $this->party->refresh();
        self::assertEquals(4, $this->party->volunteers);
    }
}
