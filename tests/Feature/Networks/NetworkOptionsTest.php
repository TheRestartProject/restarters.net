<?php

namespace Tests\Feature\Networks;

use App\Group;
use App\Network;
use App\User;
use Tests\TestCase;

/**
 * A network's options for its groups (RES-2063).
 */
class NetworkOptionsTest extends TestCase
{
    private Network $network;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->network = Network::factory()->create(['name' => 'Repair Together']);
    }

    private function patchOptions(array $body)
    {
        $token = auth()->user()?->api_token;

        return $this->patchJson("/api/v2/networks/{$this->network->id}/options" . ($token ? "?api_token=$token" : ''), $body);
    }

    /** @story:App\Http\Controllers\API\NetworkController::updateNetworkOptionsv2 */
    public function test_a_coordinator_sets_options_for_the_network_groups(): void
    {
        $coordinator = User::factory()->networkCoordinator()->create();
        $this->network->addCoordinator($coordinator);
        $group = Group::factory()->create(['features' => ['item_weight' => true]]);
        $this->network->addGroup($group);
        $this->actingAs($coordinator);

        $response = $this->patchOptions(['features' => ['item_weight' => 'off', 'custom_fields' => 'default_on']]);

        $response->assertOk();
        $this->assertEquals(['item_weight' => 'off', 'custom_fields' => 'default_on'], $response->json('data.features'));
        $this->assertEquals(['item_weight' => true, 'custom_fields' => false], $response->json('data.feature_defaults'));

        $groupFeatures = $this->getJson("/api/v2/groups/{$group->idgroups}")->json('data.features');
        $this->assertFalse($groupFeatures['item_weight']['enabled']);
        $this->assertTrue($groupFeatures['item_weight']['locked']);
        $this->assertEquals(['Repair Together'], $groupFeatures['item_weight']['set_by']);
        $this->assertTrue($groupFeatures['custom_fields']['enabled']);
    }

    /** @story:App\Http\Controllers\API\NetworkController::updateNetworkOptionsv2 */
    public function test_null_clears_a_network_setting(): void
    {
        $this->network->update(['features' => ['item_weight' => 'off']]);
        $this->actingAs(User::factory()->administrator()->create());

        $response = $this->patchOptions(['features' => ['item_weight' => null]]);

        $response->assertOk();
        $this->assertNull($response->json('data.features.item_weight'));
        $this->assertEquals([], $this->network->fresh()->features);
    }

    /** @story:App\Http\Controllers\API\NetworkController::updateNetworkOptionsv2 */
    public function test_unknown_features_and_values_are_rejected(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        $this->patchOptions(['features' => ['photos' => 'on']])->assertStatus(422);
        $this->patchOptions(['features' => 'abc'])->assertStatus(422);
        $this->patchOptions(['features' => ['item_weight' => true]])->assertStatus(422);
        $this->patchOptions(['features' => ['item_weight' => 'maybe']])->assertStatus(422);
    }

    /** @story:App\Http\Controllers\API\NetworkController::updateNetworkOptionsv2 */
    public function test_only_admins_and_this_network_coordinators_change_options(): void
    {
        $this->actingAs(User::factory()->restarter()->create());
        $this->patchOptions(['features' => ['item_weight' => 'off']])->assertStatus(403);

        $other = User::factory()->networkCoordinator()->create();
        Network::factory()->create()->addCoordinator($other);
        $this->actingAs($other);
        $this->patchOptions(['features' => ['item_weight' => 'off']])->assertStatus(403);
    }

    public function test_the_network_resource_shows_the_options(): void
    {
        $this->network->update(['features' => ['custom_fields' => 'default_off']]);

        $data = $this->getJson("/api/v2/networks/{$this->network->id}")->json('data');

        $this->assertEquals(['item_weight' => null, 'custom_fields' => 'default_off'], $data['features']);
        $this->assertEquals(['item_weight' => true, 'custom_fields' => false], $data['feature_defaults']);
    }
}
