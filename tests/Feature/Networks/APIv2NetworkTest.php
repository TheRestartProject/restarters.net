<?php

namespace Tests\Feature;

use App\Group;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
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

        // Test updated_at filters.
        $start = Carbon::now()->subDays(1)->toIso8601String();
        $end = Carbon::now()->addDays(1)->toIso8601String();
        $response = $this->get("/api/v2/networks/{$network->id}/groups?updated_start=" . urlencode($start) . "&updated_end=" . urlencode($end));
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(1, count($json));

        $start = Carbon::now()->addDays(1)->toIso8601String();
        $end = Carbon::now()->addDays(2)->toIso8601String();
        $response = $this->get("/api/v2/networks/{$network->id}/groups?updated_start=" . urlencode($start) . "&updated_end=" . urlencode($end));
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(0, count($json));
    }

    public function providerTrueFalse() {
        return [
            [false],
            [true],
        ];
    }

    public function testListEvents() {
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

        // Manually set the updated_at fields so that we can check they are returned correctly.
        DB::statement(DB::raw("UPDATE events SET updated_at = '2011-01-01 12:34'"));
        DB::statement(DB::raw("UPDATE `groups` SET updated_at = '2011-01-02 12:34'"));

        $response = $this->get("/api/v2/networks/{$network->id}/events");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(1, count($json));
        $this->assertEquals($event->idevents, $json[0]['id']);
        $this->assertEquals('2011-01-01T12:34:00+00:00', $json[0]['updated_at']);
        $this->assertEquals('2011-01-02T12:34:00+00:00', $json[0]['group']['updated_at']);

        # Test updated filters.
        $start = '2011-01-01T10:34:00+00:00';
        $end = '2011-01-01T14:34:00+00:00';
        $response = $this->get("/api/v2/networks/{$network->id}/events?updated_start=" . urlencode($start) . "&updated_end=" . urlencode($end));
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(1, count($json));

        $start = '2011-01-01T15:34:00+00:00';
        $end = '2011-01-01T16:34:00+00:00';
        $response = $this->get("/api/v2/networks/{$network->id}/events?updated_start=" . urlencode($start) . "&updated_end=" . urlencode($end));
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(0, count($json));
    }
}