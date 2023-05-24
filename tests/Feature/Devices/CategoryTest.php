<?php

namespace Tests\Feature;

use App\Device;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryChange()
    {
        $event = Party::factory()->create();
        $this->device_inputs = Device::factory()->raw([
                                                          'event_id' => $event->idevents,
                                                          'category' => 11,
                                                          'category_creation' => 11,
                                                          'quantity' => 1,
                                                      ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $this->device_inputs['category'] = 46;
        unset($this->device_inputs['category_creation']);

        $rsp = $this->post('/device/edit/' . $iddevices, $this->device_inputs);
        self::assertEquals('Device updated!', $rsp['success']);

        $device = Device::findOrFail($iddevices);
        self::assertEquals($device->category_creation, 11);
    }
}
