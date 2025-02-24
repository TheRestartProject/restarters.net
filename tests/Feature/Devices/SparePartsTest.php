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

        $this->event = Party::factory()->create();
        $this->device_inputs = Device::factory()->raw([
            'event_id' => $this->event->idevents,
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
        $iddevices = $this->createDevice($this->event->idevents,
            'misc', null, 1.5, 100, '',
            Device::REPAIR_STATUS_FIXED_STR,
            Device::NEXT_STEPS_MORE_TIME_NEEDED_STR,
            Device::PARTS_PROVIDER_MANUFACTURER_STR);

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

        $iddevices = $this->createDevice($this->event->idevents,
            'misc', null, 1.5, 100, '',
            Device::REPAIR_STATUS_REPAIRABLE_STR,
            Device::NEXT_STEPS_MORE_TIME_NEEDED_STR,
            Device::PARTS_PROVIDER_THIRD_PARTY_STR);

        $device = Device::find($iddevices);
        $this->assertEquals(trans('partials.repairable'), $device->getRepairStatus());
        $this->assertEquals(Device::SPARE_PARTS_NEEDED, $device->spare_parts);
        $this->assertEquals(Device::PARTS_PROVIDER_THIRD_PARTY, $device->parts_provider);
        $this->assertEquals(trans('partials.yes_third_party'), $device->getSpareParts());
    }

    /** @test */
    public function recording_no_spare_parts_needed()
    {
        $iddevices = $this->createDevice($this->event->idevents,
            'misc', null, 1.5, 100, '',
            Device::REPAIR_STATUS_FIXED_STR,
            NULL,
            Device::PARTS_PROVIDER_NO_STR);

        $device = Device::find($iddevices);
        $this->assertEquals(Device::SPARE_PARTS_NOT_NEEDED, $device->spare_parts);
        $this->assertNull($device->parts_provider);
        $this->assertEquals(trans('partials.no'), $device->getSpareParts());
    }

    /** @test */
    public function recording_spare_parts_related_barrier()
    {
        $iddevices = $this->createDevice($this->event->idevents,
            'misc', Device::BARRIER_SPARE_PARTS_NOT_AVAILABLE_STR, 1.5, 100, '',
            Device::REPAIR_STATUS_ENDOFLIFE_STR, null, Device::PARTS_PROVIDER_MANUFACTURER_STR);

        $device = Device::find($iddevices);
        $this->assertEquals(Device::SPARE_PARTS_NEEDED, $device->spare_parts);
        $this->assertNull($device->parts_provider);
        $this->assertEquals(trans('partials.end_of_life'), $device->getRepairStatus());

    }

    /** @test */
    public function recording_no_spare_parts_related_barrier()
    {
        $iddevices = $this->createDevice($this->event->idevents,
            'misc', Device::BARRIER_REPAIR_INFORMATION_NOT_AVAILABLE_STR, 1.5, 100, '',
            Device::REPAIR_STATUS_ENDOFLIFE_STR, null, Device::PARTS_PROVIDER_MANUFACTURER_STR);

        $device = Device::find($iddevices);
        $this->assertEquals(1, $device->spare_parts);
        $this->assertNull($device->parts_provider);
    }
}
