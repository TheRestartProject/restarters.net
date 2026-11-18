<?php

namespace Tests\Feature\Events;

use App\EventsUsers;
use App\Notifications\RSVPEvent;
use App\Role;
use App\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2EventRsvpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createEventAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Rsvp Test Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);

        return [$idgroups, $idevents];
    }

    // --- RSVP (POST) ---

    public function testRsvpRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->postJson("/api/v2/events/$idevents/attendees/me");

        $response->assertStatus(401);
    }

    public function testRsvpUnknownEventReturns404(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'rsvp-tok-404']);
        $this->actingAs($user);

        $response = $this->post('/api/v2/events/999999/attendees/me?api_token=rsvp-tok-404');

        $response->assertStatus(404);
    }

    public function testRsvpCreatesConfirmedAttendanceAndNotifiesHosts(): void
    {
        Notification::fake();

        $host = User::factory()->host()->create(['api_token' => 'rsvp-tok-host']);
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'rsvp-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->post("/api/v2/events/$idevents/attendees/me?api_token=rsvp-tok-1");

        $response->assertSuccessful();
        $this->assertEquals([
            'attending' => true,
            'already_attending' => false,
            'prompt_follow_group' => true,
        ], $response->json('data'));

        $member = EventsUsers::where('event', $idevents)->where('user', $restarter->id)->first();
        $this->assertNotNull($member);
        $this->assertEquals('1', (string) $member->status);
        $this->assertEquals(Role::RESTARTER, $member->role);

        Notification::assertSentTo($host, RSVPEvent::class);
    }

    public function testRsvpTwiceReportsAlreadyAttending(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'rsvp-tok-host2']);
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'rsvp-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $first = $this->post("/api/v2/events/$idevents/attendees/me?api_token=rsvp-tok-2");
        $first->assertSuccessful();
        $this->assertFalse($first->json('data.already_attending'));

        $second = $this->post("/api/v2/events/$idevents/attendees/me?api_token=rsvp-tok-2");
        $second->assertSuccessful();
        $this->assertTrue($second->json('data.already_attending'));

        $this->assertEquals(1, EventsUsers::where('event', $idevents)->where('user', $restarter->id)->count());
    }

    public function testRsvpResolvesPendingHashInviteWithoutClientKnowingHash(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'rsvp-tok-host3']);
        [, $idevents] = $this->createEventAsHost($host);

        $invitee = User::factory()->restarter()->create(['api_token' => 'rsvp-tok-3']);
        EventsUsers::create([
            'event' => $idevents,
            'user' => $invitee->id,
            'status' => 'pendinghash1234567890ab',
            'role' => Role::RESTARTER,
        ]);

        $this->app['auth']->forgetGuards();
        $this->actingAs($invitee);

        $response = $this->post("/api/v2/events/$idevents/attendees/me?api_token=rsvp-tok-3");

        $response->assertSuccessful();
        $this->assertFalse($response->json('data.already_attending'));

        // The pending hash-status row was resolved into a confirmed one, not duplicated.
        $this->assertEquals(1, EventsUsers::where('event', $idevents)->where('user', $invitee->id)->count());
        $member = EventsUsers::where('event', $idevents)->where('user', $invitee->id)->first();
        $this->assertEquals('1', (string) $member->status);
    }

    public function testRsvpDoesNotPromptFollowGroupWhenAlreadyAMember(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'rsvp-tok-host4']);
        [$idgroups, $idevents] = $this->createEventAsHost($host);

        $member = User::factory()->restarter()->create(['api_token' => 'rsvp-tok-4']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($member);
        $this->post("/api/v2/groups/$idgroups/members/me?api_token=rsvp-tok-4")->assertSuccessful();

        $response = $this->post("/api/v2/events/$idevents/attendees/me?api_token=rsvp-tok-4");

        $response->assertSuccessful();
        $this->assertFalse($response->json('data.prompt_follow_group'));
    }

    // --- Cancel RSVP (DELETE) ---

    public function testCancelRsvpRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->deleteJson("/api/v2/events/$idevents/attendees/me");

        $response->assertStatus(401);
    }

    public function testCancelRsvpRemovesAttendance(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'cancel-tok-host']);
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'cancel-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=cancel-tok-1")->assertSuccessful();

        $response = $this->delete("/api/v2/events/$idevents/attendees/me?api_token=cancel-tok-1");

        $response->assertSuccessful();
        $this->assertEquals(['left' => true], $response->json('data'));
        $this->assertEquals(0, EventsUsers::where('event', $idevents)->where('user', $restarter->id)->count());
    }

    public function testCancelRsvpIsIdempotentWhenNotAttending(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'cancel-tok-host2']);
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'cancel-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->delete("/api/v2/events/$idevents/attendees/me?api_token=cancel-tok-2");

        $response->assertSuccessful();
        $this->assertEquals(['left' => true], $response->json('data'));
    }
}
