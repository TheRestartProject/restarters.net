<?php

namespace Tests\Feature\Events;

use App\EventsUsers;
use App\Role;
use App\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2EventAttendeesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createEventAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Attendees Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);

        return [$idgroups, $idevents];
    }

    public function testAttendeesUnknownEventReturns404(): void
    {
        $response = $this->getJson('/api/v2/events/999999/attendees');

        $response->assertStatus(404);
    }

    public function testConfirmedListContainsHostAndHidesEmailForAnonymousCaller(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $this->app['auth']->forgetGuards();

        $response = $this->get("/api/v2/events/$idevents/attendees");

        $response->assertSuccessful();
        $confirmed = $response->json('data.confirmed');
        $this->assertCount(1, $confirmed);
        $this->assertEquals($host->id, $confirmed[0]['user']);
        $this->assertEquals(Role::HOST, $confirmed[0]['role']);
        $this->assertTrue($confirmed[0]['confirmed']);
        $this->assertArrayHasKey('volunteer', $confirmed[0]);
        // Party::expandVolunteers() always emits the 'email' key - it's null rather than
        // omitted when the caller lacks edit-party permission.
        $this->assertNull($confirmed[0]['volunteer']['email']);
        $this->assertEquals([], $response->json('data.invited'));
    }

    public function testConfirmedShowsEmailForCallerWithEditPartyPermission(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'attendees-tok-1']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->get("/api/v2/events/$idevents/attendees?api_token=attendees-tok-1");

        $response->assertSuccessful();
        $confirmed = $response->json('data.confirmed');
        $this->assertEquals($host->email, $confirmed[0]['volunteer']['email']);
    }

    public function testInvitedListsPendingInviteWithConfirmedFalseAndNoEmailLeak(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $invitee = User::factory()->restarter()->create();
        EventsUsers::create([
            'event' => $idevents,
            'user' => $invitee->id,
            'status' => 'somehash1234567890abcdef',
            'role' => Role::RESTARTER,
        ]);

        $this->app['auth']->forgetGuards();
        $response = $this->get("/api/v2/events/$idevents/attendees");

        $response->assertSuccessful();
        $invited = $response->json('data.invited');
        $this->assertCount(1, $invited);
        $this->assertEquals($invitee->id, $invited[0]['user']);
        $this->assertFalse($invited[0]['confirmed']);
        $this->assertNull($invited[0]['volunteer']['email']);

        // The raw status hash must not be surfaced anywhere in the row.
        $this->assertStringNotContainsString('somehash1234567890abcdef', json_encode($invited[0]));
    }

    public function testConfirmedListIsNotTruncated(): void
    {
        $host = User::factory()->host()->create();
        [$idgroups, $idevents] = $this->createEventAsHost($host);

        // The legacy Blade view truncates confirmed/invited to 5-6 rows for perf; the v2
        // endpoint must return the full list (docs/nuxt-migration/api-contracts-phase-c.md#C1b).
        for ($i = 0; $i < 8; $i++) {
            $volunteer = User::factory()->restarter()->create();
            EventsUsers::create([
                'event' => $idevents,
                'user' => $volunteer->id,
                'status' => '1',
                'role' => Role::RESTARTER,
            ]);
        }

        $this->app['auth']->forgetGuards();
        $response = $this->get("/api/v2/events/$idevents/attendees");

        $response->assertSuccessful();
        // 8 volunteers + the host.
        $this->assertCount(9, $response->json('data.confirmed'));
    }

    public function testManuallyAddedVolunteerWithNoUserHasNullUserAndNoVolunteerBlock(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        EventsUsers::create([
            'event' => $idevents,
            'user' => null,
            'status' => '1',
            'role' => Role::RESTARTER,
            'full_name' => 'Anonymous Helper',
        ]);

        $this->app['auth']->forgetGuards();
        $response = $this->get("/api/v2/events/$idevents/attendees");

        $response->assertSuccessful();
        $confirmed = $response->json('data.confirmed');
        $manual = collect($confirmed)->firstWhere('fullName', 'Anonymous Helper');
        $this->assertNotNull($manual);
        $this->assertNull($manual['user']);
        $this->assertArrayNotHasKey('volunteer', $manual);
    }
}
