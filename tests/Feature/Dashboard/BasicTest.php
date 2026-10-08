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
    protected $idgroups;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $this->idgroups = $this->createGroup();
    }

    /**
     *@dataProvider provider
     */
    public function testPageLoads($city, $country, $lat, $lng, $nearbyGroupCount): void
    {
        // Test the dashboard page loads and shows a nearby group when relevant.
        $user = User::factory()->host()->create();
        $this->actingAs($user);

        $user = User::factory()->restarter()->create();
        $user->update([
            'location' => $city,
            'country_code' => $country,
            'latitude' => $lat,
            'longitude' => $lng,
            ]);
        $user->save();
        $user->refresh();
        $this->assertEquals($country, $user->country_code);
        $this->assertEquals($city, $user->location);
        $this->actingAs($user);

        // The /dashboard Blade page is retired under the Nuxt cutover; the
        // SPA fetches its data from /api/v2/dashboard instead (see
        // DashboardController@indexv2). The v2 endpoints authenticate via the
        // token guard (auth:sanctum,api), so pass the user's api_token
        // explicitly rather than relying on the session actingAs guard.
        $response = $this->get('/api/v2/dashboard?api_token='.$user->api_token);
        $response->assertSuccessful();
        $data = $response->json('data');

        $this->assertEquals(! is_null($lat), $data['has_location']);
        $this->assertEquals($nearbyGroupCount, count($data['nearby_groups']));
        $this->assertEquals($nearbyGroupCount, count($data['new_nearby_groups']));

        // Test Discourse API call which will be made by the Vue client.
        $response = $this->get('/api/talk/topics?api_token='.$user->api_token);
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

    public function testUpcomingEvents(): void {
        // The /dashboard and /party Blade pages, and the /group/join/{id}
        // and /group/invite web routes, are all retired under the Nuxt
        // cutover. The business logic under test here — an unapproved
        // event doesn't count as "upcoming" for a member, an approved one
        // does, and a merely-invited (not yet joined) user doesn't see it
        // either — is real backend behaviour, so it's repointed to the
        // live equivalents: /api/v2/dashboard for the dashboard data,
        // POST /api/v2/groups/{id}/members/me for joining, and
        // POST /api/v2/groups/{id}/invites for inviting.
        $host = User::factory()->restarter()->create();

        // Create an event.
        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $event = Party::factory()->create([
                                                   'group' => $this->idgroups,
                                                   'event_start_utc' => '2130-01-01T12:13:00+00:00',
                                                   'event_end_utc' => '2130-01-01T13:14:00+00:00',
                                                   'free_text' => 'A test event',
                                                   'location' => 'London'
                                               ]);

        // Join the group - as a Restarter.
        $this->actingAs($host);
        $this->post('/api/v2/groups/' . $this->idgroups . '/members/me?api_token=' . $host->api_token)
            ->assertSuccessful();

        // Should not show in upcoming as not yet approved.
        $response1 = $this->get('/api/v2/dashboard');
        $response1->assertSuccessful();
        $upcomingEvents = $response1->json('data.upcoming_events');
        $this->assertFalse(collect($upcomingEvents)->contains('id', $event->idevents));

        // Admin approves the event.
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $eventData = $event->getAttributes();
        $eventData['id'] = $event->idevents;
        $eventData['moderate'] = 'approve';
        $response1a = $this->patch('/api/v2/events/'.$event->idevents . '?api_token=' . $admin->api_key, $this->eventAttributesToAPI($eventData));
        $response1a->assertSuccessful();
        $this->artisan("queue:work --stop-when-empty");
        $this->artisan("queue:work --stop-when-empty");

        // Should now show as an upcoming event on the dashboard.
        $this->actingAs($host);
        $response2 = $this->get('/api/v2/dashboard');
        $response2->assertSuccessful();
        $upcomingEvents = $response2->json('data.upcoming_events');
        $this->assertEquals($event->idevents, $upcomingEvents[0]['id']);

        // Invite a second host to the group.
        $host2 = User::factory()->restarter()->create([
            'location' => 'London',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583
        ]);
        $admin2 = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response4 = $this->post('/api/v2/groups/' . $this->idgroups . '/invites?api_token=' . $admin2->api_token, [
            'emails' => [$host2->email],
            'message' => 'Join us, but not in a creepy zombie way',
        ]);

        $response4->assertSuccessful();
        $this->assertEquals(1, $response4->json('data.invites_sent'));

        // Should not show in upcoming as not yet a confirmed member.
        $this->actingAs($host2);

        $response5 = $this->get('/api/v2/dashboard');
        $response5->assertSuccessful();
        $upcomingEvents = $response5->json('data.upcoming_events');
        $this->assertEquals(0, count($upcomingEvents));
    }
}
