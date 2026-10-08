<?php

namespace Tests\Feature\Events;

use App\EventsUsers;
use App\Invite;
use App\Notifications\JoinEvent;
use App\Party;
use App\Role;
use App\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2EventVolunteersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createEventAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Volunteers Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);

        return [$idgroups, $idevents];
    }

    // --- PATCH volunteer (host toggle) ---

    public function testPatchVolunteerRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);
        $restarter = User::factory()->restarter()->create();

        $response = $this->patchJson("/api/v2/events/$idevents/volunteers/{$restarter->id}", ['host' => true]);

        $response->assertStatus(401);
    }

    public function testPatchVolunteerDeniedForRestarterWithoutPermission(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'pv-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=pv-tok-1")->assertSuccessful();

        $response = $this->patch("/api/v2/events/$idevents/volunteers/{$restarter->id}?api_token=pv-tok-1", ['host' => true]);

        $response->assertStatus(403);
    }

    public function testHostCanPromoteVolunteerToHost(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'pv-tok-host']);
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'pv-tok-restarter']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=pv-tok-restarter")->assertSuccessful();

        $this->app['auth']->forgetGuards();
        $this->actingAs($host);
        $response = $this->patch("/api/v2/events/$idevents/volunteers/{$restarter->id}?api_token=pv-tok-host", ['host' => true]);

        $response->assertSuccessful();
        $this->assertEquals(['host' => true], $response->json('data'));

        $member = EventsUsers::where('event', $idevents)->where('user', $restarter->id)->first();
        $this->assertEquals(Role::HOST, $member->role);
    }

    public function testHostCanDemoteHostToRestarter(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'pv-tok-host2']);
        [, $idevents] = $this->createEventAsHost($host);

        $otherHost = User::factory()->restarter()->create(['api_token' => 'pv-tok-other']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($otherHost);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=pv-tok-other")->assertSuccessful();

        $this->app['auth']->forgetGuards();
        $this->actingAs($host);
        $this->patch("/api/v2/events/$idevents/volunteers/{$otherHost->id}?api_token=pv-tok-host2", ['host' => true])->assertSuccessful();

        $response = $this->patch("/api/v2/events/$idevents/volunteers/{$otherHost->id}?api_token=pv-tok-host2", ['host' => false]);

        $response->assertSuccessful();
        $member = EventsUsers::where('event', $idevents)->where('user', $otherHost->id)->first();
        $this->assertEquals(Role::RESTARTER, $member->role);
    }

    public function testPatchVolunteerUnknownEventReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'pv-tok-404e']);
        $restarter = User::factory()->restarter()->create();
        $this->actingAs($admin);

        $response = $this->patch("/api/v2/events/999999/volunteers/{$restarter->id}?api_token=pv-tok-404e", ['host' => true]);

        $response->assertStatus(404);
    }

    // --- DELETE volunteer (keyed by idevents_users) ---

    public function testDeleteVolunteerRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);
        $row = EventsUsers::where('event', $idevents)->first();

        $response = $this->deleteJson("/api/v2/events/$idevents/volunteers/{$row->idevents_users}");

        $response->assertStatus(401);
    }

    public function testDeleteVolunteerDeniedForRestarterWithoutPermission(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);
        $row = EventsUsers::where('event', $idevents)->first();

        $restarter = User::factory()->restarter()->create(['api_token' => 'dv-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->delete("/api/v2/events/$idevents/volunteers/{$row->idevents_users}?api_token=dv-tok-1");

        $response->assertStatus(403);
        $this->assertNotNull(EventsUsers::find($row->idevents_users));
    }

    public function testDeleteVolunteerRemovesRowById(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dv-tok-host']);
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'dv-tok-restarter']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=dv-tok-restarter")->assertSuccessful();
        $row = EventsUsers::where('event', $idevents)->where('user', $restarter->id)->first();

        $this->app['auth']->forgetGuards();
        $this->actingAs($host);
        $response = $this->delete("/api/v2/events/$idevents/volunteers/{$row->idevents_users}?api_token=dv-tok-host");

        $response->assertSuccessful();
        $this->assertEquals(['deleted' => true], $response->json('data'));
        $this->assertNull(EventsUsers::find($row->idevents_users));
    }

    public function testDeleteVolunteerHandlesManuallyAddedRowWithNoUser(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dv-tok-host2']);
        [, $idevents] = $this->createEventAsHost($host);

        $row = EventsUsers::create([
            'event' => $idevents,
            'user' => null,
            'status' => '1',
            'role' => Role::RESTARTER,
            'full_name' => 'Anonymous Helper',
        ]);

        $response = $this->delete("/api/v2/events/$idevents/volunteers/{$row->idevents_users}?api_token=dv-tok-host2");

        $response->assertSuccessful();
        $this->assertNull(EventsUsers::find($row->idevents_users));
    }

    public function testDeleteVolunteerIsIdempotentForUnknownRow(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'dv-tok-host3']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->delete("/api/v2/events/$idevents/volunteers/999999?api_token=dv-tok-host3");

        $response->assertSuccessful();
        $this->assertEquals(['deleted' => false], $response->json('data'));
    }

    // --- Invites ---

    public function testInvitesRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->postJson("/api/v2/events/$idevents/invites", ['emails' => ['a@example.com']]);

        $response->assertStatus(401);
    }

    public function testInvitesDeniedForRestarterWithoutPermission(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'inv-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->post("/api/v2/events/$idevents/invites?api_token=inv-tok-1", ['emails' => ['a@example.com']]);

        $response->assertStatus(403);
    }

    public function testInviteExistingUserCreatesHashInviteAndSendsNotification(): void
    {
        Notification::fake();

        $host = User::factory()->host()->create(['api_token' => 'inv-tok-host']);
        [, $idevents] = $this->createEventAsHost($host);

        $existingUser = User::factory()->restarter()->create();

        $response = $this->post("/api/v2/events/$idevents/invites?api_token=inv-tok-host", [
            'emails' => [$existingUser->email],
            'message' => 'Come along!',
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.invites_sent'));
        $this->assertEquals([], $response->json('data.invalid'));

        $row = EventsUsers::where('event', $idevents)->where('user', $existingUser->id)->first();
        $this->assertNotNull($row);
        $this->assertNotEquals('1', (string) $row->status);

        Notification::assertSentTo($existingUser, JoinEvent::class);
    }

    public function testInviteUnknownEmailCreatesInviteRow(): void
    {
        Notification::fake();

        $host = User::factory()->host()->create(['api_token' => 'inv-tok-host2']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->post("/api/v2/events/$idevents/invites?api_token=inv-tok-host2", [
            'emails' => ['stranger@example.com'],
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.invites_sent'));

        $invite = Invite::where('email', 'stranger@example.com')->where('type', 'event')->where('record_id', $idevents)->first();
        $this->assertNotNull($invite);
    }

    public function testInviteSkipsAlreadyConfirmedUser(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'inv-tok-host3']);
        [, $idevents] = $this->createEventAsHost($host);

        $confirmed = User::factory()->restarter()->create(['api_token' => 'inv-tok-confirmed']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($confirmed);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=inv-tok-confirmed")->assertSuccessful();
        $statusBefore = EventsUsers::where('event', $idevents)->where('user', $confirmed->id)->first()->status;

        $this->app['auth']->forgetGuards();
        $this->actingAs($host);
        $response = $this->post("/api/v2/events/$idevents/invites?api_token=inv-tok-host3", [
            'emails' => [$confirmed->email],
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.invites_sent'));

        // Status untouched - still confirmed, not overwritten with a hash.
        $this->assertEquals($statusBefore, EventsUsers::where('event', $idevents)->where('user', $confirmed->id)->first()->status);
    }

    public function testInviteReportsMalformedAddressesAsInvalid(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'inv-tok-host4']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->post("/api/v2/events/$idevents/invites?api_token=inv-tok-host4", [
            'emails' => ['not-an-email', 'also bad'],
        ]);

        $response->assertSuccessful();
        $this->assertEquals(0, $response->json('data.invites_sent'));
        $this->assertEquals(['not-an-email', 'also bad'], $response->json('data.invalid'));
    }

    // --- Headcount counters folded into PATCH /api/v2/events/{id} ---

    public function testHeadcountParticipantsAndVolunteersUpdateViaPatchEvent(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'hc-tok-host']);
        [, $idevents] = $this->createEventAsHost($host);

        $atts = $this->eventAttributesToAPI(Party::find($idevents)->getAttributes());
        $atts['participants'] = 12;
        $atts['volunteers'] = 3;

        $response = $this->patch("/api/v2/events/$idevents?api_token=hc-tok-host", $atts);

        $response->assertSuccessful();
        $party = Party::find($idevents);
        $this->assertEquals(12, $party->pax);
        $this->assertEquals(3, $party->volunteers);
    }

    public function testHeadcountUpdateDeniedForRestarterWithoutPermission(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'hc-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $atts = $this->eventAttributesToAPI(Party::find($idevents)->getAttributes());
        $atts['participants'] = 99;

        $response = $this->patch("/api/v2/events/$idevents?api_token=hc-tok-1", $atts);

        $response->assertStatus(403);
        $this->assertNotEquals(99, Party::find($idevents)->pax);
    }
}
