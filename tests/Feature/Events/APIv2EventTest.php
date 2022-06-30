<?php

namespace Tests\Feature;

use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class APIv2EventTest extends TestCase
{
    public function testGetEventsForGroup() {
        $user = factory(User::class)->states('Administrator')->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup();
        $id1 = $this->createEvent($idgroups, 'tomorrow');
        $id2 = $this->createEvent($idgroups, 'yesterday');

        // Test invalid group id.
        try {
            $this->get('/api/v2/groups/-1/events');
            $this->assertFalse(true);
        } catch (ModelNotFoundException $e) {
        }

        // Get all events.
        $response = $this->get("/api/v2/groups/$idgroups/events");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(2, count($json['data']));
        $this->assertEquals($id1, $json['data'][0]['id']);
        $this->assertEquals($id2, $json['data'][1]['id']);

        // Get future events
        $response = $this->get("/api/v2/groups/$idgroups/events?start="  . urlencode(Carbon::now()->toIso8601String()));
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($json['data']));
        $this->assertEquals($id1, $json['data'][0]['id']);

        // Get past.
        $response = $this->get("/api/v2/groups/$idgroups/events?end="  . urlencode(Carbon::now()->toIso8601String()));
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($json['data']));
        $this->assertEquals($id2, $json['data'][0]['id']);
    }
}
