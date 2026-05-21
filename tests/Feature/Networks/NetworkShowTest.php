<?php

namespace Tests\Feature\Networks;

use App\Group;
use App\GroupTags;
use App\Network;
use App\User;
use Tests\TestCase;

class NetworkShowTest extends TestCase
{
    /** @test */
    public function network_coordinator_sees_networkpage_with_can_manage_tags(): void
    {
        $network = Network::factory()->create();

        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $coordinator->refresh();

        $tag = GroupTags::factory()->create([
            'network_id' => $network->id,
        ]);

        $this->actingAs($coordinator);

        $response = $this->get('/networks/' . $network->id);
        $response->assertSuccessful();

        $props = $this->assertVueProperties($response, [
            [
                ':can-manage-tags' => 'true',
            ],
        ]);

        // Available tags should include the network's tag.
        $initialTags = json_decode($props[0][':initial-tags'], true);
        $tagIds = array_column($initialTags, 'id');
        $this->assertContains($tag->id, $tagIds);
    }

    /** @test */
    public function admin_sees_networkpage_with_can_manage_tags(): void
    {
        $network = Network::factory()->create();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->get('/networks/' . $network->id);
        $response->assertSuccessful();

        $this->assertVueProperties($response, [
            [
                ':can-manage-tags' => 'true',
            ],
        ]);
    }

    /** @test */
    public function map_bounds_passed_to_networkpage(): void
    {
        $network = Network::factory()->create();

        $group1 = Group::factory()->create([
            'latitude' => 51.5,
            'longitude' => -0.1,
        ]);
        $group2 = Group::factory()->create([
            'latitude' => 52.0,
            'longitude' => 0.5,
        ]);

        $network->addGroup($group1);
        $network->addGroup($group2);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $response = $this->get('/networks/' . $network->id);
        $response->assertSuccessful();

        $props = $this->assertVueProperties($response, [
            [':can-manage-tags' => 'true'],
        ]);

        $bounds = json_decode($props[0][':map-bounds'], true);
        $this->assertEquals(51.5, $bounds[0][0]);
        $this->assertEquals(-0.1, $bounds[0][1]);
        $this->assertEquals(52.0, $bounds[1][0]);
        $this->assertEquals(0.5, $bounds[1][1]);
    }
}
