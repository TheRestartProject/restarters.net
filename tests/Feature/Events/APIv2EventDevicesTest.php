<?php

namespace Tests\Feature\Events;

use App\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2EventDevicesTest extends TestCase
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

    public function testDevicesUnknownEventReturns404(): void
    {
        $response = $this->getJson('/api/v2/events/999999/devices');

        $response->assertStatus(404);
    }

    public function testDevicesReturnsEmptyArrayWhenNoneLogged(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $this->app['auth']->forgetGuards();
        $response = $this->get("/api/v2/events/$idevents/devices");

        $response->assertSuccessful();
        $this->assertEquals([], $response->json('data'));
    }

    public function testDevicesReturnsDevicesLoggedAtTheEvent(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $id1 = $this->createDevice($idevents, 'fixed');
        $id2 = $this->createDevice($idevents, 'repairable');

        $response = $this->get("/api/v2/events/$idevents/devices");

        $response->assertSuccessful();
        $ids = array_column($response->json('data'), 'id');
        $this->assertEqualsCanonicalizing([$id1, $id2], $ids);
    }

    public function testDevicesDoesNotIncludeAnotherEventsDevices(): void
    {
        $host = User::factory()->host()->create();
        [$idgroups, $idevents1] = $this->createEventAsHost($host);
        $idevents2 = $this->createEvent($idgroups, '+2 weeks', true, true);

        $this->createDevice($idevents1, 'fixed');
        $idOnOther = $this->createDevice($idevents2, 'repairable');

        $response = $this->get("/api/v2/events/$idevents1/devices");

        $response->assertSuccessful();
        $ids = array_column($response->json('data'), 'id');
        $this->assertNotContains($idOnOther, $ids);
    }
}
