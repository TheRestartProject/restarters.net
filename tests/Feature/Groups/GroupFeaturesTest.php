<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Network;
use App\Services\GroupFeatures;
use Tests\TestCase;

/**
 * Which features apply to a group (RES-2063): a network's "always" setting, then the group's own choice, then a
 * network's default, then the feature's own default. On wins when networks disagree.
 */
class GroupFeaturesTest extends TestCase
{
    private function network(array $features, string $name = 'Net'): Network
    {
        return Network::factory()->create(['name' => $name, 'features' => $features]);
    }

    private function resolve(Group $group): array
    {
        return GroupFeatures::resolve($group->fresh());
    }

    public function test_feature_defaults_apply_when_nobody_has_set_anything(): void
    {
        $features = $this->resolve(Group::factory()->create());

        $this->assertSame(['item_weight', 'custom_fields'], array_keys($features));
        $this->assertEquals(
            ['enabled' => true, 'source' => 'default', 'locked' => false, 'group_setting' => null, 'inherited' => true, 'set_by' => []],
            $features['item_weight']
        );
        $this->assertFalse($features['custom_fields']['enabled']);
        $this->assertFalse($features['custom_fields']['inherited']);
    }

    public function test_the_group_choice_beats_the_feature_default(): void
    {
        $group = Group::factory()->create(['features' => ['item_weight' => false, 'custom_fields' => true]]);

        $features = $this->resolve($group);

        $this->assertFalse($features['item_weight']['enabled']);
        $this->assertEquals('group', $features['item_weight']['source']);
        $this->assertFalse($features['item_weight']['group_setting']);
        $this->assertTrue($features['item_weight']['inherited']);
        $this->assertTrue($features['custom_fields']['enabled']);
    }

    public function test_a_network_default_applies_until_the_group_chooses(): void
    {
        $group = Group::factory()->create();
        $this->network(['custom_fields' => 'default_on'], 'Repair Together')->addGroup($group);

        $features = $this->resolve($group);
        $this->assertTrue($features['custom_fields']['enabled']);
        $this->assertEquals('network_default', $features['custom_fields']['source']);
        $this->assertEquals(['Repair Together'], $features['custom_fields']['set_by']);
        $this->assertFalse($features['custom_fields']['locked']);

        $group->features = ['custom_fields' => false];
        $group->save();
        $features = $this->resolve($group);
        $this->assertFalse($features['custom_fields']['enabled']);
        $this->assertEquals('group', $features['custom_fields']['source']);
        $this->assertTrue($features['custom_fields']['inherited']);
    }

    public function test_a_network_always_setting_beats_the_group(): void
    {
        $group = Group::factory()->create(['features' => ['item_weight' => true]]);
        $this->network(['item_weight' => 'off'], 'Strict')->addGroup($group);

        $features = $this->resolve($group);

        $this->assertFalse($features['item_weight']['enabled']);
        $this->assertTrue($features['item_weight']['locked']);
        $this->assertEquals('network', $features['item_weight']['source']);
        $this->assertEquals(['Strict'], $features['item_weight']['set_by']);
        $this->assertTrue($features['item_weight']['group_setting']);
    }

    public function test_on_wins_when_networks_disagree(): void
    {
        $group = Group::factory()->create();
        $this->network(['item_weight' => 'off', 'custom_fields' => 'default_off'], 'A')->addGroup($group);
        $this->network(['item_weight' => 'on', 'custom_fields' => 'default_on'], 'B')->addGroup($group);

        $features = $this->resolve($group);

        $this->assertTrue($features['item_weight']['enabled']);
        $this->assertEquals(['B'], $features['item_weight']['set_by']);
        $this->assertTrue($features['custom_fields']['enabled']);
        $this->assertEquals(['B'], $features['custom_fields']['set_by']);
    }

    public function test_enabled_lists_each_feature_on_or_off(): void
    {
        $group = Group::factory()->create(['features' => ['custom_fields' => true]]);

        $this->assertSame(['item_weight' => true, 'custom_fields' => true], GroupFeatures::enabled($group->fresh()));
    }

    public function test_network_settings_list_every_feature(): void
    {
        $network = $this->network(['custom_fields' => 'default_on']);

        $this->assertSame(['item_weight' => null, 'custom_fields' => 'default_on'], GroupFeatures::networkSettings($network));
    }
}
