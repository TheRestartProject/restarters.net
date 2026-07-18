<?php

namespace Tests\Feature\Devices;

use App\Device;
use App\Role;
use Tests\TestCase;

/**
 * PATCH /api/v2/devices/{id} authorises against the device's *own* owning
 * event, not just the eventid in the request body. Without that, a host of
 * event A could pass eventid=A and reassign/overwrite a device that actually
 * belongs to a stranger's event B (device ids are sequential and enumerable).
 */
class APIv2DeviceUpdateAuthTest extends TestCase
{
    public function testCannotUpdateDeviceBelongingToAnotherHostsEvent(): void
    {
        // Host A owns event A, with a device on it.
        $hostA = $this->loginAsTestUser(Role::HOST);
        $groupA = $this->createGroup('Group A');
        $eventA = $this->createEvent($groupA, 'yesterday');

        $device = Device::create([
            'event' => $eventA,
            'category' => 11,
            'category_creation' => 11,
            'problem' => 'Original problem',
            'notes' => 'Original notes',
            'brand' => 'Acme',
            'model' => 'K1',
            'item_type' => 'Kettle',
            'repair_status' => Device::REPAIR_STATUS_FIXED,
            'spare_parts' => Device::SPARE_PARTS_NOT_NEEDED,
            'parts_provider' => 0,
            'more_time_needed' => 0,
            'professional_help' => 0,
            'do_it_yourself' => 0,
        ]);
        $deviceId = $device->iddevices;

        // Host B owns an unrelated event B (and has no rights over event A).
        $hostB = $this->loginAsTestUser(Role::HOST);
        $groupB = $this->createGroup('Group B');
        $eventB = $this->createEvent($groupB, 'yesterday');

        // Host B tries to hijack host A's device by passing their OWN eventid
        // (which they legitimately host) as the permission target.
        $this->withExceptionHandling();
        $response = $this->patchJson('/api/v2/devices/'.$deviceId.'?api_token='.$hostB->api_token, [
            'eventid' => $eventB,
            'category' => 11,
            'item_type' => 'Hijacked',
            'brand' => 'Evil',
            'model' => 'X',
            'repair_status' => Device::REPAIR_STATUS_FIXED_STR,
            'parts_provider' => Device::PARTS_PROVIDER_NO_STR,
            'spare_parts' => Device::PARTS_PROVIDER_NO_STR,
        ]);

        $response->assertStatus(403);

        // The device is untouched: still on event A, original fields intact.
        $fresh = Device::findOrFail($deviceId);
        $this->assertEquals($eventA, $fresh->event);
        $this->assertEquals('Kettle', $fresh->item_type);
        $this->assertEquals('Acme', $fresh->brand);
    }

    public function testHostCanStillUpdateTheirOwnEventsDevice(): void
    {
        // Regression guard: the new ownership check must not block the normal
        // case (a host editing a device on their own event).
        $host = $this->loginAsTestUser(Role::HOST);
        $group = $this->createGroup('Own Group');
        $event = $this->createEvent($group, 'yesterday');

        $device = Device::create([
            'event' => $event,
            'category' => 11,
            'category_creation' => 11,
            'problem' => 'Original problem',
            'notes' => 'Original notes',
            'brand' => 'Acme',
            'model' => 'K1',
            'item_type' => 'Kettle',
            'repair_status' => Device::REPAIR_STATUS_FIXED,
            'spare_parts' => Device::SPARE_PARTS_NOT_NEEDED,
            'parts_provider' => 0,
            'more_time_needed' => 0,
            'professional_help' => 0,
            'do_it_yourself' => 0,
        ]);

        $response = $this->patchJson('/api/v2/devices/'.$device->iddevices.'?api_token='.$host->api_token, [
            'eventid' => $event,
            'category' => 11,
            'item_type' => 'Toaster',
            'brand' => 'Acme',
            'model' => 'K1',
            'repair_status' => Device::REPAIR_STATUS_FIXED_STR,
            'parts_provider' => Device::PARTS_PROVIDER_NO_STR,
            'spare_parts' => Device::PARTS_PROVIDER_NO_STR,
        ]);

        $response->assertSuccessful();
        $this->assertEquals('Toaster', Device::findOrFail($device->iddevices)->item_type);
    }
}
