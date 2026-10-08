<?php

namespace Tests\Feature\Events;

use App\Party;
use App\User;
use Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the discourse_thread field added to app/Http/Resources/Party.php for the Nuxt client's
 * event page (parity-v2/events.md gap 11). See APIv2EventImagesTest for the images field (gap 1)
 * - that file already has the tus-upload plumbing this would otherwise duplicate.
 */
class APIv2EventResourceFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createEventAsHost(User $host): array
    {
        $this->actingAs($host);
        $idgroups = $this->createGroup('Event Resource Fields Group '.Str::random(8), 'https://therestartproject.org', 'London');
        $idevents = $this->createEvent($idgroups, '+1 week', true, true);

        return [$idgroups, $idevents];
    }

    public function testShareableLinkVisibleToHost(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'erf-tok-sl1']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->get("/api/v2/events/$idevents?api_token=erf-tok-sl1");
        $response->assertSuccessful();

        $link = $response->json('data.shareable_link');
        $this->assertNotNull($link);
        $this->assertStringContainsString('/party/invite/', $link);
    }

    public function testShareableLinkAbsentForNonHost(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'erf-tok-sl2']);
        [, $idevents] = $this->createEventAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'erf-tok-sl3']);
        $this->actingAs($restarter);

        $response = $this->get("/api/v2/events/$idevents?api_token=erf-tok-sl3");
        $response->assertSuccessful();

        // Absent, not null: the invite link is only ever rendered in the
        // host-only invite modal, so a restarter has no use for it.
        $this->assertArrayNotHasKey('shareable_link', $response->json('data'));
    }

    public function testShareableLinkAbsentForAnonymousUser(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'erf-tok-sl4']);
        [, $idevents] = $this->createEventAsHost($host);

        Auth::logout();
        $response = $this->get("/api/v2/events/$idevents");
        $response->assertSuccessful();

        $this->assertArrayNotHasKey('shareable_link', $response->json('data'));
    }

    public function testDiscourseThreadNullWhenNotLinked(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'erf-tok-1']);
        [, $idevents] = $this->createEventAsHost($host);

        $response = $this->get("/api/v2/events/$idevents?api_token=erf-tok-1");
        $response->assertSuccessful();

        $this->assertNull($response->json('data.discourse_thread'));
    }

    public function testDiscourseThreadNullForAnonymousUserEvenWhenLinked(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);
        Party::find($idevents)->update(['discourse_thread' => '4821']);
        Auth::logout();

        $response = $this->get("/api/v2/events/$idevents");
        $response->assertSuccessful();

        $this->assertNull($response->json('data.discourse_thread'));
    }

    public function testDiscourseThreadNullForNonAttendeeEvenWhenLinked(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);
        Party::find($idevents)->update(['discourse_thread' => '4821']);

        $stranger = User::factory()->restarter()->create(['api_token' => 'erf-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($stranger);

        $response = $this->get("/api/v2/events/$idevents?api_token=erf-tok-2");
        $response->assertSuccessful();

        $this->assertNull($response->json('data.discourse_thread'));
    }

    public function testDiscourseThreadReturnedForConfirmedAttendee(): void
    {
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);
        Party::find($idevents)->update(['discourse_thread' => '4821']);

        $attendee = User::factory()->restarter()->create(['api_token' => 'erf-tok-3']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($attendee);
        $this->post("/api/v2/events/$idevents/attendees/me?api_token=erf-tok-3")->assertSuccessful();

        $response = $this->get("/api/v2/events/$idevents?api_token=erf-tok-3");
        $response->assertSuccessful();

        $this->assertEquals('4821', $response->json('data.discourse_thread'));
    }

    public function testDiscourseThreadReturnedForTheCreatingHost(): void
    {
        // createEventv2 auto-adds the creating host as a confirmed (status===1) attendee, so the
        // host sees the thread immediately - via the same attendance check as anyone else, not a
        // host-specific bypass.
        $host = User::factory()->host()->create(['api_token' => 'erf-tok-4']);
        [, $idevents] = $this->createEventAsHost($host);
        Party::find($idevents)->update(['discourse_thread' => '4821']);

        $response = $this->get("/api/v2/events/$idevents?api_token=erf-tok-4");
        $response->assertSuccessful();

        $this->assertEquals('4821', $response->json('data.discourse_thread'));
    }

    public function testDiscourseThreadNullForAdministratorWhoIsNotAttending(): void
    {
        // develop's gate ($is_attending && $event->discourse_thread) has no special case for
        // hosts/admins/coordinators - it's attendance-only. An Administrator who hasn't RSVP'd
        // should not see it, even though they could edit/moderate the event.
        $host = User::factory()->host()->create();
        [, $idevents] = $this->createEventAsHost($host);
        Party::find($idevents)->update(['discourse_thread' => '4821']);

        $admin = User::factory()->administrator()->create(['api_token' => 'erf-tok-5']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($admin);

        $response = $this->get("/api/v2/events/$idevents?api_token=erf-tok-5");
        $response->assertSuccessful();

        $this->assertNull($response->json('data.discourse_thread'));
    }
}
