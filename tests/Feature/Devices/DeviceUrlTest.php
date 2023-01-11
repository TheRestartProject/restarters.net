<?php

namespace Tests\Feature\Dashboard;

use App\Device;
use App\Party;
use App\Role;
use App\User;
use DB;
use Hash;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tests\TestCase;

class DeviceUrlTest extends TestCase
{
    public function testBasic()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Create an event with a device.
        $this->event = Party::factory()->create();
        $this->device_inputs = Device::factory()->raw([
                                                               'event_id' => $this->event->idevents,
                                                               'quantity' => 1,
                                                           ]);

        $rsp = $this->post('/device/create', $this->device_inputs);
        self::assertTrue($rsp['success']);
        $iddevices = $rsp['devices'][0]['iddevices'];
        self::assertNotNull($iddevices);

        $rsp = $this->post('/device-url', [
            'device_id' => $iddevices,
            'source' => 1,
            'url' => 'https://therestartproject.org'
        ]);

        $rsp->assertJsonStructure([
            'success',
            'id'
        ]);

        $idurl = json_decode($rsp->getContent(), true)['id'];

        $rsp = $this->patch('/device-url/' . $idurl, [
            'source' => 2,
            'url' => 'https://restarters.net'
        ]);

        $rsp->assertJson([
            'success' => true
        ]);

        $rsp = $this->delete('/device-url/' . $idurl);
        $rsp->assertJson([
                             'success' => true
                         ]);
    }

    public function testIndex() {
        // Should fail as we don't have this route.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $this->expectException(MethodNotAllowedHttpException::class);
        $this->get('/device-url');
    }
}
