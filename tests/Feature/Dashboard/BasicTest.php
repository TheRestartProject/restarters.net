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
        $user = factory(User::class)->states('Host')->create();
        $this->actingAs($user);

        $user = factory(User::class)->states('Restarter')->create();
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
            [
                'administrator' => 'false',
                'host' => 'false',
                'restarter' => 'true',
                'network-coordinator' => 'false',
                'location' => "$city",
                ':your-groups' => '[]',
                ':upcoming-events' => '[]',
                ':topics' => '[]',
                'see-all-topics-link' => env('DISCOURSE_URL').'/latest',
                ':is-logged-in' => 'true',
                'discourse-base-url' => env('DISCOURSE_URL'),
            ],
        ]);

        $this->assertEquals($nearbyGroupCount, count(json_decode($props[0][':nearby-groups'], true)));
        $this->assertEquals($nearbyGroupCount, count(json_decode($props[0][':new-groups'], true)));
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
        $host = factory(User::class)->states('Restarter')->create();

        // Create an event.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $event = factory(Party::class)->create([
                                                   'group' => $this->idgroups,
                                                   'event_date' => '2130-01-01',
                                                   'start' => '12:13',
                                                   'free_text' => 'A test event',
                                                   'location' => 'London'
                                               ]);

        // Join the group - as a Restarter.
        $this->actingAs($host);
        $this->get('/group/join/' . $this->idgroups);

        // Should not show in upcoming as not yet approved.
        $response = $this->get('/dashboard');
        $response->assertDontSeeText('A test event');

        // Admin approves the event.
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $eventData = $event->getAttributes();
        $eventData['wordpress_post_id'] = 100;
        $eventData['id'] = $event->idevents;
        $eventData['moderate'] = 'approve';
        $this->post('/party/edit/'.$event->idevents, $eventData);

        // Won't yet show on Dashboard, as we are a member of the group, but not a host on it.
        $this->actingAs($host);
        $response = $this->get('/dashboard');
        $props = $this->assertVueProperties($response, [
            [
                ':is-logged-in' => 'true'
            ]
        ]);
        $upcomingEvents = json_decode($props[0][':upcoming-events'], TRUE);
        $this->assertEquals(0, count($upcomingEvents));

        // Promote to a host.
        $group = Group::find($this->idgroups);
        $group->makeMemberAHost($host);

        // Should now show as an upcoming event, both on dashboard page and events page.
        $this->actingAs($host);
        $response = $this->get('/dashboard');

        $props = $this->assertVueProperties($response, [
            [
                ':is-logged-in' => 'true'
            ]
        ]);
        $upcomingEvents = json_decode($props[0][':upcoming-events'], TRUE);
        $this->assertEquals($event->idevents, $upcomingEvents[0]['idevents']);

        $response = $this->get('/party');

        $props = $this->assertVueProperties($response, [
            [
                ':canedit' => 'false'
            ]
        ]);
        $initialEvents = json_decode($props[0][':initial-events'], TRUE);
        $this->assertEquals($event->idevents, $initialEvents[0]['idevents']);

        // Invite a second host to the group.
        $host2 = factory(User::class)->states('Restarter')->create([
            'location' => 'London',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583
        ]);
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->post('/group/invite', [
            'group_name' => 'Test Group',
            'group_id' => $this->idgroups,
            'manual_invite_box' => $host2->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response->assertSessionHas('success');

        // Should not show in upcoming as not yet a member, but should show in nearby.
        $this->get('/logout');
        $this->actingAs($host2);

        $response = $this->get('/dashboard');
        $props = $this->assertVueProperties($response, [
            [
                ':is-logged-in' => 'true'
            ]
        ]);
        $upcomingEvents = json_decode($props[0][':upcoming-events'], TRUE);
        $this->assertEquals(0, count($upcomingEvents));

        $response = $this->get('/party');

        $props = $this->assertVueProperties($response, [
            [
                ':canedit' => 'false'
            ]
        ]);
        $initialEvents = json_decode($props[0][':initial-events'], TRUE);
        $this->assertEquals($event->idevents, $initialEvents[0]['idevents']);
        $this->assertTrue($initialEvents[0]['nearby']);
    }
}
