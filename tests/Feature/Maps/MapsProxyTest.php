<?php

namespace Tests\Feature\Maps;

use App\Role;
use App\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapsProxyTest extends TestCase
{
    public function test_autocomplete_requires_authentication(): void
    {
        $this->withExceptionHandling();
        $response = $this->get('/maps/autocomplete?input=london');
        $response->assertRedirect('/login');
    }

    public function test_place_details_requires_authentication(): void
    {
        $this->withExceptionHandling();
        $response = $this->get('/maps/place-details?place_id=ChIJdd4hrwug2EcRmSrV3Vo6llI');
        $response->assertRedirect('/login');
    }

    public function test_autocomplete_returns_predictions_for_authenticated_user(): void
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

        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->get('/maps/autocomplete?input=london');

        $response->assertStatus(200);
        $response->assertJsonStructure(['predictions']);
        $response->assertJsonCount(1, 'predictions');
        $response->assertJsonPath('predictions.0.place_id', 'ChIJdd4hrwug2EcRmSrV3Vo6llI');
        $response->assertJsonPath('predictions.0.description', 'London, UK');
    }

    public function test_autocomplete_requires_input_parameter(): void
    {
        $this->withExceptionHandling();
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->getJson('/maps/autocomplete');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['input']);
    }

    public function test_place_details_returns_location_for_authenticated_user(): void
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

        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->get('/maps/place-details?place_id=ChIJdd4hrwug2EcRmSrV3Vo6llI');

        $response->assertStatus(200);
        $response->assertJsonPath('result.geometry.location.lat', 51.5074);
        $response->assertJsonPath('result.formatted_address', 'London, UK');
    }

    public function test_place_details_requires_place_id_parameter(): void
    {
        $this->withExceptionHandling();
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->getJson('/maps/place-details');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['place_id']);
    }
}
