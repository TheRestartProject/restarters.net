<?php

namespace Tests\Feature\Devices;

use App\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2DevicesListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createEventAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Devices List Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);

        return [$idgroups, $idevents];
    }

    public function testListIsPublic(): void
    {
        $response = $this->get('/api/v2/devices');

        $response->assertSuccessful();
        $this->assertArrayHasKey('count', $response->json('data'));
        $this->assertArrayHasKey('items', $response->json('data'));
    }

    public function testListFiltersByPoweredCategory(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        // Cat2 (222) is seeded powered=1; Cat1 (111, used by the 'fixed' factory state) is
        // seeded powered=0.
        $poweredId = $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 222);
        $unpoweredId = $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 111);

        $response = $this->get('/api/v2/devices?powered=true&size=100');

        $response->assertSuccessful();
        $ids = array_column($response->json('data.items'), 'id');
        $this->assertContains($poweredId, $ids);
        $this->assertNotContains($unpoweredId, $ids);
    }

    public function testListFiltersByBrand(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        // createDevice() always sets brand='Test brand' - filter on that rather than posting a
        // custom brand directly (the helper doesn't expose a brand override, and mirroring its
        // proven request shape avoids duplicating the whole device-creation payload here).
        $iddevices = $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 111);

        $response = $this->get('/api/v2/devices?brand=Test+brand');

        $response->assertSuccessful();
        $ids = array_column($response->json('data.items'), 'id');
        $this->assertContains($iddevices, $ids);
    }

    public function testListRespectsPagination(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        for ($i = 0; $i < 3; $i++) {
            $this->createDevice($idevents, 'fixed', null, 1.5, 100, '', null, null, null, 111);
        }

        $response = $this->get('/api/v2/devices?size=2&page=1&brand=');

        $response->assertSuccessful();
        $this->assertLessThanOrEqual(2, count($response->json('data.items')));
    }
}
