<?php

namespace Tests\Feature;

use App\Group;
use App\Network;
use App\Party;
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
        $this->assertTrue(array_key_exists('default_language', $json));
    }

    /**
     * @dataProvider providerTrueFalse
     * @param $value
     */
    public function testListGroups($getNextEvent) {
        $network = factory(Network::class)->create([
                                                       'name' => 'Restart',
                                                       'events_push_to_wordpress' => true,
                                                   ]);
        $group = factory(Group::class)->create();
        $network->addGroup($group);

        // Create event for group
        $event = factory(Party::class)->states('moderated')->create([
                                                                         'event_start_utc' => '2038-01-01T00:00:00Z',
                                                                         'event_end_utc' => '2038-01-01T02:00:00Z',
                                                                         'group' => $group->idgroups,
                                                                     ]);

        // List networks.
        $response = $this->get("/api/v2/networks/{$network->id}/groups?" . ($getNextEvent ? 'includeNextEvent=true' : ''));

        try {
            $response->assertSuccessful();
            $json = json_decode($response->getContent(), true)['data'];
            $this->assertEquals(1, count($json));
            $this->assertEquals($group->idgroups, $json[0]['id']);

            if ($getNextEvent) {
                $this->assertEquals($event->idevents, $json[0]['next_event']['id']);
            } else {
                $this->assertFalse(array_key_exists('next_event', $json[0]));
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function providerTrueFalse() {
        return [
            [false],
            [true],
        ];
    }
}