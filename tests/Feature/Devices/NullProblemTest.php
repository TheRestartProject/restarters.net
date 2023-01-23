<?php

namespace Tests\Feature;

use App\Device;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;

class NullProblemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        Party::truncate();
        Device::truncate();
        DB::statement('SET foreign_key_checks=1');

        $event = Party::factory()->create();
        $this->device_inputs = Device::factory()->raw([
                                                               'event_id' => $event->idevents,
                                                               'quantity' => 1,
                                                           ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->withoutExceptionHandling();
    }

    /** @test */
    public function null_problem_mapped_to_empty_string()
    {
        $this->device_inputs['problem'] = null;
        $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals('', $device->problem);
    }
}
