<?php

namespace Tests\Feature\Events;

use App\Audits;
use App\Device;
use App\EventsUsers;
use App\Party;
use App\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2EventDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createEventAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Delete Event Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);

        return [$idgroups, $idevents];
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->deleteJson("/api/v2/events/$idevents");

        $response->assertStatus(401);
    }

    public function testDeleteDeniedForRestarterWithoutPermission(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'de-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->delete("/api/v2/events/$idevents?api_token=de-tok-1");

        $response->assertStatus(403);
        $this->assertNotNull(Party::find($idevents));
    }

    public function testDeleteUnknownEventReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'de-tok-404']);
        $this->actingAs($admin);

        $response = $this->delete('/api/v2/events/999999?api_token=de-tok-404');

        $response->assertStatus(404);
    }

    public function testHostCanDeleteEventEvenWithDevicesAttached(): void
    {
        // Parity check (judgment call #4): Party::canDelete() (zero devices) is only a
        // client-side confirm-dialog rule - the endpoint itself must NOT enforce it.
        $host = User::factory()->host()->create(['api_token' => 'de-tok-2']);
        [, $idevents] = $this->createEventAsHost($host);
        $this->createDevice($idevents, 'fixed');

        $response = $this->delete("/api/v2/events/$idevents?api_token=de-tok-2");

        $response->assertSuccessful();
        $this->assertEquals(['deleted' => true], $response->json('data'));
    }

    public function testDeleteRemovesDevicesAndEventsUsersRowsAndSoftDeletesEvent(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'de-tok-3']);
        [, $idevents] = $this->createEventAsHost($host);
        $iddevices = $this->createDevice($idevents, 'fixed');

        // createEvent()'s approve=true branch causes a save() on top of the initial create(),
        // so there's real pre-existing audit history here to clear.
        $preExistingAuditIds = Audits::where('auditable_type', Party::class)->where('auditable_id', $idevents)->pluck('id');
        $this->assertNotEmpty($preExistingAuditIds);

        $response = $this->delete("/api/v2/events/$idevents?api_token=de-tok-3");

        $response->assertSuccessful();
        $this->assertNull(Device::find($iddevices));
        $this->assertEquals(0, EventsUsers::where('event', $idevents)->count());
        $this->assertNull(Party::find($idevents));
        $this->assertNotNull(Party::withTrashed()->find($idevents));

        // The endpoint clears PRE-EXISTING audit history before deleting (matching legacy
        // deleteEvent()'s ordering exactly) - none of the rows that existed before the delete
        // call should survive it. (The soft-delete itself may add its own new audit entry via
        // the Auditable trait - that's not what this assertion is about.)
        $survivingCount = Audits::where('auditable_type', Party::class)
            ->where('auditable_id', $idevents)
            ->whereIn('id', $preExistingAuditIds)
            ->count();
        $this->assertEquals(0, $survivingCount);
    }

    public function testAdministratorCanDeleteAnyEvent(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $admin = User::factory()->administrator()->create(['api_token' => 'de-tok-4']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($admin);

        $response = $this->delete("/api/v2/events/$idevents?api_token=de-tok-4");

        $response->assertSuccessful();
        $this->assertNull(Party::find($idevents));
    }
}
