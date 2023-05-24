<?php

namespace Tests\Feature\Dashboard;

use App\Group;
use App\Party;
use App\Role;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class BasicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $this->idgroups = $this->createGroup();
        $this->get('/logout');
    }

    /**
     *@dataProvider provider
     */
    public function testPageLoads($city, $country, $lat, $lng, $nearbyGroupCount)
    {
        // Test the dashboard page loads and shows a nearby group when relevant.
        $user = User::factory()->host()->create();
        $this->actingAs($user);

        $user = User::factory()->restarter()->create();
        $user->update([
            'location' => $city,
            'country' => $country,
            'latitude' => $lat,
            'longitude' => $lng,
            ]);
        $user->save();
        $user->refresh();
        $this->assertEquals($user->country, $country);
        $this->assertEquals($user->location, $city);
        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $props = $this->assertVueProperties($response, [
            [],
            [
                'administrator' => 'false',
                'host' => 'false',
                'restarter' => 'true',
                'network-coordinator' => 'false',
                'location' => "$city",
                ':your-groups' => '[]',
                ':upcoming-events' => '[]',
                'see-all-topics-link' => env('DISCOURSE_URL').'/latest',
                ':is-logged-in' => 'true',
                'discourse-base-url' => env('DISCOURSE_URL'),
            ],
        ]);

        $this->assertEquals($nearbyGroupCount, count(json_decode($props[1][':nearby-groups'], true)));
        $this->assertEquals($nearbyGroupCount, count(json_decode($props[1][':new-groups'], true)));

        // Test Discourse API call which will be made by the Vue client.
        $response = $this->get('/api/talk/topics');
        $response->assertSuccessful();
        $ret = json_decode($response->getContent(), TRUE);
        self::assertEquals('success', $ret['success']);
        self::assertTrue(array_key_exists('topics', $ret));
    }

    public function provider()
    {
        return [
            ['London', 'GB', 51.5465, -0.10581, 1],    // Known location, nearby group
            [null, 'GB', null, null, 0],               // Unknown location, no nearby group
            ['Lima', 'PE', -12.0464, -77.04280, 0],    // Known location, no nearby group
            [null, 'PE', null, null, 0],                // Unknown location, no nearby group
        ];
    }

    public function testUpcomingEvents() {
        $host = User::factory()->restarter()->create();

        // Create an event.
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $event = Party::factory()->create([
                                                   'group' => $this->idgroups,
                                                   'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                                   'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                                   'free_text' => 'A test event',
                                                   'location' => 'London'
                                               ]);

        // Join the group - as a Restarter.
        $this->actingAs($host);
        $this->get('/group/join/' . $this->idgroups);

        // Should not show in upcoming as not yet approved.
        $response1 = $this->get('/dashboard');
        $response1->assertDontSeeText('A test event');

        // Admin approves the event.
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response1b = $this->get('/party/edit/'.$event->idevents);

        $props = $this->getVueProperties($response1b);
        $this->assertEquals($event->idevents, json_decode($props[1][':idevents'], TRUE));

        $eventData = $event->getAttributes();
        $eventData['id'] = $event->idevents;
        $eventData['moderate'] = 'approve';
        $response1a = $this->patch('/api/v2/events/'.$event->idevents, $this->eventAttributesToAPI($eventData));

        // Should now show as an upcoming event, both on dashboard page and events page.
        $this->actingAs($host);
        $response2 = $this->get('/dashboard');

        $props = $this->assertVueProperties($response2, [
            [],
            [
                ':is-logged-in' => 'true'
            ]
        ]);
        $upcomingEvents = json_decode($props[1][':upcoming-events'], TRUE);
        $this->assertEquals($event->idevents, $upcomingEvents[0]['idevents']);
        $this->assertEquals(true, $upcomingEvents[0]['approved']);

        $response3 = $this->get('/party');

        $props = $this->assertVueProperties($response3, [
            [],
            [
                ':canedit' => 'false'
            ]
        ]);
        $initialEvents = json_decode($props[1][':initial-events'], TRUE);
        $this->assertEquals($event->idevents, $initialEvents[0]['idevents']);
        $this->assertEquals(true, $initialEvents[0]['approved']);

        // Invite a second host to the group.
        $host2 = User::factory()->restarter()->create([
            'location' => 'London',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583
        ]);
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response4 = $this->post('/group/invite', [
            'group_name' => 'Test Group',
            'group_id' => $this->idgroups,
            'manual_invite_box' => $host2->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response4->assertSessionHas('success');

        // Should not show in upcoming as not yet a member, but should show in nearby.
        $this->get('/logout');
        $this->actingAs($host2);

        $response5 = $this->get('/dashboard');
        $props = $this->assertVueProperties($response5, [
            [],
            [
                ':is-logged-in' => 'true'
            ]
        ]);
        $upcomingEvents = json_decode($props[1][':upcoming-events'], TRUE);
        $this->assertEquals(0, count($upcomingEvents));

        $response6 = $this->get('/party');

        $props = $this->assertVueProperties($response6, [
            [],
            [
                ':canedit' => 'false'
            ]
        ]);
        $initialEvents = json_decode($props[1][':initial-events'], TRUE);
        $this->assertEquals($event->idevents, $initialEvents[0]['idevents']);
        $this->assertTrue($initialEvents[0]['nearby']);
        $this->assertEquals(true, $initialEvents[0]['approved']);
    }
}
