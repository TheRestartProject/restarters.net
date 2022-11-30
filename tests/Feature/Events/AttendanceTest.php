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
    public function setUp(): void
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

    public function testParticipants() {
        // Initial count will be 0.
        self::assertEquals(0, $this->party->pax);

        $rsp = $this->post('/party/update-quantity', [
            'event_id' => $this->idevents,
            'quantity' => 3
        ]);

        self::assertTrue($rsp['success']);
        $this->party->refresh();
        self::assertEquals(3, $this->party->pax);
    }


    public function testVolunteers() {
        // Initial count will be 1, for the host.
        self::assertEquals(1, $this->party->volunteers);

        $rsp = $this->post('/party/update-volunteerquantity', [
            'event_id' => $this->idevents,
            'quantity' => 4
        ]);

        self::assertTrue($rsp['success']);
        $this->party->refresh();
        self::assertEquals(4, $this->party->volunteers);
    }
}
