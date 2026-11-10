<?php

namespace Tests\Feature\Devices;

use App\Device;
use App\Group;
use App\Party;
use App\User;
use Tests\TestCase;

/**
 * Values of a group's custom item fields on its items (RES-2063).
 */
class DeviceCustomValuesTest extends TestCase
{
    private Party $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $group = Group::factory()->create([
            'features' => ['custom_fields' => true],
            'custom_fields' => [
                ['key' => 'town', 'label' => 'Town', 'type' => 'text', 'options' => []],
                ['key' => 'minutes', 'label' => 'Minutes', 'type' => 'number', 'options' => []],
                ['key' => 'visit', 'label' => 'First visit?', 'type' => 'choice', 'options' => ['Yes', 'No']],
                ['key' => 'smell', 'label' => 'Smell', 'type' => 'text', 'options' => [], 'removed' => true],
            ],
        ]);
        $this->event = Party::factory()->create(['group' => $group->idgroups]);
        $this->actingAs(User::factory()->administrator()->create());
    }

    private function item(array $extra = []): array
    {
        return [
            'eventid' => $this->event->idevents,
            'category' => 111,
            'item_type' => 'Kettle',
            'repair_status' => 'Fixed',
        ] + $extra;
    }

    private function create(array $customValues)
    {
        return $this->postJson('/api/v2/devices', $this->item(['custom_values' => $customValues]));
    }

    /** @story:DeviceController::createDevicev2 */
    public function test_values_are_saved_and_returned(): void
    {
        $response = $this->create(['town' => 'Leeds', 'minutes' => '12,5', 'visit' => 'Yes']);

        $response->assertOk();
        $id = $response->json('id');
        $this->assertEquals(['town' => 'Leeds', 'minutes' => 12.5, 'visit' => 'Yes'], Device::find($id)->custom_values);
        $this->assertEquals(['town' => 'Leeds', 'minutes' => 12.5, 'visit' => 'Yes'], $this->getJson("/api/v2/devices/$id")->json('data.custom_values'));
    }

    /** @story:DeviceController::createDevicev2 */
    public function test_values_are_checked_against_the_group_fields(): void
    {
        $this->create(['postcode' => 'LS1'])->assertStatus(422)->assertJsonValidationErrors('custom_values.postcode');
        $this->create(['minutes' => 'lots'])->assertStatus(422)->assertJsonValidationErrors('custom_values.minutes');
        $this->create(['visit' => 'Maybe'])->assertStatus(422)->assertJsonValidationErrors('custom_values.visit');
        $this->create(['town' => str_repeat('x', 256)])->assertStatus(422)->assertJsonValidationErrors('custom_values.town');
        // A removed field takes no new values.
        $this->create(['smell' => 'Burnt'])->assertStatus(422)->assertJsonValidationErrors('custom_values.smell');
    }

    /** @story:DeviceController::updateDevicev2 */
    public function test_an_update_keeps_values_not_sent_and_clears_blank_ones(): void
    {
        $id = $this->create(['town' => 'Leeds', 'visit' => 'No'])->json('id');
        $device = Device::find($id);
        $device->custom_values = $device->custom_values + ['smell' => 'Burnt'];
        $device->save();

        $this->patchJson("/api/v2/devices/$id", $this->item(['custom_values' => ['visit' => '', 'minutes' => 3]]))->assertOk();

        $this->assertEquals(['town' => 'Leeds', 'smell' => 'Burnt', 'minutes' => 3], Device::find($id)->custom_values);
    }

    /** @story:DeviceController::updateDevicev2 */
    public function test_an_update_without_custom_values_leaves_them_alone(): void
    {
        $id = $this->create(['town' => 'Leeds'])->json('id');

        $this->patchJson("/api/v2/devices/$id", $this->item())->assertOk();

        $this->assertEquals(['town' => 'Leeds'], Device::find($id)->custom_values);
    }

    public function test_an_item_without_values_returns_an_empty_object(): void
    {
        $id = $this->postJson('/api/v2/devices', $this->item())->json('id');

        $this->assertSame('{}', json_encode($this->getJson("/api/v2/devices/$id")->getData()->data->custom_values));
    }

    public function test_the_event_carries_the_group_switches_and_fields(): void
    {
        $data = $this->getJson("/api/v2/events/{$this->event->idevents}")->json('data');

        $this->assertEquals(['item_weight' => true, 'custom_fields' => true], $data['group_features']);
        $this->assertEquals(['town', 'minutes', 'visit'], array_column($data['group_custom_fields'], 'key'));
    }
}
