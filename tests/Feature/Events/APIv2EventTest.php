<?php

namespace Tests\Feature;

use App\Device;
use App\Group;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use function PHPUnit\Framework\assertEquals;

class APIv2EventTest extends TestCase
{
    public function testGetEventsForGroup() {
        $user = User::factory()->administrator()->create([
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
        $this->assertFalse(array_key_exists('description', $json['data'][0]));  // Summary - this isn't present.

        // Get past.
        $response = $this->get("/api/v2/groups/$idgroups/events?end="  . urlencode(Carbon::now()->toIso8601String()));
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($json['data']));
        $this->assertEquals($id2, $json['data'][0]['id']);

        // Get individual event.
        $response = $this->get("/api/v2/events/$id1");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($id1, $json['data']['id']);
        $this->assertTrue(array_key_exists('description', $json['data']));
        $this->assertTrue(array_key_exists('stats', $json['data']));
        $this->assertTrue(array_key_exists('updated_at', $json['data']));

        // Check unapproved.  Only the event for tomorrow will show as we do not show past events for moderation.
        $response = $this->get("/api/v2/moderate/events?api_token=1234");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        self::assertEquals(1, count($json));
        self::assertEquals($id1, $json[0]['id']);
        self::assertFalse($json[0]['approved']);
    }

    public function testGetEventsForUnapprovedGroup() {
        $user = User::factory()->administrator()->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup('Test Group', 'https://therestartproject.org', 'London', 'Some text.', true, false);
        $idevents = $this->createEvent($idgroups, 'tomorrow');

        $party = Party::find($idevents);
        $party->approved = true;
        $party->save();

        $network = Network::factory()->create();
        $network->addGroup(Group::find($idgroups));

        // Should not show in list of events.
        $response = $this->get("/api/v2/groups/$idgroups/events");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals([], $json['data']);

        // Nor show up in the list of events for the network.
        $response = $this->get("/api/v2/networks/{$network->id}/events");
        $json = json_decode($response->getContent(), true);
        self::assertEquals(0, count($json['data']));

        // But we should be able to fetch individually.
        $response = $this->get("/api/v2/events/$idevents");
        $response->assertSuccessful();
    }

    public function testMaxUpdatedAt() {
        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup('Test Group', 'https://therestartproject.org', 'London', 'Some text.', true, true);
        $network = Network::factory()->create();
        $network->addGroup(Group::find($idgroups));
        $network->addCoordinator($user);

        $idevents = $this->createEvent($idgroups, 'yesterday');
        $device = Device::factory()->fixed()->create([
            'category' => 111,
            'category_creation' => 111,
            'event' => $idevents,
        ]);

        // Sleep for a second so that the updated_at time on the event and group should not be the current time.
        sleep(1);
        $now = Carbon::now()->toIso8601String();

        $response = $this->get("/api/v2/events/$idevents");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $eventUpdated = $json['data']['updated_at'];
        $groupUpdated = $json['data']['group']['updated_at'];

        // API v2 dates are ISO strings so we can just string compare.
        self::assertTrue($eventUpdated == $groupUpdated);
        self::assertFalse($eventUpdated == $now);

        $response = $this->get("/api/events/network?api_token=1234");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        // API v1 dates aren't as clear so format them for comparison.
        $eventUpdated = (new Carbon($json[0]['updated_at']))->format('Y-m-d H:i:s');
        $nowF = (new Carbon($now))->format('Y-m-d H:i:s');
        self::assertFalse($eventUpdated == $nowF);
    }

    /**
     * @param $role
     * @return void
     * @dataProvider roleProvider
     */
    public function testCreateLoggedOutUsingKey($role) {
        switch ($role) {
            case 'Administrator': $user = User::factory()->administrator()->create(); break;
            case 'NetworkCoordinator': $user = User::factory()->networkCoordinator()->create(); break;
            case 'Host': $user = User::factory()->host()->create(); break;
        }

        $response = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
            'name' => "Test Group",
            'website' => 'https://therestartproject.org',
            'location' => "London",
            'description' => "Some text",
            'timezone' => 'Europe/London',
            'network_data' => [
                'dummy' => 'dummy',
            ]
        ]);

        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
        $idgroups = $json['id'];

        $eventAttributes = Party::factory()->raw();
        $eventAttributes['group'] = $idgroups;

        $event_start = Carbon::createFromTimestamp(strtotime('tomorrow'))->setTimezone('UTC');
        $event_end = Carbon::createFromTimestamp(strtotime('tomorrow'))->setTimezone('UTC')->addHour(2);

        $response = $this->post('/api/v2/events?api_token=' . $user->api_token, [
            'groupid' => $idgroups,
            'start' => $event_start->toIso8601String(),
            'end' => $event_end->toIso8601String(),
            'title' => $eventAttributes['venue'],
            'location' => $eventAttributes['location'],
            'description' => $eventAttributes['free_text'],
            'timezone' => $eventAttributes['timezone']
        ]);

        self::assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        self::assertTrue(array_key_exists('id', $json));
        $idevents = $json['id'];
        self::assertNotNull($idevents);
    }

    public function roleProvider() {
        return [
            [ 'Administrator' ],
            [ 'NetworkCoordinator' ],
            [ 'Host' ],
        ];
    }

    public function testCreateEventGeocodeFailure()
    {
        $user = User::factory()->host()->create();

        $response = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
            'name' => "Test Group",
            'website' => 'https://therestartproject.org',
            'location' => "London",
            'description' => "Some text",
            'timezone' => 'Europe/London',
            'network_data' => [
                'dummy' => 'dummy',
            ]
        ]);

        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
        $idgroups = $json['id'];

        $eventAttributes = Party::factory()->raw();
        $eventAttributes['group'] = $idgroups;

        $event_start = Carbon::createFromTimestamp(strtotime('tomorrow'))->setTimezone('UTC');
        $event_end = Carbon::createFromTimestamp(strtotime('tomorrow'))->setTimezone('UTC')->addHour(2);

        $this->expectException(ValidationException::class);

        $response = $this->post('/api/v2/events?api_token=' . $user->api_token, [
            'groupid' => $idgroups,
            'start' => $event_start->toIso8601String(),
            'end' => $event_end->toIso8601String(),
            'title' => $eventAttributes['venue'],
            'location' => 'ForceGeoCodeFailure',
            'description' => $eventAttributes['free_text'],
            'timezone' => $eventAttributes['timezone']
        ]);
    }

    public function testCreateEventInvalidTimezone()
    {
        $user = User::factory()->host()->create();

        $response = $this->post('/api/v2/groups?api_token=' . $user->api_token, [
            'name' => "Test Group",
            'website' => 'https://therestartproject.org',
            'location' => "London",
            'description' => "Some text",
            'timezone' => 'Europe/London',
            'network_data' => [
                'dummy' => 'dummy',
            ]
        ]);

        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
        $idgroups = $json['id'];

        $eventAttributes = Party::factory()->raw();
        $eventAttributes['group'] = $idgroups;

        $event_start = Carbon::createFromTimestamp(strtotime('tomorrow'))->setTimezone('UTC');
        $event_end = Carbon::createFromTimestamp(strtotime('tomorrow'))->setTimezone('UTC')->addHour(2);

        $this->expectException(ValidationException::class);

        $response = $this->post('/api/v2/events?api_token=' . $user->api_token, [
            'groupid' => $idgroups,
            'start' => $event_start->toIso8601String(),
            'end' => $event_end->toIso8601String(),
            'title' => $eventAttributes['venue'],
            'location' => 'London',
            'description' => $eventAttributes['free_text'],
            'timezone' => 'invalidtimezone'
        ]);
    }

    public function testEmptyNetworkData() {
        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup();
        $idevents = $this->createEvent($idgroups, 'tomorrow');

        $party = Party::findOrFail($idevents);
        $party->network_data = [];
        $party->save();

        $response = $this->get("/api/v2/events/$idevents");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        assertEquals(null, $json['data']['network_data']);
    }
}
