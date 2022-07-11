<?php

namespace Tests\Feature;

use App\Network;
use App\User;
use DB;
use Tests\TestCase;

class APIv2NetworkTest extends TestCase
{
    public function testList() {
        $user = factory(User::class)->states('Administrator')->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        $this->actingAs($user);

        // List networks.
        $response = $this->get('/api/v2/networks');

        // Check that we can find a network in the list.
        $network = Network::first();
        self::assertNotNull($network);

        // decode the response.
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertGreaterThanOrEqual(1, count($json));

        // find network in response
        $found = false;

        foreach ($json as $n) {
            if ($n['id'] == $network->id) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found);
    }

    public function testGet() {
        $network = Network::first();
        self::assertNotNull($network);

        $response = $this->get('/api/v2/networks/' . $network->id);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals($network->id, $json['id']);
        $this->assertEquals($network->name, $json['name']);
        $this->assertEquals($network->description, $json['description']);
        $this->assertEquals($network->website, $json['website']);
        $this->assertTrue(array_key_exists('stats', $json));
    }

    // TODO List groups.
}