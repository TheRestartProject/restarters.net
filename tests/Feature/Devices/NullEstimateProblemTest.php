<?php

namespace Tests\Feature;

use App\Device;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;

/**
 * A blank weight means nobody weighed the item, so it is stored as NULL rather than 0 (RES-2063).
 */
class NullEstimateProblemTest extends TestCase
{
    /** @story:DeviceController::createDevicev2 */
    public function testNullEstimateCreate(): void
    {
        $event = Party::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $iddevices = $this->createDevice($event->idevents, 'misc', null, 1, null);

        $this->assertNull(Device::find($iddevices)->estimate);
        $this->assertNull($this->getDevice($iddevices)['estimate']);
    }

    /** @story:DeviceController::createDevicev2 */
    public function testBlankEstimateIsStoredAsNull(): void
    {
        $event = Party::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $iddevices = $this->createDevice($event->idevents, 'misc', null, 1, '');

        $this->assertNull(Device::find($iddevices)->estimate);
    }

    /** @story:DeviceController::createDevicev2 */
    public function testDecimalCommaIsAccepted(): void
    {
        $event = Party::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $iddevices = $this->createDevice($event->idevents, 'misc', null, 1, '1,5');

        $this->assertEquals(1.5, Device::find($iddevices)->estimate);
        $this->assertEquals(1.5, $this->getDevice($iddevices)['estimate']);
    }

    /** @story:DeviceController::createDevicev2 */
    public function testEnteredZeroIsKept(): void
    {
        $event = Party::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $iddevices = $this->createDevice($event->idevents, 'misc', null, 1, 0);

        $this->assertSame(0.0, (float) Device::find($iddevices)->estimate);
        $this->assertNotNull(Device::find($iddevices)->estimate);
    }

    /** @story:DeviceController::updateDevicev2 */
    public function testNullEstimateEdit(): void
    {
        $event = Party::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());

        $iddevices = $this->createDevice($event->idevents, 'misc', null, 1, 100);
        $this->assertEquals(100, Device::find($iddevices)->estimate);

        $atts = $this->getDevice($iddevices);
        $atts['estimate'] = null;
        $atts['category'] = $atts['category']['id'];
        $response = $this->patch("/api/v2/devices/$iddevices", $atts);
        $response->assertSuccessful();

        $this->assertNull(Device::find($iddevices)->estimate);
    }

    public function testExistingZerosBecomeNull(): void
    {
        // The migration turned stored zeros into NULL, and the column default is now NULL.
        $event = Party::factory()->create();
        $device = Device::factory()->create(['event' => $event->idevents]);

        $this->assertNull($device->fresh()->estimate);
        $column = DB::selectOne("SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'devices' AND COLUMN_NAME = 'estimate'");
        $this->assertEquals('YES', $column->IS_NULLABLE);
    }
}
