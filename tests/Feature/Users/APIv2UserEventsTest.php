<?php

namespace Tests\Feature\Users;

use App\EventsUsers;
use App\Group;
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
    private function createEventDirectly(User $host, bool $groupApproved, string $eventStartOffset = '+1 week'): array
    {
        $group = Group::factory()->create(['approved' => $groupApproved]);
        UserGroups::create(['user' => $host->id, 'group' => $group->idgroups, 'status' => 1, 'role' => Role::HOST]);

        $start = Carbon::parse($eventStartOffset);
        $party = Party::factory()->create([
            'group' => $group->idgroups,
            'approved' => true,
            'user_id' => $host->id,
            // Bypassing the API means no geocoding step ran - upcomingEventsInUserArea()'s
            // distance formula needs real lat/lng on the event, not just the group.
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
            'event_start_utc' => $start->toIso8601String(),
            'event_end_utc' => $start->copy()->addHours(2)->toIso8601String(),
        ]);
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
}
