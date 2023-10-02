<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Network;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CalendarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create a network, group, event, user.
        $network = Network::factory()->create();
        $this->network = $network;

        $host = User::factory()->create([
                                                 'latitude' => 50.64,
                                                 'longitude' => 5.58,
                                                 'location' => 'London',
                                                 'calendar_hash' => \Str::random(15)
                                             ]);
        $this->actingAs($host);
        $this->host = $host;

        $group = Group::factory()->create([
                                                   'latitude' => 50.63,
                                                   'longitude' => 5.57,
                                                   'approved' => true,
                                                   'area' => 'London'
                                               ]);
        $network->addGroup($group);
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $group->approved = true;
        $group->save();
        $this->group = $group;

        $group2 = Group::factory()->create([
                                                   'latitude' => 1,
                                                   'longitude' => 2,
                                                   'approved' => true,
                                               ]);
        $this->group2 = $group2;

        $this->start = '2100-01-01T10:15:05+05:00';
        $this->end = '2100-01-0113:45:05+05:00';

        $event = Party::factory()->create([
                                                   'group' => $group->idgroups,
                                                   'latitude' => 50.645,
                                                   'longitude' => 5.575,
                                                   'event_start_utc' => $this->start,
                                                   'event_end_utc' => $this->end,
                                               ]);

        $event->approve();
        $this->event = $event;

        EventsUsers::create([
                                'event' => $event->idevents,
                                'user' => $host->id,
                                'status' => 1,
                                'role' => 4,
                                'full_name' => $host->name,
                            ]);


        $event2 = Party::factory()->create([
            'group' => $group->idgroups,
            'latitude' => 50.645,
            'longitude' => 5.575,
            'event_start_utc' => $this->start,
            'event_end_utc' => $this->end,
        ]);

        $event2->approve();

        EventsUsers::create([
            'event' => $event2->idevents,
            'user' => $host->id,
            'status' => 1,
            'role' => 4,
            'full_name' => $host->name,
        ]);
    }

    public function testByUser() {
        // Valid hash.
        $response = $this->get('/calendar/user/' . $this->host->calendar_hash);
        $response->assertStatus(200);
        $this->expectOutputRegex('/VEVENT/');
        $this->expectOutputString($this->start);
        $this->expectOutputString($this->end);
        $this->expectOutputString('CONFIRMED');

        // Invalid hash.
        $this->expectException(\Exception::class);
        $response = $this->get('/calendar/user/' . $this->host->calendar_hash . '1');
    }

    public function testByGroup() {
        // One event.
        $response = $this->get('/calendar/group/' . $this->group->idgroups);
        $response->assertStatus(200);
        $this->expectOutputRegex('/VEVENT/');
        $this->expectOutputString($this->start);
        $this->expectOutputString($this->end);

        // No events.
        $this->expectException(NotFoundHttpException::class);
        $response = $this->get('/calendar/group/' . $this->group2->idgroups);
    }

    public function testByNetwork() {
        // One event.
        $response = $this->get('/calendar/network/' . $this->network->id);
        $response->assertStatus(200);
        $this->expectOutputRegex('/VEVENT/');
        $this->expectOutputString($this->start);
        $this->expectOutputString($this->end);

        // No events.
        $network = Network::factory()->create();
        $this->expectException(NotFoundHttpException::class);
        $response = $this->get('/calendar/network/' . $network->id);
    }

    public function testByArea() {
        // One event.
        $response = $this->get('/calendar/group-area/London');
        $response->assertStatus(200);
        $this->expectOutputRegex('/VEVENT/');
        $this->expectOutputString($this->start);
        $this->expectOutputString($this->end);

        // No events.
        $this->expectException(NotFoundHttpException::class);
        $response = $this->get('/calendar/group-area/Edinburgh');
    }

    public function testAll() {
        // One event.
        $response = $this->get('/calendar/all-events/' . env('CALENDAR_HASH'));
        $response->assertStatus(200);
        $this->expectOutputRegex('/VEVENT/');
        $this->expectOutputString($this->start);
        $this->expectOutputString($this->end);

        $this->expectException(NotFoundHttpException::class);
        $response = $this->get('/calendar/all-events/' . env('CALENDAR_HASH') . '1');
    }

    public function testCancelled() {
        $this->event->cancelled = 1;
        $this->event->save();
        $response = $this->get('/calendar/user/' . $this->host->calendar_hash);
        $response->assertStatus(200);
        $this->expectOutputRegex('/CANCELLED/');
    }

    public function testEventNotApproved() {
        $this->event->approved = false;
        $this->event->save();
        $response = $this->get('/calendar/user/' . $this->host->calendar_hash);
        $response->assertStatus(200);
        $this->expectOutputRegex('/TENTATIVE/');
    }

    public function testGroupNotApproved() {
        $this->group->approved = false;
        $this->group->save();
        $response = $this->get('/calendar/user/' . $this->host->calendar_hash);
        $response->assertStatus(200);
        $this->expectOutputRegex('/TENTATIVE/');
    }

    public function testEventNotVisible() {
        $host = User::factory()->create([
                                            'latitude' => 50.64,
                                                 'longitude' => 5.58,
                                                 'location' => 'London',
                                                 'calendar_hash' => \Str::random(15)
                                             ]);
        $this->actingAs($host);
        $response = $this->get('/calendar/user/' . $host->calendar_hash);
        $response->assertStatus(200);
        $this->assertStringNotContainsString('/VEVENT/', $response->getContent());
    }
}