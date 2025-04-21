<?php

namespace Tests\Feature;

use App\Device;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;

class NullEstimateProblemTest extends TestCase
{
    public function testNullEstimateCreate(): void
    {
        $event = Party::factory()->create();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        // Creating a device with a null estimate should result in an exception.  We are supposed to pass in 0.
        $this->expectException(ValidationException::class);
        $iddevices = $this->createDevice($event->idevents, 'misc', null, 1, null);
    }

    public function testNullEstimateEdit(): void
    {
        $event = Party::factory()->create();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $iddevices = $this->createDevice($event->idevents, 'misc', null, 1, 100);
        $device = Device::find($iddevices);
        $this->assertEquals(1, $device->age);

        // Editing a device with a null age should result in an exception.  We are supposed to pass in 0,
        // which can either mean "age not known" or "age < 1 year".
        $atts = $this->getDevice($iddevices);
        $atts['estimate'] = null;
        $atts['category'] = $atts['category']['id'];
        $this->expectException(ValidationException::class);
        $response = $this->patch("/api/v2/devices/$iddevices", $atts);
    }
}
