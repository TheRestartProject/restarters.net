<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;

class SparePartsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $event = factory(Party::class)->create();
        $this->device_inputs = factory(Device::class)->raw([
            'event_id' => $event->idevents,
            'quantity' => 1,
        ]);

        $this->input_spare_parts_from_manufacturer = 1;
        $this->input_no_spare_parts_needed = 2;
        $this->input_spare_parts_from_third_party = 3;

        $admin = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin);

        $this->withoutExceptionHandling();
    }

    /** @test */
    public function recording_spare_parts_from_manufacturer()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_FIXED;
        $this->device_inputs['spare_parts'] = $this->input_spare_parts_from_manufacturer;
        $response = $this->post('/device/create', $this->device_inputs);

        $device = Device::find(1);
        $this->assertEquals(Device::SPARE_PARTS_NEEDED, $device->spare_parts);
        $this->assertEquals(Device::PARTS_PROVIDER_MANUFACTURER, $device->parts_provider);
    }

    /** @test */
    public function recording_spare_parts_from_third_party()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_FIXED;
        $this->device_inputs['spare_parts'] = $this->input_spare_parts_from_third_party;

        $response = $this->post('/device/create', $this->device_inputs);

        $device = Device::find(1);
        $this->assertEquals(Device::SPARE_PARTS_NEEDED, $device->spare_parts);
        $this->assertEquals(Device::PARTS_PROVIDER_THIRD_PARTY, $device->parts_provider);
    }

    /** @test */
    public function recording_no_spare_parts_needed()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_FIXED;
        $this->device_inputs['spare_parts'] = $this->input_no_spare_parts_needed;

        $response = $this->post('/device/create', $this->device_inputs);

        $device = Device::find(1);
        $this->assertEquals(Device::SPARE_PARTS_NOT_NEEDED, $device->spare_parts);
        $this->assertNull($device->parts_provider);
    }

    /** @test */
    public function recording_spare_parts_related_barrier()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_ENDOFLIFE;
        $this->device_inputs['barrier'] = [1];

        $response = $this->post('/device/create', $this->device_inputs);

        $device = Device::find(1);
        $this->assertEquals(Device::SPARE_PARTS_NEEDED, $device->spare_parts);
        $this->assertNull($device->parts_provider);
    }

    /** @test */
    public function recording_no_spare_parts_related_barrier()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_ENDOFLIFE;
        $this->device_inputs['barrier'] = [4];

        $response = $this->post('/device/create', $this->device_inputs);

        $device = Device::find(1);
        $this->assertEquals(Device::SPARE_PARTS_NOT_NEEDED, $device->spare_parts);
        $this->assertNull($device->parts_provider);
    }
}
