<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Group;
use App\Models\Network;
use App\Models\Party;
use App\Models\User;
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
    public function testGetEventsForGroup(): void {
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

        // Check unapproved.  Past events show for moderation, earliest first.
        $response = $this->get("/api/v2/moderate/events?api_token=1234");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        self::assertEquals(2, count($json));
        $this->assertEquals($id2, $json[0]['id']);
        $this->assertEquals($id1, $json[1]['id']);
        self::assertFalse($json[0]['approved']);
        self::assertFalse($json[1]['approved']);
    }

    public function testGetEventsForUnapprovedGroup(): void {
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

    public function testMaxUpdatedAt(): void {
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

        // Sleep for two seconds so that the updated_at time on the event and group should not be the current time.
        sleep(2);
        $now = Carbon::now()->toIso8601String();

        $response = $this->get("/api/v2/events/$idevents");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $eventUpdated = $json['data']['updated_at'];
        $groupUpdated = $json['data']['group']['updated_at'];

        // Both times should not be the time we started at.  They might be 1s different if we happened to hit
        // the second boundary (which we have seen on CircleCI).
        $this->assertNotEquals($now, $eventUpdated);
        $this->assertNotEquals($now, $groupUpdated);

        $eventUpdatedEpoch = (new Carbon($eventUpdated))->getTimestamp();
        $groupUpdatedEpoch = (new Carbon($groupUpdated))->getTimestamp();
        $this->assertTrue(abs($eventUpdatedEpoch - $groupUpdatedEpoch) <= 1);

        $response = $this->get("/api/events/network?api_token=1234");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        // API v1 dates aren't as clear so format them for comparison.
        $eventUpdatedEpoch = (new Carbon($json[0]['updated_at']))->getTimestamp();
        $this->assertTrue(abs($eventUpdatedEpoch - $groupUpdatedEpoch) <= 1);
        $this->assertNotEquals($now, (new Carbon($json[0]['updated_at']))->toIso8601String());
    }

    /**
     * @param $role
     * @dataProvider roleProvider
     */
    public function testCreateLoggedOutUsingKey($role): void {
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

    public function roleProvider(): array {
        return [
            [ 'Administrator' ],
            [ 'NetworkCoordinator' ],
            [ 'Host' ],
        ];
    }

    public function testEditForbidden(): void {
        $user1 = User::factory()->host()->create([
            'api_token' => '1234',
        ]);

        $this->actingAs($user1);

        $idgroups = $this->createGroup();
        $id1 = $this->createEvent($idgroups, 'tomorrow');

        $user2 = User::factory()->host()->create([
            'api_token' => '5678',
        ]);

        $this->actingAs($user2);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $eventData = Party::factory()->raw([
            'group' => $idgroups,
            'event_start_utc' => '2100-01-01T10:15:05+05:00',
            'event_end_utc' => '2100-01-0113:45:05+05:00',
            'latitude'=>'1',
            'longitude'=>'1'
        ]);

        $eventData['moderate'] = 'approve';
        $this->patch('/api/v2/events/'.$id1, $this->eventAttributesToAPI($eventData));
    }

    public function testCreateEventGeocodeFailure(): void
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

    public function testCreateEventInvalidTimezone(): void
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

    public function testEmptyNetworkData(): void {
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

    public function testNetworkCoordinatorCanApprove(): void {
        $network = Network::factory()->create();
        $group = Group::factory()->create();
        $network->addGroup($group);

        $event = Party::factory()->create(['group' => $group->idgroups]);
        $event->save();

        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $eventData = $event->getAttributes();
        $eventData['moderate'] = 'approve';
        $response = $this->patch('/api/v2/events/'.$event->idevents, $this->eventAttributesToAPI($eventData));
        $response->assertSuccessful();
    }
}
