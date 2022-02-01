<?php

namespace Tests\Unit;

use App\Group;
use App\Party;
use App\User;
use DB;
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
            'event_start_utc' => '2021-01-01T10:15:05+05:00',
            'event_end_utc' => '2021-01-01T13:45:05+05:00',
            'timezone' => NULL
        ]);

        // Check that the ISO times are as we would expect for this zone.
        self::assertEquals('2021-01-01T10:15:05+00:00', $e->event_start_utc);
        self::assertEquals('2021-01-01T13:45:05+00:00', $e->event_end_utc);

        // Check that the local times are as we expect.
        self::assertEquals('2021-01-01', $e->event_date);
        self::assertEquals('15:15:05', $e->start);
        self::assertEquals('18:45:05', $e->end);

        // Update the ISO times using a different timezone and check that the local times update.
        $e->event_start_utc = '2021-01-01T13:00:00+00:00';
        $e->event_end_utc = '2021-01-01T14:00:00+00:00';

        self::assertEquals('2021-01-01', $e->event_date);
        self::assertEquals('18:00:00', $e->start);
        self::assertEquals('19:00:00', $e->end);
    }

    public function testOrder() {
        // Two groups in different timezones.
        $g1 = factory(Group::class)->create([
                                               'timezone' => 'Asia/Samarkand'
                                           ]);

        $g2 = factory(Group::class)->create([
                                               'timezone' => 'Europe/Amsterdam'
                                           ]);

        // A host for each.
        $host1 = factory(User::class)->states('Host')->create();
        $g1->addVolunteer($host1);
        $g1->makeMemberAHost($host1);

        $host2 = factory(User::class)->states('Host')->create();
        $g1->addVolunteer($host2);
        $g1->makeMemberAHost($host2);

        // Create an event for each, using the old style local time create format.
        $this->actingAs($host1);
        $event = factory(Party::class)->raw();
        unset($event['event_start_utc']);
        unset($event['event_end_utc']);
        unset($event['timezone']);
        $event['group'] = $g1->idgroups;
        $event['event_date'] = '2037-01-01';
        $event['start'] = '11:10';
        $event['end'] = '13:20';
        $response = $this->post('/party/create/', $event);
        $response->assertStatus(302);

        $this->actingAs($host2);
        $event = factory(Party::class)->raw();
        unset($event['event_start_utc']);
        unset($event['event_end_utc']);
        unset($event['timezone']);
        $event['group'] = $g2->idgroups;
        $event['event_date'] = '2037-01-01';
        $event['start'] = '12:15';
        $event['end'] = '14:35';
        $response = $this->post('/party/create/', $event);
        $response->assertStatus(302);

        // Now get them and check the ordering works.
        $response = $this->get('/party');

        $props = $this->assertVueProperties($response, [
            [
                'heading-level' => 'h2',
            ],
        ]);

        $events = json_decode($props[0][':initial-events'], TRUE);

        // Check the returned events:
        // - The events should be Amsterdam first because that is the earliest actual time and therefore the soonest
        //   starting event.
        // - The local times (event_date, start, end) should be the same as we put in.
        // - The UTC fields should be returned with the times converted to UTC.
        // - The timezone should be set.
        $this->assertEquals('Europe/Amsterdam', $events[0]['timezone']);
        $this->assertEquals('2037-01-01T11:15:00+00:00', $events[0]['event_start_utc']);
        $this->assertEquals('2037-01-01T13:35:00+00:00', $events[0]['event_end_utc']);
        $this->assertEquals('2037-01-01', $events[0]['event_date']);
        $this->assertEquals('12:15:00', $events[0]['start']);
        $this->assertEquals('14:35:00', $events[0]['end']);
        $this->assertEquals('Asia/Samarkand', $events[1]['timezone']);
        $this->assertEquals('2037-01-01T06:10:00+00:00', $events[1]['event_start_utc']);
        $this->assertEquals('2037-01-01T08:20:00+00:00', $events[1]['event_end_utc']);
        $this->assertEquals('2037-01-01', $events[1]['event_date']);
        $this->assertEquals('11:10:00', $events[1]['start']);
        $this->assertEquals('13:20:00', $events[1]['end']);

        // A different way of confirming the same thing is to convert the input and returned times to epoch -
        // they should be the same.
        $this->assertEquals(strtotime($events[0]['event_start_utc']), (new \DateTime('2037-01-01 12:15', new \DateTimeZone('Europe/Amsterdam')))->format('U'));
        $this->assertEquals(strtotime($events[0]['event_end_utc']), (new \DateTime('2037-01-01 14:35', new \DateTimeZone('Europe/Amsterdam')))->format('U'));
        $this->assertEquals(strtotime($events[1]['event_start_utc']), (new \DateTime('2037-01-01 11:10', new \DateTimeZone('Asia/Samarkand')))->format('U'));
        $this->assertEquals(strtotime($events[1]['event_end_utc']), (new \DateTime('2037-01-01 13:20', new \DateTimeZone('Asia/Samarkand')))->format('U'));
    }
}
