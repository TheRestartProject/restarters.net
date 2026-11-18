<?php

namespace Tests\Feature\Groups;

use App\Category;
use App\Device;
use App\Party;
use App\User;
use Tests\TestCase;

class APIv2GroupStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testUnknownGroupReturns404(): void
    {
        $response = $this->get('/api/v2/groups/999999/stats');

        $response->assertStatus(404);
    }

    public function testDoesNotRequireAuthentication(): void
    {
        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $idgroups = $this->createGroup();
        \Auth::logout();

        $response = $this->get("/api/v2/groups/$idgroups/stats");

        $response->assertSuccessful();
    }

    public function testStatsShapeForGroupWithNoActivity(): void
    {
        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $idgroups = $this->createGroup();

        $response = $this->get("/api/v2/groups/$idgroups/stats");
        $response->assertSuccessful();

        $json = $response->json('data');

        $this->assertArrayHasKey('group_stats', $json);
        $this->assertEquals(0, $json['group_stats']['parties']);

        $this->assertEquals(['fixed' => 0, 'repairable' => 0, 'dead' => 0], $json['device_stats']);
        $this->assertEquals([], $json['top_devices']);

        foreach ([1, 2, 3, 4] as $cluster) {
            $this->assertEquals(0, $json['cluster_stats'][$cluster]['total']);
            $this->assertNull($json['cluster_stats'][$cluster]['most_seen']['name']);
            $this->assertEquals(0, $json['cluster_stats'][$cluster]['most_seen']['count']);
        }
    }

    public function testStatsReflectDevicesOnPastEvent(): void
    {
        // desktopComputer (idcategories 11, cluster 1, powered) is already seeded
        // by TestCase::setUp() - the only combination findMostSeen()/
        // countByClustersYearStatus() will actually surface.

        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $idgroups = $this->createGroup();

        // Must be >= 2013-01-01 (countByClustersYearStatus's floor) and in the past
        // (statusCount()/Party::past() both require this).
        $event = Party::factory()->moderated()->create([
            'group' => $idgroups,
            'event_start_utc' => '2021-06-01T10:00:00+00:00',
            'event_end_utc' => '2021-06-01T13:00:00+00:00',
        ]);

        $this->createDevice($event->idevents, 'misc', null, 1.5, 0, '', Device::REPAIR_STATUS_FIXED_STR, null, null, 11);
        $this->createDevice($event->idevents, 'misc', null, 1.5, 0, '', Device::REPAIR_STATUS_REPAIRABLE_STR, null, null, 11);

        $response = $this->get("/api/v2/groups/$idgroups/stats");
        $response->assertSuccessful();

        $json = $response->json('data');

        $this->assertEquals(1, $json['device_stats']['fixed']);
        $this->assertEquals(1, $json['device_stats']['repairable']);
        $this->assertEquals(0, $json['device_stats']['dead']);

        $this->assertEquals(2, $json['cluster_stats'][1]['total']);
        $this->assertEquals(1, $json['cluster_stats'][1]['fixed']);
        $this->assertEquals(1, $json['cluster_stats'][1]['repairable']);
        $this->assertEquals('Desktop computer', $json['cluster_stats'][1]['most_seen']['name']);
        $this->assertEquals(2, $json['cluster_stats'][1]['most_seen']['count']);
        $this->assertEquals('Desktop computer', $json['cluster_stats'][1]['most_repaired']['name']);
        $this->assertEquals(1, $json['cluster_stats'][1]['most_repaired']['count']);

        // Cluster 2/3/4 untouched.
        $this->assertEquals(0, $json['cluster_stats'][2]['total']);

        $this->assertNotEmpty($json['top_devices']);
        $this->assertEquals('Desktop computer', $json['top_devices'][0]['name']);
        $this->assertEquals(1, $json['top_devices'][0]['counter']);

        $this->assertEquals(1, $json['group_stats']['parties']);
    }
}
