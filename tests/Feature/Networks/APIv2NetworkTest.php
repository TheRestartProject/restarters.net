<?php

namespace Tests\Feature;

use App\Group;
use App\GroupTags;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use Carbon\Carbon;
use DB;
use http\Client\Request;
use Tests\TestCase;

class APIv2NetworkTest extends TestCase
{
    public function testList(): void {
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

    public function testGet(): void {
        $network = Network::first();
        self::assertNotNull($network);

        // Ensure we have a logo to test retrieval.  We store them in a network_logos subfolder.
        $network->logo = 'network_logos/1590591632bedc48025b738e87fe674cf030e8c953ccdd91e914597.png';
        $network->save();

        $response = $this->get('/api/v2/networks/' . $network->id);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals($network->id, $json['id']);
        $this->assertEquals($network->name, $json['name']);
        $this->assertEquals($network->description, $json['description']);
        $this->assertEquals($network->website, $json['website']);
        $this->assertStringEndsWith('/uploads/' . $network->logo, $json['logo']);
        $this->assertTrue(array_key_exists('stats', $json));
        $this->assertTrue(array_key_exists('default_language', $json));

        $response = $this->get('/api/v2/networks');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];

        foreach ($json as $n) {
            if ($n['id'] == $network->id) {
                $this->assertStringEndsWith('/uploads/' . $network->logo, $n['logo']);
                break;
            }
        }
    }

    /**
     * @dataProvider providerGroupsParameters
     * @param $value
     */
    public function testListGroups($getNextEvent, $getDetails): void {
        $network = Network::factory()->create([
                                                       'name' => 'Restart',
                                                       'events_push_to_wordpress' => true,
                                                   ]);
        $group = Group::factory()->create([
                                                   'location' => 'London',
                                                   'area' => 'London',
                                                   'country_code' => 'GB',
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
        $this->assertEquals($group->country_code, $location['country_code']);
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

    public function providerGroupsParameters(): array {
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
    public function testListEvents($getDetails): void {
        $network = Network::factory()->create([
                                                       'name' => 'Restart',
                                                       'events_push_to_wordpress' => true,
                                                   ]);
        $group = Group::factory()->create([
                                                   'approved' => true,
                                               ]);
        $network->addGroup($group);

        // Create event for group
        $event = Party::factory()->moderated()->create([
                                                                        'event_start_utc' => '2038-01-01T00:00:00Z',
                                                                        'event_end_utc' => '2038-01-01T02:00:00Z',
                                                                        'group' => $group->idgroups,
                                                                    ]);

        // Manually set the updated_at fields so that we can check they are returned correctly.
        DB::statement("UPDATE events SET updated_at = '2011-01-01 12:34'");
        DB::statement("UPDATE `groups` SET updated_at = '2011-01-02 12:34'");

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

    public function providerEventsParameters(): array {
        return [
            [false],
            [true],
        ];
    }

    public function testListNetworkTags(): void {
        $network = Network::factory()->create();

        // Create a global tag
        $globalTag = GroupTags::factory()->create([
            'tag_name' => 'GlobalTag',
            'network_id' => null,
        ]);

        // Create a tag for this network
        $networkTag = GroupTags::factory()->create([
            'tag_name' => 'NetworkTag',
            'network_id' => $network->id,
        ]);

        // Create a tag for another network
        $otherNetwork = Network::factory()->create();
        $otherTag = GroupTags::factory()->create([
            'tag_name' => 'OtherNetworkTag',
            'network_id' => $otherNetwork->id,
        ]);

        // Unauthenticated: should see no tags at all
        $response = $this->get("/api/v2/networks/{$network->id}/tags");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEmpty($json);

        // Admin: should see global + network-specific tags
        $admin = User::factory()->administrator()->create(['api_token' => 'admintoken123']);
        $this->actingAs($admin);
        $response = $this->get("/api/v2/networks/{$network->id}/tags?api_token=admintoken123");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $tagIds = array_column($json, 'id');
        $this->assertContains($globalTag->id, $tagIds); // Admin can see global tags
        $this->assertContains($networkTag->id, $tagIds);
        $this->assertNotContains($otherTag->id, $tagIds);

        // Test network_only parameter (even for admin, should exclude global)
        $response = $this->get("/api/v2/networks/{$network->id}/tags?network_only=true&api_token=admintoken123");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $tagIds = array_column($json, 'id');
        $this->assertNotContains($globalTag->id, $tagIds);
        $this->assertContains($networkTag->id, $tagIds);
    }

    public function testCreateNetworkTagAsCoordinator(): void {
        $network = Network::factory()->create();

        $user = User::factory()->networkCoordinator()->create([
            'api_token' => '1234',
        ]);
        $network->addCoordinator($user);

        $this->actingAs($user);

        $response = $this->postJson("/api/v2/networks/{$network->id}/tags?api_token=1234", [
            'name' => 'TestTag',
            'description' => 'A test tag',
        ]);

        $response->assertStatus(201);
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals('TestTag', $json['name']);
        $this->assertEquals($network->id, $json['network_id']);

        // Verify tag was created in database
        $tag = GroupTags::find($json['id']);
        $this->assertNotNull($tag);
        $this->assertEquals($network->id, $tag->network_id);
    }

    public function testCreateNetworkTagAsAdmin(): void {
        $network = Network::factory()->create();

        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);

        $this->actingAs($user);

        $response = $this->postJson("/api/v2/networks/{$network->id}/tags?api_token=1234", [
            'name' => 'AdminTag',
            'description' => 'An admin-created tag',
        ]);

        $response->assertStatus(201);
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals('AdminTag', $json['name']);
    }

    public function testCreateNetworkTagUnauthorized(): void {
        $network = Network::factory()->create();

        // User who is not a coordinator for this network
        $user = User::factory()->restarter()->create([
            'api_token' => '1234',
        ]);

        $this->actingAs($user);

        $response = $this->postJson("/api/v2/networks/{$network->id}/tags?api_token=1234", [
            'name' => 'UnauthorizedTag',
        ]);

        $response->assertStatus(403);
    }

    public function testCreateDuplicateNetworkTag(): void {
        $network = Network::factory()->create();

        // Create existing tag
        GroupTags::factory()->create([
            'tag_name' => 'ExistingTag',
            'network_id' => $network->id,
        ]);

        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);

        $this->actingAs($user);

        $response = $this->postJson("/api/v2/networks/{$network->id}/tags?api_token=1234", [
            'name' => 'ExistingTag',
        ]);

        $response->assertStatus(422);
    }

    public function testDeleteNetworkTag(): void {
        $network = Network::factory()->create();

        $tag = GroupTags::factory()->create([
            'tag_name' => 'ToDelete',
            'network_id' => $network->id,
        ]);

        $user = User::factory()->networkCoordinator()->create([
            'api_token' => '1234',
        ]);
        $network->addCoordinator($user);

        $this->actingAs($user);

        $response = $this->delete("/api/v2/networks/{$network->id}/tags/{$tag->id}?api_token=1234");

        $response->assertSuccessful();

        // Verify tag was deleted
        $this->assertNull(GroupTags::find($tag->id));
    }

    public function testDeleteTagFromWrongNetwork(): void {
        $network1 = Network::factory()->create();
        $network2 = Network::factory()->create();

        $tag = GroupTags::factory()->create([
            'tag_name' => 'Network2Tag',
            'network_id' => $network2->id,
        ]);

        $user = User::factory()->networkCoordinator()->create([
            'api_token' => '1234',
        ]);
        $network1->addCoordinator($user);

        $this->actingAs($user);

        // Try to delete tag from network1 but tag belongs to network2
        $response = $this->delete("/api/v2/networks/{$network1->id}/tags/{$tag->id}?api_token=1234");

        $response->assertStatus(403);

        // Verify tag was NOT deleted
        $this->assertNotNull(GroupTags::find($tag->id));
    }

    public function testListGroupsFilterByTag(): void {
        $network = Network::factory()->create();

        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();
        $network->addGroup($group1);
        $network->addGroup($group2);

        $tag = GroupTags::factory()->create([
            'tag_name' => 'FilterTag',
            'network_id' => $network->id,
        ]);

        // Add tag only to group1
        $group1->group_tags()->attach($tag->id);

        // Without filter - should return both groups
        $response = $this->get("/api/v2/networks/{$network->id}/groups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(2, count($json));

        // With tag filter - should return only group1
        $response = $this->get("/api/v2/networks/{$network->id}/groups?tag={$tag->id}");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(1, count($json));
        $this->assertEquals($group1->idgroups, $json[0]['id']);
    }

    public function testListEventsFilterByTag(): void {
        $network = Network::factory()->create();

        $group1 = Group::factory()->create(['approved' => true]);
        $group2 = Group::factory()->create(['approved' => true]);
        $network->addGroup($group1);
        $network->addGroup($group2);

        $event1 = Party::factory()->moderated()->create([
            'group' => $group1->idgroups,
            'event_start_utc' => '2038-01-01T00:00:00Z',
            'event_end_utc' => '2038-01-01T02:00:00Z',
        ]);
        $event2 = Party::factory()->moderated()->create([
            'group' => $group2->idgroups,
            'event_start_utc' => '2038-01-02T00:00:00Z',
            'event_end_utc' => '2038-01-02T02:00:00Z',
        ]);

        $tag = GroupTags::factory()->create([
            'tag_name' => 'EventFilterTag',
            'network_id' => $network->id,
        ]);

        // Add tag only to group1
        $group1->group_tags()->attach($tag->id);

        // Without filter - should return both events
        $response = $this->get("/api/v2/networks/{$network->id}/events");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(2, count($json));

        // With tag filter - should return only event1
        $response = $this->get("/api/v2/networks/{$network->id}/events?tag={$tag->id}");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];
        $this->assertEquals(1, count($json));
        $this->assertEquals($event1->idevents, $json[0]['id']);
    }

    public function testNetworkCoordinatorCanUpdateGroupTags(): void {
        $network = Network::factory()->create();

        $group = Group::factory()->create(['approved' => true]);
        $network->addGroup($group);

        // Create tags
        $globalTag = GroupTags::factory()->create([
            'tag_name' => 'GlobalTag',
            'network_id' => null,
        ]);
        $networkTag = GroupTags::factory()->create([
            'tag_name' => 'NetworkTag',
            'network_id' => $network->id,
        ]);
        $otherNetworkTag = GroupTags::factory()->create([
            'tag_name' => 'OtherNetworkTag',
            'network_id' => Network::factory()->create()->id,
        ]);

        $user = User::factory()->networkCoordinator()->create([
            'api_token' => '1234',
        ]);
        $network->addCoordinator($user);

        $this->actingAs($user);

        // NC can only add tags from their own networks (NOT global, NOT other networks)
        $response = $this->patch("/api/v2/groups/{$group->idgroups}?api_token=1234", [
            'tags' => json_encode([$globalTag->id, $networkTag->id, $otherNetworkTag->id]),
        ]);

        $response->assertSuccessful();

        // Refresh group and check tags
        $group->refresh();
        $tagIds = $group->group_tags->pluck('id')->toArray();

        // Should have ONLY network tags (global tags are admin-only)
        $this->assertNotContains($globalTag->id, $tagIds); // Global tags are admin-only
        $this->assertContains($networkTag->id, $tagIds); // Own network's tag - allowed
        $this->assertNotContains($otherNetworkTag->id, $tagIds); // Other network's tag - not allowed
    }

    public function testTagResourceIncludesNetworkId(): void {
        $network = Network::factory()->create();

        $networkTag = GroupTags::factory()->create([
            'tag_name' => 'NetworkSpecificTag',
            'network_id' => $network->id,
        ]);

        $globalTag = GroupTags::factory()->create([
            'tag_name' => 'GlobalTag2',
            'network_id' => null,
        ]);

        $response = $this->get("/api/v2/networks/{$network->id}/tags");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];

        foreach ($json as $tag) {
            if ($tag['id'] == $networkTag->id) {
                $this->assertEquals($network->id, $tag['network_id']);
            }
            if ($tag['id'] == $globalTag->id) {
                $this->assertNull($tag['network_id']);
            }
        }
    }

    /**
     * Test that NC can only apply tags from networks where BOTH:
     * 1. The NC is a coordinator, AND
     * 2. The group belongs to that network
     *
     * This tests the "tag visibility intersection" requirement.
     */
    public function testNCCanOnlyApplyTagsFromIntersectionOfCoordinatedAndGroupNetworks(): void {
        // Create two networks
        $network1 = Network::factory()->create(['name' => 'Network1']);
        $network2 = Network::factory()->create(['name' => 'Network2']);

        // Create tags for each network
        $tag1 = GroupTags::factory()->create([
            'tag_name' => 'Network1Tag',
            'network_id' => $network1->id,
        ]);
        $tag2 = GroupTags::factory()->create([
            'tag_name' => 'Network2Tag',
            'network_id' => $network2->id,
        ]);

        // Create a group that belongs to Network1 ONLY
        $group = Group::factory()->create();
        $network1->addGroup($group);
        // Group is NOT in Network2

        // User coordinates BOTH networks
        $user = User::factory()->create(['api_token' => '1234']);
        $network1->addCoordinator($user);
        $network2->addCoordinator($user);

        $this->actingAs($user);

        // Try to add both tags
        $response = $this->patch("/api/v2/groups/{$group->idgroups}?api_token=1234", [
            'tags' => json_encode([$tag1->id, $tag2->id]),
        ]);

        $response->assertSuccessful();

        // Refresh group and check tags
        $group->refresh();
        $tagIds = $group->group_tags->pluck('id')->toArray();

        // Should ONLY have tag1 (from Network1 which group belongs to)
        // Should NOT have tag2 (even though NC coordinates Network2, group is not in it)
        $this->assertContains($tag1->id, $tagIds);
        $this->assertNotContains($tag2->id, $tagIds);
    }

    /**
     * Test that when NC updates a group's tags, tags from networks they don't coordinate are preserved.
     */
    public function testNCPreservesTagsFromOtherNetworksWhenUpdating(): void {
        // Create two networks
        $network1 = Network::factory()->create(['name' => 'CoordinatedNetwork']);
        $network2 = Network::factory()->create(['name' => 'OtherNetwork']);

        // Create tags for each network
        $tag1 = GroupTags::factory()->create([
            'tag_name' => 'CoordinatedTag',
            'network_id' => $network1->id,
        ]);
        $tag2 = GroupTags::factory()->create([
            'tag_name' => 'OtherTag',
            'network_id' => $network2->id,
        ]);
        $globalTag = GroupTags::factory()->create([
            'tag_name' => 'GlobalTag',
            'network_id' => null,
        ]);

        // Create a group that belongs to BOTH networks
        $group = Group::factory()->create();
        $network1->addGroup($group);
        $network2->addGroup($group);

        // Add pre-existing tags from network2 and global
        $group->group_tags()->attach([$tag2->id, $globalTag->id]);

        // User coordinates only Network1
        $user = User::factory()->create(['api_token' => '1234']);
        $network1->addCoordinator($user);

        $this->actingAs($user);

        // NC adds tag1 (their network's tag)
        $response = $this->patch("/api/v2/groups/{$group->idgroups}?api_token=1234", [
            'tags' => json_encode([$tag1->id]),
        ]);

        $response->assertSuccessful();

        // Refresh group and check tags
        $group->refresh();
        $tagIds = $group->group_tags->pluck('id')->toArray();

        // Should have:
        // - tag1: NC added from their network
        // - tag2: preserved from network2 (NC doesn't coordinate)
        // - globalTag: preserved (NC cannot see/edit global tags)
        $this->assertContains($tag1->id, $tagIds); // Added by NC
        $this->assertContains($tag2->id, $tagIds); // Preserved from other network
        $this->assertContains($globalTag->id, $tagIds); // Preserved global tag
    }

    /**
     * Test that tag resource includes groups_count field.
     */
    public function testTagResourceIncludesGroupsCount(): void {
        $network = Network::factory()->create();

        // Create tag
        $tag = GroupTags::factory()->create([
            'tag_name' => 'TagWithGroups',
            'network_id' => $network->id,
        ]);

        // Create groups and attach tag to some of them
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();
        $group3 = Group::factory()->create();
        $network->addGroup($group1);
        $network->addGroup($group2);
        $network->addGroup($group3);

        $group1->group_tags()->attach($tag->id);
        $group2->group_tags()->attach($tag->id);
        // group3 does NOT have the tag

        $response = $this->get("/api/v2/networks/{$network->id}/tags");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];

        $foundTag = collect($json)->firstWhere('id', $tag->id);
        $this->assertNotNull($foundTag);
        $this->assertEquals(2, $foundTag['groups_count']);
    }

    /**
     * Test that unauthenticated API calls return no tags for network tags endpoint.
     */
    public function testUnauthenticatedNetworkTagsReturnsEmpty(): void {
        $network = Network::factory()->create();

        // Create some tags
        GroupTags::factory()->create([
            'tag_name' => 'NetworkTag',
            'network_id' => $network->id,
        ]);
        GroupTags::factory()->create([
            'tag_name' => 'GlobalTag',
            'network_id' => null,
        ]);

        // Make unauthenticated request
        $response = $this->get("/api/v2/networks/{$network->id}/tags");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];

        // Should return empty array
        $this->assertEmpty($json);
    }

    /**
     * Test that unauthenticated API calls return no tags for groups tags endpoint.
     */
    public function testUnauthenticatedGroupTagsReturnsEmpty(): void {
        // Create some tags
        $network = Network::factory()->create();
        GroupTags::factory()->create([
            'tag_name' => 'NetworkTag',
            'network_id' => $network->id,
        ]);
        GroupTags::factory()->create([
            'tag_name' => 'GlobalTag',
            'network_id' => null,
        ]);

        // Make unauthenticated request
        $response = $this->get("/api/v2/groups/tags");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true)['data'];

        // Should return empty array
        $this->assertEmpty($json);
    }
}