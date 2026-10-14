<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Helpers\Geocoder;
use App\Network;
use App\User;
use Tests\TestCase;

/**
 * The group add/edit page shows a draggable map so users can fine-tune the
 * pin after geocoding. When the client supplies an explicit lat/lng, the API
 * must store those coordinates rather than overwriting them with the
 * server-side geocode of the location text.
 */
class GroupLatLngOverrideTest extends TestCase
{
    private function apiUser(): User
    {
        $user = User::factory()->administrator()->create(['api_token' => '1234']);
        $network = Network::factory()->create(['shortname' => 'network']);
        $user->repair_network = $network->id;
        $user->save();

        return $user;
    }

    /** @test */
    public function create_uses_client_lat_lng_when_supplied(): void
    {
        $this->apiUser();

        // Deliberately different from the geocoder's London (51.5073, -0.1277)
        // so we can tell whose coordinates won.
        $response = $this->post('/api/v2/groups?api_token=1234', [
            'name' => 'Dragged Group',
            'location' => 'London',
            'description' => 'Some text.',
            'timezone' => 'Europe/London',
            'lat' => 51.5197,
            'lng' => -0.1409,
        ]);

        $response->assertSuccessful();
        $group = Group::findOrFail(json_decode($response->getContent(), true)['id']);

        $this->assertEqualsWithDelta(51.5197, $group->latitude, 0.00001);
        $this->assertEqualsWithDelta(-0.1409, $group->longitude, 0.00001);
        // Country still comes from geocoding the location text.
        $this->assertEquals('GB', $group->country_code);
    }

    /** @test */
    public function create_without_lat_lng_still_geocodes(): void
    {
        $this->apiUser();

        $response = $this->post('/api/v2/groups?api_token=1234', [
            'name' => 'Geocoded Group',
            'location' => 'London',
            'description' => 'Some text.',
            'timezone' => 'Europe/London',
        ]);

        $response->assertSuccessful();
        $group = Group::findOrFail(json_decode($response->getContent(), true)['id']);

        $this->assertEqualsWithDelta(51.5073509, $group->latitude, 0.00001);
        $this->assertEqualsWithDelta(-0.1277583, $group->longitude, 0.00001);
    }

    /** @test */
    public function create_with_lat_lng_survives_geocode_failure(): void
    {
        $this->apiUser();

        // The location text doesn't geocode, but the user has placed the pin
        // themselves — that shouldn't block creation.
        $response = $this->post('/api/v2/groups?api_token=1234', [
            'name' => 'Pin Only Group',
            'location' => 'ForceGeocodeFailure',
            'description' => 'Some text.',
            'timezone' => 'Europe/London',
            'lat' => 48.8566,
            'lng' => 2.3522,
        ]);

        $response->assertSuccessful();
        $group = Group::findOrFail(json_decode($response->getContent(), true)['id']);

        $this->assertEqualsWithDelta(48.8566, $group->latitude, 0.00001);
        $this->assertEqualsWithDelta(2.3522, $group->longitude, 0.00001);
        $this->assertNull($group->country_code);
    }

    /** @test */
    public function create_rejects_out_of_range_lat_lng(): void
    {
        $this->apiUser();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->post('/api/v2/groups?api_token=1234', [
            'name' => 'Bad Coords Group',
            'location' => 'London',
            'description' => 'Some text.',
            'timezone' => 'Europe/London',
            'lat' => 123.0,
            'lng' => -190.0,
        ]);
    }

    /** @test */
    public function edit_uses_client_lat_lng_when_supplied(): void
    {
        $this->apiUser();

        $group = Group::factory()->create([
            'location' => 'London',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
            'country_code' => 'GB',
        ]);

        // Location text unchanged, pin dragged.
        $response = $this->patch('/api/v2/groups/' . $group->idgroups . '?api_token=1234', [
            'name' => $group->name,
            'location' => 'London',
            'description' => 'Some text.',
            'timezone' => 'Europe/London',
            'lat' => 51.5540,
            'lng' => -0.1744,
        ]);

        $response->assertSuccessful();
        $group->refresh();

        $this->assertEqualsWithDelta(51.5540, $group->latitude, 0.00001);
        $this->assertEqualsWithDelta(-0.1744, $group->longitude, 0.00001);
        // Unchanged location text keeps the existing country.
        $this->assertEquals('GB', $group->country_code);
    }

    /** @test */
    public function edit_without_lat_lng_keeps_existing_behaviour(): void
    {
        $this->apiUser();

        $group = Group::factory()->create([
            'location' => 'London',
            'latitude' => 10.0,
            'longitude' => 20.0,
            'country_code' => 'GB',
        ]);

        // Unchanged location, no coords: keeps existing coords (no re-geocode).
        $response = $this->patch('/api/v2/groups/' . $group->idgroups . '?api_token=1234', [
            'name' => $group->name,
            'location' => 'London',
            'description' => 'Some text.',
            'timezone' => 'Europe/London',
        ]);

        $response->assertSuccessful();
        $group->refresh();

        $this->assertEqualsWithDelta(10.0, $group->latitude, 0.00001);
        $this->assertEqualsWithDelta(20.0, $group->longitude, 0.00001);
    }
}
