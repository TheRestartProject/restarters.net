<?php

namespace Tests\Feature;

use App\Device;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;

class NullAgeProblemTest extends TestCase
{
    public function testNullAge()
    {
        $event = Party::factory()->create();
        $this->device_inputs = Device::factory()->raw([
                                                               'event_id' => $event->idevents,
                                                               'quantity' => 1,
                                                               'age' => null
                                                           ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);
        $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals(0, $device->age);

        $this->device_inputs['age'] = 1;
        $rsp = $this->post('/device/edit/' . $iddevices, $this->device_inputs);
        self::assertEquals('Device updated!', $rsp['success']);

        $this->device_inputs['age'] = null;
        $rsp = $this->post('/device/edit/' . $iddevices, $this->device_inputs);
        self::assertEquals('Device updated!', $rsp['success']);
    }
}
