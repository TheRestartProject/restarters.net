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

        // Create an event for each.
        $this->actingAs($host1);
        $event = factory(Party::class)->raw();
        unset($event['timezone']);
        $event['event_start_utc'] = '2037-01-01T06:10:00+05:00';
        $event['event_end_utc'] = '2037-01-01T08:20:00+05:00';
        $event['group'] = $g1->idgroups;
        $response = $this->post('/party/create/', $event);
        $response->assertStatus(302);

        $this->actingAs($host2);
        $event = factory(Party::class)->raw();
        unset($event['timezone']);
        $event['group'] = $g2->idgroups;
        $event['event_start_utc'] = '2037-01-01T10:15:00+01:00';
        $event['event_end_utc'] = '2037-01-01T12:35:00+01:00';
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
        // - The UTC fields should be returned, but having converted to UTC and therefore having +00:00.
        // - The timezone should be set.
        $this->assertEquals('2037-01-01T09:15:00+00:00', $events[0]['event_start_utc']);
        $this->assertEquals('2037-01-01T11:35:00+00:00', $events[0]['event_end_utc']);
        $this->assertEquals('Europe/Amsterdam', $events[0]['timezone']);
        $this->assertEquals('2037-01-01T01:10:00+00:00', $events[1]['event_start_utc']);
        $this->assertEquals('2037-01-01T03:20:00+00:00', $events[1]['event_end_utc']);
        $this->assertEquals('Asia/Samarkand', $events[1]['timezone']);

        // A different way of confirming the same thing is to convert the input and returned times to epoch -
        // they should be the same.
        $this->assertEquals(strtotime($events[0]['event_start_utc']), (new \DateTime('2037-01-01 10:15', new \DateTimeZone('Europe/Amsterdam')))->format('U'));
        $this->assertEquals(strtotime($events[0]['event_end_utc']), (new \DateTime('2037-01-01 12:35', new \DateTimeZone('Europe/Amsterdam')))->format('U'));
        $this->assertEquals(strtotime($events[1]['event_start_utc']), (new \DateTime('2037-01-01 06:10', new \DateTimeZone('Asia/Samarkand')))->format('U'));
        $this->assertEquals(strtotime($events[1]['event_end_utc']), (new \DateTime('2037-01-01 08:20', new \DateTimeZone('Asia/Samarkand')))->format('U'));
    }
}
