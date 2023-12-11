<?php

namespace Tests\Feature;

use App\Device;
use App\Group;
use App\Network;
use App\Party;
use App\User;
use DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Party::factory()->create();
        $this->admin = User::factory()->administrator()->create();
        $this->device_inputs = Device::factory()->raw([
                                                               'event_id' => $this->event->idevents,
                                                               'quantity' => 1,
                                                           ]);
        $this->actingAs($this->admin);

        $this->withoutExceptionHandling();
    }

    public function testEdit()
    {
        $rsp = $this->post('/device/create', $this->device_inputs);
        self::assertTrue($rsp['success']);
        $iddevices = $rsp['devices'][0]['iddevices'];
        self::assertNotNull($iddevices);

        # Add a barrier to repair - there was a bug in this case with quantity > 1.
        $this->device_inputs['repair_status'] = Device::REPAIR_STATUS_ENDOFLIFE;
        $this->device_inputs['barrier'] = [1];

        # Edit the quantity.
        $atts = $this->device_inputs;
        $atts['quantity'] = 2;
        $rsp = $this->post('/device/edit/' . $iddevices, $atts);
        self::assertEquals('Device updated!', $rsp['success']);

        # Delete the device.
        $rsp = $this->get('/device/delete/' . $iddevices, [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);
        self::assertTrue($rsp['success']);

        # Delete again - should fail.
        $rsp = $this->get('/device/delete/' . $iddevices, [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);
        self::assertFalse($rsp['success']);
    }

    public function testEditAsNetworkCoordinator()
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

        $device_inputs = Device::factory()->raw([
                                                  'event_id' => $event->idevents,
                                                  'quantity' => 1,
                                              ]);

        $rsp = $this->post('/device/create', $device_inputs);
        self::assertTrue($rsp['success']);
        $iddevices = $rsp['devices'][0]['iddevices'];
        self::assertNotNull($iddevices);

        # Edit the quantity.
        $atts = $device_inputs;
        $atts['quantity'] = 2;
        $rsp = $this->post('/device/edit/' . $iddevices, $atts);
        self::assertEquals('Device updated!', $rsp['success']);
    }

    public function testDeviceEditAddImage() {
        Storage::fake('avatars');
        $user = User::factory()->administrator()->create();
        $this->actingAs($user);

        $rsp = $this->post('/device/create', $this->device_inputs);
        self::assertTrue($rsp['success']);
        $iddevices = $rsp['devices'][0]['iddevices'];
        self::assertNotNull($iddevices);

        // Try with no file.
        $response = $this->json('POST', '/device/image-upload/' . $iddevices, []);
        $this->assertEquals(json_decode($response->getContent(), TRUE), [
            'success' => true,
            'iddevices' => $iddevices,
            'images' => []
        ]);

        // We don't upload files in a standard Laravel way, so testing upload is a bit of a hack.
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = TRUE;
        file_put_contents('/tmp/UT.jpg', file_get_contents(public_path() . '/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error'    => "0",
                'name'     => 'UT.jpg',
                'size'     => 123,
                'tmp_name' => [ '/tmp/UT.jpg' ],
                'type'     => 'image/jpg'
            ]
        ];

        $params = [];

        $response = $this->json('POST', '/device/image-upload/' . $iddevices, $params);
        $ret = json_decode($response->getContent(), TRUE);
        self::assertEquals(true, $ret['success']);
        self::assertEquals($iddevices, $ret['iddevices']);
        self::assertEquals(1, count($ret['images']));

        // And again, which will test we can upload two.
        file_put_contents('/tmp/UT2.jpg', file_get_contents('public/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error'    => "0",
                'name'     => 'UT2.jpg',
                'size'     => 123,
                'tmp_name' => [ '/tmp/UT2.jpg' ],
                'type'     => 'image/jpg'
            ]
        ];
        $response2 = $this->json('POST', '/device/image-upload/' . $iddevices, $params);
        $ret = json_decode($response2->getContent(), TRUE);
        self::assertEquals(true, $ret['success']);
        self::assertEquals($iddevices, $ret['iddevices']);
        self::assertEquals(2, count($ret['images']));

        // Delete one
        $response3 = $this->json('GET', '/device/image/delete/' . $iddevices . '/' . $ret['images'][0]['idxref'], $params);
        $this->assertTrue($response3->isRedirection());
        $response3->assertSessionHas('message');
        $this->assertEquals('Thank you, the image has been deleted', \Session::get('message'));
    }

    public function testDeviceAddAddImage() {
        Storage::fake('avatars');
        $user = User::factory()->administrator()->create();
        $this->actingAs($user);

        // Use a negative id to indicate an Add.
        $iddevices = -1;

        // We don't upload files in a standard Laravel way, so testing upload is a bit of a hack.
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        \FixometerFile::$uploadTesting = TRUE;
        file_put_contents('/tmp/UT.jpg', file_get_contents(public_path() . '/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error'    => "0",
                'name'     => 'UT.jpg',
                'size'     => 123,
                'tmp_name' => [ '/tmp/UT.jpg' ],
                'type'     => 'image/jpg'
            ]
        ];

        $params = [];

        $response = $this->json('POST', '/device/image-upload/' . $iddevices, $params);
        $ret = json_decode($response->getContent(), TRUE);
        self::assertEquals(true, $ret['success']);
        self::assertEquals($iddevices, $ret['iddevices']);
        self::assertEquals(1, count($ret['images']));

        // And again, which will test we can upload two.
        file_put_contents('/tmp/UT2.jpg', file_get_contents('public/images/community.jpg'));

        $_FILES = [
            'file' => [
                'error'    => "0",
                'name'     => 'UT2.jpg',
                'size'     => 123,
                'tmp_name' => [ '/tmp/UT2.jpg' ],
                'type'     => 'image/jpg'
            ]
        ];
        $response2 = $this->json('POST', '/device/image-upload/' . $iddevices, $params);
        $ret = json_decode($response2->getContent(), TRUE);
        self::assertEquals(true, $ret['success']);
        self::assertEquals($iddevices, $ret['iddevices']);
        self::assertEquals(2, count($ret['images']));

        // Delete one
        $response3 = $this->json('GET', '/device/image/delete/' . $iddevices . '/' . $ret['images'][0]['idxref'], $params);
        $this->assertTrue($response3->isRedirection());
        $response3->assertSessionHas('message');
        $this->assertEquals('Thank you, the image has been deleted', \Session::get('message'));
    }

    public function testNextSteps() {
        $device_inputs = Device::factory()->raw([
            'event_id' => $this->event->idevents,
            'quantity' => 1,
            'repair_status' => 2,
        ]);
        $rsp = $this->post('/device/create', $device_inputs);
        self::assertTrue($rsp['success']);
        $iddevices = $rsp['devices'][0]['iddevices'];
        self::assertNotNull($iddevices);

        # Edit the repair details to say more time needed
        $atts = $device_inputs;
        $atts['repair_details'] = 1;
        $rsp = $this->post('/device/edit/' . $iddevices, $atts);
        self::assertEquals('Device updated!', $rsp['success']);

        # Check the resulting fields.
        $device = Device::findOrFail($iddevices);
        self::assertEquals(0, $device->professional_help);
        self::assertEquals(0, $device->do_it_yourself);
        self::assertEquals(1, $device->more_time_needed);

        # Edit the repair details to say professional help needed.
        $atts = $device_inputs;
        $atts['repair_details'] = 2;
        $rsp = $this->post('/device/edit/' . $iddevices, $atts);
        self::assertEquals('Device updated!', $rsp['success']);

        # Check the resulting fields.
        $device = Device::findOrFail($iddevices);
        self::assertEquals(1, $device->professional_help);
        self::assertEquals(0, $device->do_it_yourself);
        self::assertEquals(0, $device->more_time_needed);

        # Edit the repair details to say DIY needed.
        $atts = $device_inputs;
        $atts['repair_details'] = 3;
        $rsp = $this->post('/device/edit/' . $iddevices, $atts);
        self::assertEquals('Device updated!', $rsp['success']);

        # Check the resulting fields.
        $device = Device::findOrFail($iddevices);
        self::assertEquals(0, $device->professional_help);
        self::assertEquals(1, $device->do_it_yourself);
        self::assertEquals(0, $device->more_time_needed);
    }

    public function testBarrierMultiple()
    {
        $atts = $this->device_inputs;
        $atts['quantity'] = 2;
        $atts['repair_status'] = Device::REPAIR_STATUS_ENDOFLIFE;
        $atts['barrier'] = [1];

        $rsp = $this->post('/device/create', $atts);
        self::assertTrue($rsp['success']);
        $iddevices = $rsp['devices'][0]['iddevices'];
        self::assertNotNull($iddevices);
    }
}
