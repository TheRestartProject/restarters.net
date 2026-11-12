<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Network;
use App\User;
use Tests\TestCase;

/**
 * The group's Options: feature switches and custom item fields (RES-2063).
 */
class GroupOptionsTest extends TestCase
{
    private Group $group;
    private User $host;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->group = Group::factory()->create();
        $this->host = User::factory()->host()->create();
        $this->group->addVolunteer($this->host);
        $this->group->makeMemberAHost($this->host);
    }

    private function patchOptions(array $body)
    {
        $token = auth()->user()?->api_token;

        return $this->patchJson("/api/v2/groups/{$this->group->idgroups}/options" . ($token ? "?api_token=$token" : ''), $body);
    }

    /** @story:App\Http\Controllers\API\GroupController::updateGroupOptionsv2 */
    public function test_a_host_switches_features_for_their_group(): void
    {
        $this->actingAs($this->host);

        $response = $this->patchOptions(['features' => ['item_weight' => false, 'custom_fields' => true]]);

        $response->assertOk();
        $this->assertFalse($response->json('data.features.item_weight.enabled'));
        $this->assertEquals('group', $response->json('data.features.item_weight.source'));
        $this->assertTrue($response->json('data.features.custom_fields.enabled'));
        $this->assertEquals(['item_weight' => false, 'custom_fields' => true], $this->group->fresh()->features);
    }

    /** @story:App\Http\Controllers\API\GroupController::updateGroupOptionsv2 */
    public function test_null_goes_back_to_the_default(): void
    {
        $this->group->update(['features' => ['item_weight' => false]]);
        $this->actingAs($this->host);

        $response = $this->patchOptions(['features' => ['item_weight' => null]]);

        $response->assertOk();
        $this->assertTrue($response->json('data.features.item_weight.enabled'));
        $this->assertEquals('default', $response->json('data.features.item_weight.source'));
        $this->assertEquals([], $this->group->fresh()->features);
    }

    /** @story:App\Http\Controllers\API\GroupController::updateGroupOptionsv2 */
    public function test_unknown_features_and_values_are_rejected(): void
    {
        $this->actingAs($this->host);

        $this->patchOptions(['features' => ['photos' => true]])->assertStatus(422);
        $this->patchOptions(['features' => 'abc'])->assertStatus(422);
        $this->patchOptions(['features' => ['item_weight' => 'sometimes']])->assertStatus(422);
    }

    /** @story:App\Http\Controllers\API\GroupController::updateGroupOptionsv2 */
    public function test_only_people_who_can_edit_the_group_change_options(): void
    {
        $this->actingAs(User::factory()->restarter()->create());
        $this->patchOptions(['features' => ['item_weight' => false]])->assertStatus(403);

        $coordinator = User::factory()->networkCoordinator()->create();
        $network = Network::factory()->create();
        $network->addGroup($this->group);
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);
        $this->patchOptions(['features' => ['item_weight' => false]])->assertOk();

        $this->actingAs(User::factory()->administrator()->create());
        $this->patchOptions(['features' => ['item_weight' => true]])->assertOk();
    }

    /** @story:App\Http\Controllers\API\GroupController::updateGroupOptionsv2 */
    public function test_a_host_defines_custom_item_fields(): void
    {
        $this->actingAs($this->host);

        $response = $this->patchOptions(['custom_fields' => [
            ['label' => 'Town the visitor came from', 'type' => 'text'],
            ['label' => 'First visit?', 'type' => 'choice', 'options' => ['Yes', 'No']],
            ['label' => 'Minutes spent', 'type' => 'number', 'options' => ['ignored']],
        ]]);

        $response->assertOk();
        $this->assertEquals([
            ['key' => 'town_the_visitor_came_from', 'label' => 'Town the visitor came from', 'type' => 'text', 'options' => []],
            ['key' => 'first_visit', 'label' => 'First visit?', 'type' => 'choice', 'options' => ['Yes', 'No']],
            ['key' => 'minutes_spent', 'label' => 'Minutes spent', 'type' => 'number', 'options' => []],
        ], $response->json('data.custom_fields'));
    }

    /** @story:App\Http\Controllers\API\GroupController::updateGroupOptionsv2 */
    public function test_renaming_keeps_the_key_and_removing_never_reuses_it(): void
    {
        $this->actingAs($this->host);
        $this->patchOptions(['custom_fields' => [['label' => 'Town', 'type' => 'text'], ['label' => 'Smell', 'type' => 'text']]]);

        // Rename "Town", remove "Smell".
        $response = $this->patchOptions(['custom_fields' => [['key' => 'town', 'label' => 'Home town', 'type' => 'text']]]);
        $this->assertEquals([['key' => 'town', 'label' => 'Home town', 'type' => 'text', 'options' => []]], $response->json('data.custom_fields'));

        // A new field called "Smell" gets a new key, so values recorded under the old one stay hidden.
        $response = $this->patchOptions(['custom_fields' => [
            ['key' => 'town', 'label' => 'Home town', 'type' => 'text'],
            ['label' => 'Smell', 'type' => 'text'],
        ]]);
        $this->assertEquals('smell_2', $response->json('data.custom_fields.1.key'));

        // Sending a removed field's key again does not bring it back: it is a new field with a new key.
        $response = $this->patchOptions(['custom_fields' => [
            ['key' => 'town', 'label' => 'Home town', 'type' => 'text'],
            ['key' => 'smell_2', 'label' => 'Smell', 'type' => 'text'],
            ['key' => 'smell', 'label' => 'Odour', 'type' => 'number'],
        ]]);
        $this->assertEquals(['town', 'smell_2', 'odour'], array_column($response->json('data.custom_fields'), 'key'));

        // The group resource shows the active fields only.
        $this->assertEquals(['town', 'smell_2', 'odour'], array_column($this->getJson("/api/v2/groups/{$this->group->idgroups}")->json('data.custom_fields'), 'key'));
    }

    /** @story:App\Http\Controllers\API\GroupController::updateGroupOptionsv2 */
    public function test_custom_fields_are_checked(): void
    {
        $this->actingAs($this->host);

        $this->patchOptions(['custom_fields' => [['label' => '', 'type' => 'text']]])->assertStatus(422);
        $this->patchOptions(['custom_fields' => [['label' => 'Colour', 'type' => 'date']]])->assertStatus(422);
        $this->patchOptions(['custom_fields' => [['label' => 'Colour', 'type' => 'choice']]])->assertStatus(422);
        $this->patchOptions(['custom_fields' => [['label' => 'Colour', 'type' => 'choice', 'options' => ['Red', 'Red']]]])->assertStatus(422);
        $eleven = array_map(fn ($i) => ['label' => "Field $i", 'type' => 'text'], range(1, 11));
        $this->patchOptions(['custom_fields' => $eleven])->assertStatus(422);
    }

    public function test_the_group_resource_shows_features_and_fields(): void
    {
        $this->group->update(['features' => ['custom_fields' => true], 'custom_fields' => [
            ['key' => 'town', 'label' => 'Town', 'type' => 'text', 'options' => []],
            ['key' => 'old', 'label' => 'Old', 'type' => 'text', 'options' => [], 'removed' => true],
        ]]);

        $data = $this->getJson("/api/v2/groups/{$this->group->idgroups}")->json('data');

        $this->assertTrue($data['features']['custom_fields']['enabled']);
        $this->assertTrue($data['features']['item_weight']['enabled']);
        $this->assertEquals([['key' => 'town', 'label' => 'Town', 'type' => 'text', 'options' => []]], $data['custom_fields']);
    }
}
