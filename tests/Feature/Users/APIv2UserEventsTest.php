<?php

namespace Tests\Feature\Users;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2UserEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * Builds a group+event owned by $host directly via Eloquent rather than nested API calls.
     *
     * The "other user's" fixture in these tests must never be created by acting as that user
     * and driving it through the HTTP test client (createGroup()/createEvent()): doing so, then
     * switching identity via forgetGuards()+actingAs() for the actual assertion call, was found
     * to leave the 'api'/'web' guards resolving back to the FIRST identity by the time the
     * request reaches the controller - a Laravel test-harness quirk specific to nested
     * simulated HTTP requests sharing one container, not a bug in the endpoint or in
     * Party::forUser() (confirmed by direct diagnostic: querying EventsUsers/Party::forUser()
     * for the correct user id, right after the response, returns empty as expected - yet the
     * response itself showed the FIRST user's event). Building fixtures with plain model calls
     * sidesteps the guard state entirely; only the user actually under test ever calls actingAs().
     */
    private function createEventDirectly(
        User $host,
        bool $groupApproved,
        string $eventStartOffset = '+1 week',
        array $groupOverrides = [],
        array $eventOverrides = []
    ): array {
        $group = Group::factory()->create(array_merge(['approved' => $groupApproved], $groupOverrides));
        UserGroups::create(['user' => $host->id, 'group' => $group->idgroups, 'status' => 1, 'role' => Role::HOST]);

        $start = Carbon::parse($eventStartOffset);
        $party = Party::factory()->create(array_merge([
            'group' => $group->idgroups,
            'approved' => true,
            'user_id' => $host->id,
            // Bypassing the API means no geocoding step ran - upcomingEventsInUserArea()'s
            // distance formula needs real lat/lng on the event, not just the group.
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
            'event_start_utc' => $start->toIso8601String(),
            'event_end_utc' => $start->copy()->addHours(2)->toIso8601String(),
        ], $eventOverrides));
        EventsUsers::create(['event' => $party->idevents, 'user' => $host->id, 'status' => '1', 'role' => Role::HOST]);

        return [$group->idgroups, $party->idevents];
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson('/api/v2/users/me/events');

        $response->assertStatus(401);
    }

    public function testIncludesEventsTheUserHosts(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'ue-tok-host']);
        [$idgroups, $idevents] = $this->createEventDirectly($host, true);
        $this->actingAs($host);

        $response = $this->get('/api/v2/users/me/events?api_token=ue-tok-host');

        $response->assertSuccessful();
        $row = collect($response->json('data'))->firstWhere('id', $idevents);
        $this->assertNotNull($row);
        $this->assertTrue($row['attending']);
        $this->assertFalse($row['all']);
        $this->assertFalse($row['nearby']);
        $this->assertEquals($idgroups, $row['group']['id']);
    }

    public function testIncludesOtherApprovedUpcomingEventsWhenUserHasNoLocation(): void
    {
        $otherHost = User::factory()->host()->create();
        [, $idevents] = $this->createEventDirectly($otherHost, true);

        $user = User::factory()->restarter()->create(['api_token' => 'ue-tok-other']);
        $this->actingAs($user);

        $response = $this->get('/api/v2/users/me/events?api_token=ue-tok-other');

        $response->assertSuccessful();
        $row = collect($response->json('data'))->firstWhere('id', $idevents);
        $this->assertNotNull($row);
        $this->assertTrue($row['all']);
        $this->assertFalse($row['nearby']);
        $this->assertFalse($row['attending']);
    }

    public function testExcludesUnapprovedGroupsEvents(): void
    {
        $otherHost = User::factory()->host()->create();
        [, $idevents] = $this->createEventDirectly($otherHost, false);

        $user = User::factory()->restarter()->create(['api_token' => 'ue-tok-unapproved']);
        $this->actingAs($user);

        $response = $this->get('/api/v2/users/me/events?api_token=ue-tok-unapproved');

        $response->assertSuccessful();
        $row = collect($response->json('data'))->firstWhere('id', $idevents);
        $this->assertNull($row);
    }

    public function testIncludesNearbyUpcomingEventsWhenUserHasLocation(): void
    {
        $otherHost = User::factory()->host()->create();
        [$idgroups, $idevents] = $this->createEventDirectly($otherHost, true);
        $network = Network::factory()->create(['shortname' => 'nearbyevnet'.Str::random(6)]);
        $network->addGroup(Group::find($idgroups));

        $user = User::factory()->restarter()->create([
            'api_token' => 'ue-tok-nearby',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
        ]);
        $this->actingAs($user);

        $response = $this->get('/api/v2/users/me/events?api_token=ue-tok-nearby');

        $response->assertSuccessful();
        $row = collect($response->json('data'))->firstWhere('id', $idevents);
        $this->assertNotNull($row);
        $this->assertTrue($row['nearby']);
        $this->assertTrue($row['all']);
        $this->assertFalse($row['attending']);
    }

    public function testDoesNotDuplicateAnEventAlreadyIncludedAsHosted(): void
    {
        $host = User::factory()->host()->create([
            'api_token' => 'ue-tok-dedup',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
        ]);
        [$idgroups, $idevents] = $this->createEventDirectly($host, true);
        $network = Network::factory()->create(['shortname' => 'dedupnet'.Str::random(6)]);
        $network->addGroup(Group::find($idgroups));
        $this->actingAs($host);

        $response = $this->get('/api/v2/users/me/events?api_token=ue-tok-dedup');

        $response->assertSuccessful();
        $matches = collect($response->json('data'))->where('id', $idevents);
        $this->assertCount(1, $matches);
    }

    public function testGroupCountryIsIncluded(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'ue-tok-country']);
        [, $idevents] = $this->createEventDirectly($host, true, '+1 week', ['country_code' => 'GB']);
        $this->actingAs($host);

        $response = $this->get('/api/v2/users/me/events?api_token=ue-tok-country');

        $response->assertSuccessful();
        $row = collect($response->json('data'))->firstWhere('id', $idevents);
        $this->assertNotNull($row);
        $this->assertEquals(Fixometer::getCountryFromCountryCode('GB'), $row['group']['country']);
    }

    public function testUpcomingEventIncludesInvitedAndVolunteerCounts(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'ue-tok-upcoming-stats']);
        // 'volunteers'/'pax' are denormalised counters on the event row itself (not derived live
        // from events_users - see Party::getEventStats(), Party.php:581), but 'volunteers' isn't
        // static once the event exists: EventsUsersObserver::created() increments it via
        // $event->increment('volunteers') whenever a *confirmed* (status='1') events_users row is
        // created for a real user (app/Observers/EventsUsersObserver.php:31-48,88-95).
        // createEventDirectly() itself creates exactly one such row, for $host with status='1', so
        // it always adds +1 on top of whatever 'volunteers' is seeded to here. Seeding 3 (not the
        // intended 4) cancels that out, landing on a clean, explained "4 confirmed volunteers"
        // scenario below rather than an unexplained 5.
        [, $idevents] = $this->createEventDirectly($host, true, '+1 week', [], ['volunteers' => 3, 'pax' => 0]);

        // An invited-but-not-yet-confirmed attendee: counted in 'invited' (status <> '1'). Because
        // its status isn't '1', the observer's created() takes the "not confirmed" branch
        // (removed() with count=false, EventsUsersObserver.php:42-46,97-101), which does NOT touch
        // 'volunteers' - so this row only affects 'invited', not the count asserted below.
        $invitee = User::factory()->restarter()->create();
        EventsUsers::create(['event' => $idevents, 'user' => $invitee->id, 'status' => '0', 'role' => Role::RESTARTER]);

        $this->actingAs($host);
        $response = $this->get('/api/v2/users/me/events?api_token=ue-tok-upcoming-stats');

        $response->assertSuccessful();
        $row = collect($response->json('data'))->firstWhere('id', $idevents);
        $this->assertNotNull($row);
        $this->assertArrayHasKey('stats', $row);
        // 3 seeded + 1 from createEventDirectly()'s own confirmed host row = 4.
        $this->assertEquals(4, $row['stats']['volunteers']);
        $this->assertEquals(1, $row['stats']['invited']);
        // The event hasn't started, so the device/waste/co2 counters stay at zero.
        $this->assertEquals(0, $row['stats']['fixed_devices']);
    }

    public function testFinishedEventIncludesFullStatsBlock(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'ue-tok-finished-stats']);
        // Seeded 1 lower than the 'volunteers' value asserted below - createEventDirectly()'s own
        // confirmed (status='1') events_users row for $host adds +1 via
        // EventsUsersObserver::created()/confirmed() (full mechanism explained in
        // testUpcomingEventIncludesInvitedAndVolunteerCounts above).
        [, $idevents] = $this->createEventDirectly($host, true, '-1 week', [], ['volunteers' => 1, 'pax' => 8]);

        // One of each repair status, on test-seeded category 333 (weight=3, footprint=3, powered=1).
        // getEventStats() derives waste_total from the category weight and co2_total from the
        // category footprint (both via the categories JOIN), so the category must carry BOTH for
        // the >0 assertions below - category 11 ("Desktop computer") has NULL weight AND footprint
        // in the seed, which yields waste_total=0/co2_total=0.
        Device::factory()->fixed()->create(['category' => 333, 'category_creation' => 333, 'event' => $idevents]);
        Device::factory()->create([
            'category' => 333,
            'category_creation' => 333,
            'event' => $idevents,
            'repair_status' => Device::REPAIR_STATUS_REPAIRABLE,
        ]);
        Device::factory()->create([
            'category' => 333,
            'category_creation' => 333,
            'event' => $idevents,
            'repair_status' => Device::REPAIR_STATUS_ENDOFLIFE,
        ]);

        $this->actingAs($host);
        $response = $this->get('/api/v2/users/me/events?api_token=ue-tok-finished-stats');

        $response->assertSuccessful();
        $row = collect($response->json('data'))->firstWhere('id', $idevents);
        $this->assertNotNull($row);
        $this->assertEquals(8, $row['stats']['participants']);
        // 1 seeded + 1 from createEventDirectly()'s own confirmed host row = 2.
        $this->assertEquals(2, $row['stats']['volunteers']);
        $this->assertEquals(1, $row['stats']['fixed_devices']);
        $this->assertEquals(1, $row['stats']['repairable_devices']);
        $this->assertEquals(1, $row['stats']['dead_devices']);
        $this->assertGreaterThan(0, $row['stats']['waste_total']);
        $this->assertGreaterThan(0, $row['stats']['co2_total']);
    }
}
