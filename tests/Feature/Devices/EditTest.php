<?php

namespace Tests\Feature;

use App\Device;
use App\Events\DeviceCreatedOrUpdated;
use App\EventsUsers;
use App\Group;
use App\Listeners\DeviceUpdatedAt;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Party::factory()->create();
        $this->admin = User::factory()->administrator()->create();
        $this->actingAs($this->admin);

        $this->withoutExceptionHandling();
    }

    public function testEdit(): void
    {
        $iddevices = $this->createDevice($this->event->idevents, 'misc');

        # Add a barrier to repair - there was a bug in this case with quantity > 1.
        $iddevices = $this->createDevice($this->event->idevents, 'misc', Device::BARRIER_SPARE_PARTS_NOT_AVAILABLE_STR);

        # Edit the problem.
        $atts = $this->getDevice($iddevices);
        $atts['problem'] = 'New problem';
        $atts['category'] = $atts['category']['id'];

        $response = $this->patch("/api/v2/devices/$iddevices", $atts);
        $response->assertSuccessful();

        $atts = $this->getDevice($iddevices);
        $this->assertEquals('New problem', $atts['problem']);

        # Delete the device.
        $this->deleteDevice($iddevices);

        # Delete again - should fail.
        $this->expectException(ModelNotFoundException::class);
        $this->deleteDevice($iddevices);
    }

    public function testEditAsNetworkCoordinator(): void
    {
        $network = Network::factory()->create();
        $group = Group::factory()->create();
        $network->addGroup($group);

        $event = Party::factory()->create(['group' => $group->idgroups]);
        $event->wordpress_post_id = 100;
        $event->approved = true;
        $event->save();

        // Make an admin who is also a network controller.
        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);

        $iddevices = $this->createDevice($event->idevents, 'misc');

        # Edit the problem.
        $atts = $this->getDevice($iddevices);
        $atts['problem'] = 'New problem';
        $atts['category'] = $atts['category']['id'];

        $response = $this->patch("/api/v2/devices/$iddevices", $atts);
        $response->assertSuccessful();
    }

    public function testNextSteps(): void {
        $iddevices = $this->createDevice($this->event->idevents, 'misc');

        # Edit the repair details to say more time needed
        $atts = $this->getDevice($iddevices);
        $atts['next_steps'] = Device::NEXT_STEPS_MORE_TIME_NEEDED_STR;
        $atts['category'] = $atts['category']['id'];
        $response = $this->patch("/api/v2/devices/$iddevices", $atts);
        $response->assertSuccessful();

        # Check the resulting fields.
        $device = Device::findOrFail($iddevices);
        self::assertEquals(0, $device->professional_help);
        self::assertEquals(0, $device->do_it_yourself);
        self::assertEquals(1, $device->more_time_needed);

        # Edit the repair details to say professional help needed.
        $atts = $this->getDevice($iddevices);
        $atts['next_steps'] = Device::NEXT_STEPS_PROFESSIONAL_HELP_STR;
        $atts['category'] = $atts['category']['id'];
        $response = $this->patch("/api/v2/devices/$iddevices", $atts);
        $response->assertSuccessful();

        # Check the resulting fields.
        $device = Device::findOrFail($iddevices);
        self::assertEquals(1, $device->professional_help);
        self::assertEquals(0, $device->do_it_yourself);
        self::assertEquals(0, $device->more_time_needed);

        # Edit the repair details to say DIY needed.
        $atts = $this->getDevice($iddevices);
        $atts['next_steps'] = Device::NEXT_STEPS_DO_IT_YOURSELF_STR;
        $atts['category'] = $atts['category']['id'];
        $response = $this->patch("/api/v2/devices/$iddevices", $atts);
        $response->assertSuccessful();

        # Check the resulting fields.
        $device = Device::findOrFail($iddevices);
        self::assertEquals(0, $device->professional_help);
        self::assertEquals(1, $device->do_it_yourself);
        self::assertEquals(0, $device->more_time_needed);
    }

    public function testQueuedJobForDeletedEvent(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        $idevents = $this->createEvent($id, 'yesterday');
        $iddevices = $this->createDevice($idevents, 'misc');
        $device = Device::find($iddevices);

        $job = new DeviceCreatedOrUpdated($device);

        # Delete the event (will stay in DB as soft delete).
        Party::where('idevents', $idevents)->delete();

        $handler = new DeviceUpdatedAt();
        $handler->handle($job);
    }
}
