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

        $event = Party::factory()->create();
        $this->device_inputs = Device::factory()->raw([
            'event_id' => $event->idevents,
            'quantity' => 1,
        ]);

        $this->input_spare_parts_from_manufacturer = 1;
        $this->input_no_spare_parts_needed = 2;
        $this->input_spare_parts_from_third_party = 3;

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->withoutExceptionHandling();
    }

    /** @test */
    public function recording_spare_parts_from_manufacturer()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_FIXED;
        $this->device_inputs['spare_parts'] = $this->input_spare_parts_from_manufacturer;
        $response = $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals(Device::SPARE_PARTS_NEEDED, $device->spare_parts);
        $this->assertEquals(Device::PARTS_PROVIDER_MANUFACTURER, $device->parts_provider);
        $this->assertEquals(trans('partials.yes_manufacturer'), $device->getSpareParts());
        $this->assertEquals(trans('partials.fixed'), $device->getRepairStatus());
    }

    /** @test */
    public function recording_spare_parts_from_third_party()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_REPAIRABLE;
        $this->device_inputs['spare_parts'] = $this->input_spare_parts_from_third_party;

        $response = $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals(trans('partials.repairable'), $device->getRepairStatus());
        $this->assertEquals(Device::SPARE_PARTS_NEEDED, $device->spare_parts);
        $this->assertEquals(Device::PARTS_PROVIDER_THIRD_PARTY, $device->parts_provider);
        $this->assertEquals(trans('partials.yes_third_party'), $device->getSpareParts());
    }

    /** @test */
    public function recording_no_spare_parts_needed()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_FIXED;
        $this->device_inputs['spare_parts'] = $this->input_no_spare_parts_needed;

        $response = $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals(Device::SPARE_PARTS_NOT_NEEDED, $device->spare_parts);
        $this->assertNull($device->parts_provider);
        $this->assertEquals(trans('partials.no'), $device->getSpareParts());
    }

    /** @test */
    public function recording_spare_parts_related_barrier()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_ENDOFLIFE;
        $this->device_inputs['barrier'] = [1];

        $response = $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals(Device::SPARE_PARTS_NEEDED, $device->spare_parts);
        $this->assertNull($device->parts_provider);
        $this->assertEquals(trans('partials.end_of_life'), $device->getRepairStatus());

    }

    /** @test */
    public function recording_no_spare_parts_related_barrier()
    {
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_ENDOFLIFE;
        $this->device_inputs['barrier'] = [4];

        $response = $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals(Device::SPARE_PARTS_NOT_NEEDED, $device->spare_parts);
        $this->assertNull($device->parts_provider);
    }
}
