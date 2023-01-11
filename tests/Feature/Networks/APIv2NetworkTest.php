<?php

namespace Tests\Feature;

use App\Group;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
use DB;
use http\Client\Request;
use Tests\TestCase;

class APIv2NetworkTest extends TestCase
{
    public function testList() {
        $user = User::factory()->administrator()->create([
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

        // Ensure we have a logo to test retrieval.
        $network->logo = '1590591632bedc48025b738e87fe674cf030e8c953ccdd91e914597.png';
        $network->save();

        $response = $this->get('/api/v2/networks/' . $network->id);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals($network->id, $json['id']);
        $this->assertEquals($network->name, $json['name']);
        $this->assertEquals($network->description, $json['description']);
        $this->assertEquals($network->website, $json['website']);
        $this->assertStringEndsWith('/mid_' . $network->logo, $json['logo']);
        $this->assertTrue(array_key_exists('stats', $json));
        $this->assertTrue(array_key_exists('default_language', $json));
    }

    /**
     * @dataProvider providerGroupsParameters
     * @param $value
     */
    public function testListGroups($getNextEvent, $getDetails) {
        $network = Network::factory()->create([
                                                       'name' => 'Restart',
                                                       'events_push_to_wordpress' => true,
                                                   ]);
        $group = Group::factory()->create([
                                                   'location' => 'London',
                                                   'area' => 'London',
                                                   'country' => 'GB',
                                               ]);
        $network->addGroup($group);

        // Create event for group
        $event = Party::factory()->moderated()->create([
                                                                         'event_start_utc' => '2038-01-01T00:00:00Z',
                                                                         'event_end_utc' => '2038-01-01T02:00:00Z',
                                                                         'group' => $group->idgroups,
                                                                     ]);

        // List networks.
        $url = "/api/v2/networks/{$network->id}/groups?" .
                ($getNextEvent ? '&includeNextEvent=true' : '') .
                ($getDetails ? '&includeDetails=true' : '');
        $response = $this->get($url);

        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(1, count($json));
        $this->assertEquals($group->idgroups, $json[0]['id']);
        $this->assertEquals($group->name, $json[0]['name']);
        $this->assertTrue(array_key_exists('location', $json[0]));
        $location = $json[0]['location'];
        $this->assertEquals($group->location, $location['location']);
        $this->assertEquals($group->country, $location['country']);
        $this->assertEquals($group->area, $location['area']);
        $this->assertEquals($group->latitude, $location['lat']);
        $this->assertEquals($group->longitude, $location['lng']);

        if ($getDetails) {
            $this->assertNotNull($json[0]['description']);
            $this->assertEquals($event->idevents, $json[0]['next_event']['id']);
        } else {
            $this->assertFalse(array_key_exists('description', $json[0]));

            if ($getNextEvent) {
                $this->assertEquals($event->idevents, $json[0]['next_event']['id']);
            } else {
                $this->assertFalse(array_key_exists('next_event', $json[0]));
            }
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

    public function providerGroupsParameters() {
        return [
            [false, false],
            [false, true],
            [true, false],
            [true, true],
        ];
    }

    /**
     * @dataProvider providerEventsParameters
     * @param $value
     */
    public function testListEvents($getDetails) {
        $network = Network::factory()->create([
                                                       'name' => 'Restart',
                                                       'events_push_to_wordpress' => true,
                                                   ]);
        $group = Group::factory()->create([
                                                   'wordpress_post_id' => '99999',
                                               ]);
        $network->addGroup($group);

        // Create event for group
        $event = Party::factory()->moderated()->create([
                                                                        'event_start_utc' => '2038-01-01T00:00:00Z',
                                                                        'event_end_utc' => '2038-01-01T02:00:00Z',
                                                                        'group' => $group->idgroups,
                                                                    ]);

        // Manually set the updated_at fields so that we can check they are returned correctly.
        DB::statement(DB::raw("UPDATE events SET updated_at = '2011-01-01 12:34'"));
        DB::statement(DB::raw("UPDATE `groups` SET updated_at = '2011-01-02 12:34'"));

        $url = "/api/v2/networks/{$network->id}/events" .
            ($getDetails ? '?includeDetails=true' : '');
        $response = $this->get($url);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(1, count($json));
        $this->assertEquals($event->idevents, $json[0]['id']);
        $this->assertEquals('2011-01-01T12:34:00+00:00', $json[0]['updated_at']);
        $this->assertEquals('2011-01-02T12:34:00+00:00', $json[0]['group']['updated_at']);

        if ($getDetails) {
            $this->assertNotNull($json[0]['description']);
        } else {
            $this->assertFalse(array_key_exists('description', $json[0]));
        }

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

    public function providerEventsParameters() {
        return [
            [false],
            [true],
        ];
    }
}