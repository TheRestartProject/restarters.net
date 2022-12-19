<?php

namespace Tests\Feature;

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
        $user = factory(User::class)->states('Administrator')->create([
                                                                          'api_token' => '1234',
                                                                      ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup('Test Group', 'https://therestartproject.org', 'London', 'Some text.', true, false);
        $idevents = $this->createEvent($idgroups, 'tomorrow');

        $party = Party::find($idevents);
        $party->approved = true;
        $party->save();

        $network = factory(Network::class)->create();
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

        // Nor be able to fetch individually.
        $this->expectException(NotFoundHttpException::class);
        $response = $this->get("/api/v2/events/$idevents");

    }
}
