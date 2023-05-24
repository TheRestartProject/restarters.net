<?php

namespace Tests\Feature;

use App\Device;
use App\Group;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

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
}
