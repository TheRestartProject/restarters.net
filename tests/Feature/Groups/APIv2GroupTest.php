<?php

namespace Tests\Feature;

use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class APIv2GroupTest extends TestCase
{
    public function testGetEventsForGroup() {
        $user = factory(User::class)->states('Administrator')->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup();

        // Test invalid group id.
        try {
            $this->get('/api/v2/groups/-1');
            $this->assertFalse(true);
        } catch (ModelNotFoundException $e) {
        }

        // Get group.
        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($idgroups, $json['data']['id']);
        $this->assertTrue(array_key_exists('description', $json['data']));
        $this->assertTrue(array_key_exists('stats', $json['data']));
        $this->assertTrue(array_key_exists('updated_at', $json['data']));
    }
}
