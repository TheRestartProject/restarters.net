<?php

namespace Tests\Feature\Maps;

use App\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * v2 (bearer/api_token, auth:sanctum,api) equivalents of MapsProxyTest's session-auth
 * /maps/* routes - see api-contracts-phase-b.md B6. Same MapsProxyController, same
 * param names (input/types/place_id), just moved under /api/v2/maps/*.
 */
class APIv2MapsProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testAutocompleteRequiresAuthentication(): void
    {
        // Json variant here specifically - without Accept:application/json, an unauthenticated
        // auth:sanctum,api request redirects (302) rather than returning JSON 401.
        $response = $this->getJson('/api/v2/maps/autocomplete?input=london');

        $response->assertStatus(401);
    }

    public function testPlaceDetailsRequiresAuthentication(): void
    {
        $response = $this->getJson('/api/v2/maps/place-details?place_id=ChIJdd4hrwug2EcRmSrV3Vo6llI');

        $response->assertStatus(401);
    }

    public function testAutocompleteReturnsPredictionsForAuthenticatedUser(): void
    {
        Http::fake([
            'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
                'status' => 'OK',
                'predictions' => [
                    [
                        'description' => 'London, UK',
                        'place_id' => 'ChIJdd4hrwug2EcRmSrV3Vo6llI',
                        'structured_formatting' => [
                            'main_text' => 'London',
                            'secondary_text' => 'UK',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->restarter()->create(['api_token' => 'maps-tok-1']);
        $this->actingAs($user);

        $response = $this->get('/api/v2/maps/autocomplete?input=london&api_token=maps-tok-1');

        $response->assertStatus(200);
        $response->assertJsonPath('predictions.0.place_id', 'ChIJdd4hrwug2EcRmSrV3Vo6llI');
        $response->assertJsonPath('predictions.0.description', 'London, UK');
    }

    public function testAutocompleteRequiresInputParameter(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'maps-tok-2']);
        $this->actingAs($user);

        // Json variant: a validation failure on a non-JSON-accepting request redirects (302).
        $response = $this->getJson('/api/v2/maps/autocomplete?api_token=maps-tok-2');

        $response->assertStatus(422);
    }

    public function testPlaceDetailsReturnsLocationForAuthenticatedUser(): void
    {
        Http::fake([
            'maps.googleapis.com/maps/api/place/details/*' => Http::response([
                'status' => 'OK',
                'result' => [
                    'geometry' => [
                        'location' => [
                            'lat' => 51.5074,
                            'lng' => -0.1278,
                        ],
                    ],
                    'formatted_address' => 'London, UK',
                ],
            ], 200),
        ]);

        $user = User::factory()->restarter()->create(['api_token' => 'maps-tok-3']);
        $this->actingAs($user);

        $response = $this->get('/api/v2/maps/place-details?place_id=ChIJdd4hrwug2EcRmSrV3Vo6llI&api_token=maps-tok-3');

        $response->assertStatus(200);
        $response->assertJsonPath('result.geometry.location.lat', 51.5074);
        $response->assertJsonPath('result.formatted_address', 'London, UK');
    }

    public function testPlaceDetailsRequiresPlaceIdParameter(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'maps-tok-4']);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/maps/place-details?api_token=maps-tok-4');

        $response->assertStatus(422);
    }

    // The legacy session-auth web route /maps/autocomplete was removed at the
    // Nuxt cutover (design.md §5); the SPA uses /api/v2/maps/* exclusively,
    // covered by the tests above.
}
