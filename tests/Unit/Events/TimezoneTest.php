<?php

namespace Tests\Unit;

use App\Group;
use App\Party;
use App\User;
use Carbon\Carbon;
use DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    /**
     * @test
     * @dataProvider timezoneProvider
     */
    public function timezone_inheritance($event, $group, $result, $exception) {
        $g = factory(Group::class)->create([
                                                   'timezone' => $group
                                               ]);

        $e = factory(Party::class)->create([
           'timezone' => $event,
           'group' => $g->idgroups
        ]);

        try {
            $timezone = $e->timezone;
            $this->assertEquals($result, $timezone);
        } catch(\Exception $e) {
            if ($exception) {
                $this->assertTrue(true);
            } else {
                $this->assertFalse(true, 'Unexpected exception thrown');
            }
        }
    }

    public function timezoneProvider() {
        return [
            [ NULL, 'Asia/Samarkand', 'Asia/Samarkand', FALSE ],
            [ 'Asia/Samarkand', NULL, 'Asia/Samarkand', FALSE ],
            [ NULL, NULL, NULL, TRUE],
        ];
    }

    public function testStartEnd() {
        $g = factory(Group::class)->create([
           'timezone' => 'Asia/Samarkand'
        ]);

        // Create an event in a different timezone, using local times.
        $e = factory(Party::class)->create([
            'group' => $g->idgroups,
            'event_start_utc' => '2021-02-01T10:15:05+05:00',
            'event_end_utc' => '2021-02-01T13:45:05+05:00',
            'timezone' => NULL
        ]);

        // Check that the ISO times are as we would expect for this zone.
        self::assertEquals('2021-02-01T10:15:05+00:00', $e->event_start_utc);
        self::assertEquals('2021-02-01T13:45:05+00:00', $e->event_end_utc);
        self::assertEquals('15:15', $e->start_local);
        self::assertEquals('18:45', $e->end_local);
        self::assertEquals('01/02/2021', $e->getFormattedLocalEnd());
    }

    /**
     * @dataProvider timesProvider
     */
    public function testOrder($date, $tz1, $start1, $end1, $tz2, $start2, $end2, $editstart2, $editend2) {
        // Two groups in different timezones.
        $g1 = factory(Group::class)->create([
                                               'timezone' => $tz1
                                           ]);

        $g2 = factory(Group::class)->create([
                                               'timezone' => $tz2
                                           ]);

        // Use Admins to avoid headers already sent problem.
        $host1 = factory(User::class)->states('Administrator')->create();
        $g1->addVolunteer($host1);
        $g1->makeMemberAHost($host1);

        $host2 = factory(User::class)->states('Administrator')->create();
        $g2->addVolunteer($host2);
        $g2->makeMemberAHost($host2);

        // Create an event for each.
        $this->actingAs($host1);
        $event = factory(Party::class)->raw();
        unset($event['timezone']);
        $event['event_start_utc'] = (new \DateTime("$date $start1", new \DateTimeZone($tz1)))->format(\DateTimeInterface::ISO8601);
        $event['event_end_utc'] = (new \DateTime("$date $end1", new \DateTimeZone($tz1)))->format(\DateTimeInterface::ISO8601);
        $event['group'] = $g1->idgroups;
        $response = $this->post('/party/create/', $event);
        $response->assertStatus(302);

        $this->actingAs($host2);
        $event = factory(Party::class)->raw();
        unset($event['timezone']);
        $event['group'] = $g2->idgroups;
        $event['event_start_utc'] = (new \DateTime("$date $start2", new \DateTimeZone($tz2)))->format(\DateTimeInterface::ISO8601);
        $event['event_end_utc'] = (new \DateTime("$date $end2", new \DateTimeZone($tz2)))->format(\DateTimeInterface::ISO8601);
        $response = $this->post('/party/create/', $event);
        $response->assertStatus(302);

        // Now get them and check the ordering works.
        $response = $this->get('/party');

        $props = $this->assertVueProperties($response, [
            [],
            [],
            [
                'heading-level' => 'h2',
            ],
        ]);

        $events = json_decode($props[2][':initial-events'], TRUE);

        // Check the returned events:
        // - The events should be second first because that is the earliest actual time and therefore the soonest
        //   starting event.
        // - The UTC fields should be returned, but having converted to UTC and therefore having +00:00.
        // - The timezone should be set.
        $this->assertEquals($tz2, $events[0]['timezone']);
        $this->assertEquals(Carbon::parse("$date $start2", $tz2)->setTimezone('UTC')->toIso8601String(), $events[0]['event_start_utc']);
        $this->assertEquals(Carbon::parse("$date $end2", $tz2)->setTimezone('UTC')->toIso8601String(), $events[0]['event_end_utc']);
        $this->assertStringContainsString('+00:00', $events[0]['event_start_utc']);
        $this->assertStringContainsString('+00:00', $events[0]['event_end_utc']);

        $this->assertEquals($tz1, $events[1]['timezone']);
        $this->assertEquals(Carbon::parse("$date $start1", $tz1)->setTimezone('UTC')->toIso8601String(), $events[1]['event_start_utc']);
        $this->assertEquals(Carbon::parse("$date $end1", $tz1)->setTimezone('UTC')->toIso8601String(), $events[1]['event_end_utc']);
        $this->assertStringContainsString('+00:00', $events[1]['event_start_utc']);
        $this->assertStringContainsString('+00:00', $events[1]['event_end_utc']);

        // Edit the event to check timezones work there too.
        $this->actingAs($host2);
        $id2 = $events[0]['idevents'];
        $event = Party::findOrFail($id2);
        $eventData = $event->getAttributes();
        $eventData['id'] = $id2;
        $eventData['event_start_utc'] = Carbon::parse("$date $editstart2", $tz1)->setTimezone('UTC')->toIso8601String();
        $eventData['event_end_utc'] = Carbon::parse("$date $editend2", $tz1)->setTimezone('UTC')->toIso8601String();
        $response2 = $this->post('/party/edit/'.$event->idevents, $eventData);
        $event->refresh();
        $this->assertEquals($eventData['event_start_utc'], $event->event_start_utc);
        $this->assertEquals($eventData['event_end_utc'], $event->event_end_utc);
    }

    public function timesProvider() {
        // The first event must be chronologically later than the second event once timezones are considered.
        return [
            [ '2037-01-15', 'Europe/London', '12:00:00', '12:00:00', 'Europe/Brussels', '12:00:00', '13:00:00', '13:00:00', '14:00:00' ],
            [ '2037-01-01', 'Europe/London', '12:00:00', '12:00:00', 'Europe/Brussels', '12:00:00', '13:00:00', '13:00:00', '14:00:00'  ],
            [ '2037-06-15', 'Europe/London', '12:00:00', '12:00:00', 'Europe/Brussels', '12:00:00', '13:00:00', '13:00:00', '14:00:00'  ],
            [ '2037-06-01', 'Europe/London', '12:00:00', '12:00:00', 'Europe/Brussels', '12:00:00', '13:00:00', '13:00:00', '14:00:00'  ],
            [ '2037-01-01', 'Asia/Samarkand', '11:10:00', '13:20:00', 'Europe/Amsterdam', '12:15:00', '14:35:00', '13:00:00', '14:00:00' ],
        ];
    }

    public function testOldDateFieldException() {
        $this->expectException(\Exception::class);
        $p = new Party();
        $p->event_date = '1970-01-01';
    }

    public function testOldStartFieldException() {
        $this->expectException(\Exception::class);
        $p = new Party();
        $p->start = '10:00';
    }

    public function testOldEndFieldException() {
        $this->expectException(\Exception::class);
        $p = new Party();
        $p->end = '10:00';
    }

    public function testTimezoneChangeUpdatesFutureEvents() {
        // Create a group.
        $g = factory(Group::class)->create([
                                               'timezone' => 'Asia/Samarkand'
                                           ]);

        $host = factory(User::class)->states('Restarter')->create();
        $g->addVolunteer($host);
        $g->makeMemberAHost($host);

        // Create a future event - will inherit the group timezone.
        $this->actingAs($host);
        $event = factory(Party::class)->raw();
        unset($event['timezone']);

        $event_start = Carbon::createFromTimestamp(time())->setTimezone('UTC')->addDay(2);;
        $event_end = $event_start->addHour(2);

        $event['event_start_utc'] = $event_start;
        $event['event_end_utc'] = $event_end;
        $event['group'] = $g->idgroups;
        $response = $this->post('/party/create/', $event);
        $response->assertRedirect();

        $party = Party::latest()->first();
        self::assertEquals('Asia/Samarkand', $party->timezone);

        // Now edit the group timezone as though we were an admin.  This expects some extra attributes.
        $atts = $g->getAttributes();
        $atts['timezone'] = 'Europe/London';
        $this->post('/group/edit/'.$g->idgroups, $atts);

        // This should have updated the timezone of the event.
        $party->refresh();
        self::assertEquals('Europe/London', $party->timezone);
    }
}
