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

        $this->event = Party::factory()->create();
        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->withoutExceptionHandling();
    }

    /** @test */
    public function null_problem_mapped_to_empty_string()
    {
        $iddevices = $this->createDevice($this->event->idevents, 'misc', null, 1, 100, null);
        $iddevices = Device::latest()->first()->iddevices;

        $device = Device::find($iddevices);
        $this->assertEquals('', $device->problem);
    }
}
