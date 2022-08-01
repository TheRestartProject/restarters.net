<?php

namespace Tests\Feature;

use App\Device;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;

class NullEstimateProblemTest extends TestCase
{
    public function testNullAge()
    {
        $event = factory(Party::class)->create();
        $this->device_inputs = factory(Device::class)->raw([
                                                               'event_id' => $event->idevents,
                                                               'quantity' => 1,
                                                               'estimate' => null
                                                           ]);

        $admin = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin);
        $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals(0, $device->estimate);

        $this->device_inputs['estimate'] = 1;
        $rsp = $this->post('/device/edit/' . $iddevices, $this->device_inputs);
        self::assertEquals('Device updated!', $rsp['success']);

        $this->device_inputs['estimate'] = null;
        $rsp = $this->post('/device/edit/' . $iddevices, $this->device_inputs);
        self::assertEquals('Device updated!', $rsp['success']);
    }
}
