<?php

namespace Tests\Feature\Devices;

use App\Party;
use App\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2LatestRepairedEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testReturnsNullDataWhenNoFinishedEventHasRepairs(): void
    {
        $response = $this->get('/api/v2/stats/latest-repaired-event');

        $response->assertSuccessful();
        $this->assertNull($response->json('data'));
    }

    public function testIsPublicAndReturnsTheMostRecentFinishedEventWithARepair(): void
    {
        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $idgroups = $this->createGroup('Latest Repaired Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, 'yesterday', true, true);
        $this->createDevice($idevents, 'fixed');

        $this->app['auth']->forgetGuards();
        $response = $this->get('/api/v2/stats/latest-repaired-event');

        $response->assertSuccessful();
        $this->assertEquals($idevents, $response->json('data.id'));
        $this->assertArrayHasKey('waste_prevented', $response->json('data'));
        $this->assertEquals($idgroups, $response->json('data.group.id'));
    }

    public function testIgnoresEventsWithNoRepairedDevices(): void
    {
        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $idgroups = $this->createGroup('Latest Repaired Test Group 2 '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, 'yesterday', true, true);
        $this->createDevice($idevents, 'repairable');

        $this->app['auth']->forgetGuards();
        $response = $this->get('/api/v2/stats/latest-repaired-event');

        $response->assertSuccessful();
        $this->assertNull($response->json('data'));
    }

    public function testIgnoresEventsThatHaveNotYetFinished(): void
    {
        $host = User::factory()->host()->create();
        $this->actingAs($host);
        $idgroups = $this->createGroup('Latest Repaired Test Group 3 '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);
        $this->createDevice($idevents, 'fixed');

        $this->app['auth']->forgetGuards();
        $response = $this->get('/api/v2/stats/latest-repaired-event');

        $response->assertSuccessful();
        $this->assertNull($response->json('data'));
    }
}
