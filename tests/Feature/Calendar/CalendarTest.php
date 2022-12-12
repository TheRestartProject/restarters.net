<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
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

        // Create a group, event, user.
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
                                                   'wordpress_post_id' => '99999',
                                                   'area' => 'London'
                                               ]);
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->group = $group;

        $group2 = Group::factory()->create([
                                                   'latitude' => 1,
                                                   'longitude' => 2,
                                                   'wordpress_post_id' => '99999',
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

    }

    public function testByUser() {
        // Valid hash.
        $response = $this->get('/calendar/user/' . $this->host->calendar_hash);
        $response->assertStatus(200);
        $this->expectOutputRegex('/VEVENT/');
        $this->expectOutputString($this->start);
        $this->expectOutputString($this->end);

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
}