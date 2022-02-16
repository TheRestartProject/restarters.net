<?php

namespace Tests\Feature;

use App\Device;
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

        $this->event = factory(Party::class)->create();
        $this->admin = factory(User::class)->state('Administrator')->create();
        $this->device_inputs = factory(Device::class)->raw([
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

    public function testImage() {
        Storage::fake('avatars');
        $user = factory(User::class)->states('Administrator')->create();
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
        file_put_contents('/tmp/UT.jpg', file_get_contents('public/images/community.jpg'));

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
        $response3 = $this->json('GET', '/device/image/delete/' . $iddevices . '/' . $ret['images'][0]['idimages'] . '/' . $ret['images'][0]['path'], $params);
        $this->assertTrue($response3->isRedirection());
        $response3->assertSessionHas('message');
        $this->assertEquals('Thank you, the image has been deleted', \Session::get('message'));
    }
}
