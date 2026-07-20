<?php

namespace Tests\Feature\Dashboard;

use App\Group;
use App\User;
use Auth;
use Tests\TestCase;

class APIv2DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testRequiresAuthentication(): void
    {
        // Json variant needed here specifically: without an Accept:application/json header,
        // an unauthenticated request to an auth:sanctum,api route redirects (302) rather than
        // returning a JSON 401 - see AuthenticationException::redirectTo(). Every other call in
        // this file uses the plain (OpenAPI-validated) verb.
        $response = $this->getJson('/api/v2/dashboard');

        $response->assertStatus(401);
    }

    public function testHasLocationFalseWithoutCoordinates(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'dash-tok-1']);
        $this->actingAs($user);

        $response = $this->get('/api/v2/dashboard?api_token=dash-tok-1');
        $response->assertSuccessful();

        $json = $response->json('data');
        $this->assertFalse($json['has_location']);
        $this->assertEquals([], $json['nearby_groups']);
        $this->assertEquals([], $json['new_nearby_groups']);
    }

    public function testHasLocationTrueAndNearbyGroupsPopulated(): void
    {
        $user = User::factory()->restarter()->create([
            'api_token' => 'dash-tok-2',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
        ]);
        $this->actingAs($user);

        // createGroup() geocodes 'London' via the mocked Geocoder to the same coordinates.
        $other = User::factory()->host()->create(['api_token' => 'dash-tok-2-host']);
        $this->actingAs($other);
        $idgroups = $this->createGroup('Nearby Group', 'https://therestartproject.org', 'London');

        $this->app['auth']->forgetGuards();
        $this->actingAs($user);

        $response = $this->get('/api/v2/dashboard?api_token=dash-tok-2');
        $response->assertSuccessful();

        $json = $response->json('data');
        $this->assertTrue($json['has_location']);

        $ids = array_column($json['nearby_groups'], 'id');
        $this->assertContains($idgroups, $ids);

        $entry = collect($json['nearby_groups'])->firstWhere('id', $idgroups);
        $this->assertEquals('Nearby Group0', $entry['name']);
        // Both are geocoded to the exact same mocked London coordinates, so distance is legitimately 0.
        $this->assertIsNumeric($entry['distance']);
        $this->assertEquals('London', $entry['location']);

        // Also within the "new" (created within last month) nearby window.
        $newIds = array_column($json['new_nearby_groups'], 'id');
        $this->assertContains($idgroups, $newIds);
    }

    public function testYourGroupsReflectsMembershipWithRoleAndArchivedFlag(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dash-tok-3']);
        $this->actingAs($host);

        $idgroups = $this->createGroup('My Group', 'https://therestartproject.org', 'London');

        $response = $this->get('/api/v2/dashboard?api_token=dash-tok-3');
        $response->assertSuccessful();

        $json = $response->json('data');
        $entry = collect($json['your_groups'])->firstWhere('id', $idgroups);

        $this->assertNotNull($entry, 'Dashboard should list the group the acting user created/hosts.');
        $this->assertEquals('My Group0', $entry['name']);
        $this->assertEquals(\App\Role::HOST, $entry['role']);
        $this->assertFalse($entry['archived']);
        $this->assertNull($entry['archived_at']);
        $this->assertNull($entry['image_url']);

        // Archive it and confirm the flag (and archived_at, which GroupArchivedBadge-equivalent
        // UI needs to render the archived-since date) both flip. archived_at is a DATE column
        // (see database/migrations/2024_08_27_123452_group_archive.php), so the time-of-day is
        // truncated on save - compare against midnight, matching Group.php's own convention.
        $archivedAt = now()->startOfDay();
        Group::find($idgroups)->update(['archived_at' => $archivedAt]);
        $this->app['auth']->forgetGuards();
        $this->actingAs($host);

        $response = $this->get('/api/v2/dashboard?api_token=dash-tok-3');
        $entry = collect($response->json('data.your_groups'))->firstWhere('id', $idgroups);
        $this->assertTrue($entry['archived']);
        $this->assertEquals($archivedAt->toIso8601String(), $entry['archived_at']);
    }

    public function testYourGroupsCappedAtFive(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dash-tok-4']);
        $this->actingAs($host);

        for ($i = 0; $i < 6; $i++) {
            $this->createGroup('Capped Group', 'https://therestartproject.org', 'London');
        }

        $response = $this->get('/api/v2/dashboard?api_token=dash-tok-4');
        $response->assertSuccessful();

        $this->assertCount(5, $response->json('data.your_groups'));
    }

    public function testUpcomingEventsIncludesEventsTheUserHosts(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dash-tok-5']);
        $this->actingAs($host);

        $idgroups = $this->createGroup('Event Group', 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+7 day');

        $response = $this->get('/api/v2/dashboard?api_token=dash-tok-5');
        $response->assertSuccessful();

        $json = $response->json('data');
        $entry = collect($json['upcoming_events'])->firstWhere('id', $idevents);

        $this->assertNotNull($entry, 'Dashboard should list an event the acting user hosts.');
        $this->assertTrue($entry['attending']);
        $this->assertEquals($idgroups, $entry['group']['id']);
        $this->assertEquals('Event Group0', $entry['group']['name']);
        $this->assertArrayHasKey('start', $entry);
        $this->assertArrayHasKey('end', $entry);
        $this->assertArrayHasKey('timezone', $entry);
        $this->assertArrayHasKey('online', $entry);
        $this->assertArrayHasKey('location', $entry);
    }
}
