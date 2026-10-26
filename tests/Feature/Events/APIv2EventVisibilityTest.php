<?php

namespace Tests\Feature\Events;

use App\Group;
use App\Party;
use App\Role;
use App\User;
use Tests\TestCase;

/**
 * Events on unapproved (unmoderated) groups must not be readable by the
 * public - restores the legacy PartyController::view() gate
 * (Fixometer::userHasViewPartyPermission) that the API-only cutover dropped
 * from GET /api/v2/events/{id}, /attendees and /devices. Events on approved
 * groups stay fully public.
 */
class APIv2EventVisibilityTest extends TestCase
{
    public function testUnapprovedGroupEventIsHiddenFromUnprivilegedUsers(): void
    {
        // Host creates an event on an UNAPPROVED group ($approve = false).
        $host = $this->loginAsTestUser(Role::HOST);
        $group = $this->createGroup('Unapproved Group', 'https://therestartproject.org', 'London', 'Some text.', true, false);
        $event = $this->createEvent($group, 'tomorrow');

        // The host can still see their own unapproved-group event.
        $this->get("/api/v2/events/$event")->assertSuccessful();

        // A different, unprivileged user cannot see it until it's moderated.
        $this->loginAsTestUser(Role::RESTARTER);
        $this->withExceptionHandling();
        $this->getJson("/api/v2/events/$event")->assertStatus(404);
        $this->getJson("/api/v2/events/$event/attendees")->assertStatus(404);
        $this->getJson("/api/v2/events/$event/devices")->assertStatus(404);
    }

    public function testApprovedGroupEventIsPublic(): void
    {
        // Host creates an event on an APPROVED group (createGroup default).
        $this->loginAsTestUser(Role::HOST);
        $group = $this->createGroup();
        $event = $this->createEvent($group, 'tomorrow');

        // Any user (here an unrelated Restarter) can read an approved-group
        // event and its attendees/devices - the gate must not touch this.
        $this->loginAsTestUser(Role::RESTARTER);
        $this->get("/api/v2/events/$event")->assertSuccessful();
        $this->get("/api/v2/events/$event/attendees")->assertSuccessful();
        $this->get("/api/v2/events/$event/devices")->assertSuccessful();
    }

    public function testUnapprovedGroupEventVisibleToAdminViaBearerToken(): void
    {
        // The gate must resolve the acting user across guards, not just the
        // default 'web' guard: the SPA authenticates with a sanctum bearer
        // token, so $request->user() alone (web only) would miss it and 404 an
        // admin moderating an unapproved-group event - the exact e2e device-
        // moderation flow. No actingAs here: the admin is authenticated ONLY by
        // the bearer token, on an unapproved group, so a 200 proves the token
        // was resolved (the old $request->user() path returns null -> 404).
        // (The anonymous/unprivileged 404 case is covered by the test above.)
        $group = Group::factory()->create(['approved' => false]);
        $event = Party::factory()->create(['group' => $group->idgroups]);

        $admin = User::factory()->administrator()->create();
        $token = $admin->createToken('spa')->plainTextToken;

        $this->getJson('/api/v2/events/'.$event->idevents, ['Authorization' => 'Bearer '.$token])
             ->assertSuccessful();
    }
}
