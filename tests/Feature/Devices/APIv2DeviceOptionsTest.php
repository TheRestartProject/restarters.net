<?php

namespace Tests\Feature\Devices;

use App\Barrier;
use App\Device;
use Tests\TestCase;

class APIv2DeviceOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testOptionsIsPublicAndReturnsBarriersSparePartsNextSteps(): void
    {
        $response = $this->get('/api/v2/devices/options');

        $response->assertSuccessful();

        $barriers = $response->json('data.barriers');
        $this->assertCount(Barrier::count(), $barriers);
        $this->assertContains(Device::BARRIER_SPARE_PARTS_NOT_AVAILABLE_STR, array_column($barriers, 'name'));
        foreach ($barriers as $barrier) {
            $this->assertArrayHasKey('id', $barrier);
            $this->assertArrayHasKey('name', $barrier);
        }

        $this->assertEquals([
            Device::PARTS_PROVIDER_NO_STR,
            Device::PARTS_PROVIDER_MANUFACTURER_STR,
            Device::PARTS_PROVIDER_THIRD_PARTY_STR,
        ], $response->json('data.spare_parts'));

        $this->assertEquals([
            Device::NEXT_STEPS_MORE_TIME_NEEDED_STR,
            Device::NEXT_STEPS_PROFESSIONAL_HELP_STR,
            Device::NEXT_STEPS_DO_IT_YOURSELF_STR,
        ], $response->json('data.next_steps'));
    }
}
