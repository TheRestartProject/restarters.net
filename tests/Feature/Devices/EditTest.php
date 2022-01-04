<?php

namespace Tests\Feature;

use App\Device;
use App\Party;
use App\User;
use DB;
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
    }
}
