<?php

namespace Tests\Feature\Events;

use App\EventsUsers;
use App\Group;
use App\Party;
use App\User;
use Tests\TestCase;

/**
 * Regression tests for the volunteer PII leak:
 * Party::expandVolunteers() previously serialised the full User Eloquent model
 * into the event page HTML, exposing api_token, calendar_hash, lat/lng, and
 * recovery tokens to anonymous visitors.
 */
class VolunteerPiiTest extends TestCase
{
    /** Sensitive field values injected into the volunteer user. */
    private string $apiToken     = 'SENTINEL_API_TOKEN_SECRET';
    private string $calendarHash = 'SENTINEL_CALENDAR_HASH';
    private string $recovery     = 'SENTINEL_RECOVERY_TOKEN';
    private float  $latitude     = 51.123456;
    private float  $longitude    = -0.123456;

    private function makeEventWithVolunteer(): array
    {
        $group     = Group::factory()->create(['approved' => true]);
        $event     = Party::factory()->create([
            'group'           => $group,
            'event_start_utc' => '2130-01-01T10:00:00+00:00',
            'event_end_utc'   => '2130-01-01T12:00:00+00:00',
        ]);
        // Force a name with no characters that change under json_encode + Blade's
        // HTML escaping. The event page embeds the attendee list as
        // `:attendance="{{ json_encode(...) }}"`, so a faker name containing an
        // apostrophe ("O'Brien" -> "O&#039;Brien") or an accent ("José" -> "José")
        // would not match the raw-name assertion below — an intermittent failure.
        $volunteer = User::factory()->restarter()->create(['name' => 'Volunteer McTestface']);

        // Bypass $fillable to inject the sentinel values directly.
        \DB::table('users')->where('id', $volunteer->id)->update([
            'api_token'     => $this->apiToken,
            'calendar_hash' => $this->calendarHash,
            'recovery'      => $this->recovery,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
        ]);

        // Confirmed attendance record (status 1 = confirmed).
        EventsUsers::create([
            'event'  => $event->idevents,
            'user'   => $volunteer->id,
            'status' => 1,
            'role'   => 3, // Restarter
        ]);

        return [$event, $volunteer];
    }

    public function testEventApiDoesNotLeakVolunteerCredentials(): void
    {
        [$event, $volunteer] = $this->makeEventWithVolunteer();

        // The anonymous attack surface moved from the (removed) Blade event
        // page to the public event API: GET /api/v2/events/{id} and its
        // /attendees list are both reachable without auth (see routes/api.php).
        foreach (["/api/v2/events/{$event->idevents}", "/api/v2/events/{$event->idevents}/attendees"] as $uri) {
            $response = $this->get($uri);
            $response->assertSuccessful();
            $body = $response->getContent();

            $this->assertStringNotContainsString($this->apiToken,           $body, "api_token must not appear in $uri");
            $this->assertStringNotContainsString($this->calendarHash,       $body, "calendar_hash must not appear in $uri");
            $this->assertStringNotContainsString($this->recovery,           $body, "recovery token must not appear in $uri");
            $this->assertStringNotContainsString((string) $this->latitude,  $body, "latitude must not appear in $uri");
            $this->assertStringNotContainsString((string) $this->longitude, $body, "longitude must not appear in $uri");
        }

        // Sanity: the volunteer's name is still exposed (the attendee list
        // itself is not broken by the PII scrubbing).
        $attendees = $this->get("/api/v2/events/{$event->idevents}/attendees");
        $this->assertStringContainsString($volunteer->name, $attendees->getContent(), 'Volunteer name must still appear in the attendees API');
    }

    public function testUserModelHiddenFieldsExcludeCredentials(): void
    {
        $user = User::factory()->restarter()->create();

        \DB::table('users')->where('id', $user->id)->update([
            'api_token'       => $this->apiToken,
            'calendar_hash'   => $this->calendarHash,
            'recovery'        => $this->recovery,
            'recovery_expires'=> '2099-01-01',
            'latitude'        => $this->latitude,
            'longitude'       => $this->longitude,
        ]);

        $user->refresh();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('api_token',        $array, 'api_token must be in $hidden');
        $this->assertArrayNotHasKey('calendar_hash',    $array, 'calendar_hash must be in $hidden');
        $this->assertArrayNotHasKey('recovery',         $array, 'recovery must be in $hidden');
        $this->assertArrayNotHasKey('recovery_expires', $array, 'recovery_expires must be in $hidden');
        $this->assertArrayNotHasKey('latitude',         $array, 'latitude must be in $hidden');
        $this->assertArrayNotHasKey('longitude',        $array, 'longitude must be in $hidden');
        $this->assertArrayNotHasKey('mediawiki',        $array, 'mediawiki must be in $hidden');

        // Non-sensitive fields must still be present.
        $this->assertArrayHasKey('name',  $array);
        $this->assertArrayHasKey('email', $array);
    }

    public function testExpandVolunteersEmitsSafeShape(): void
    {
        [$event, $volunteer] = $this->makeEventWithVolunteer();

        $volunteers = $event->allConfirmedVolunteers()->get();
        $expanded   = Party::expandVolunteers($volunteers, false);

        $this->assertNotEmpty($expanded, 'expandVolunteers must return the volunteer');

        // Decode via json_encode/decode — this is the actual attack surface (blade page HTML).
        // PHP array-access reads $attributes, but json_encode calls toArray() which merges
        // relationsToArray() LAST, so a still-loaded 'volunteer' HasOne relation would
        // silently overwrite the safe array.  Decoding the JSON catches that bypass.
        $decoded = json_decode(json_encode($expanded), true);
        $ev = $decoded[0];

        // The nested 'volunteer' key must be an array (not an Eloquent model).
        $this->assertIsArray($ev['volunteer'], "'volunteer' must be a plain array, not an Eloquent model");

        // Safe fields present.
        $this->assertArrayHasKey('id',   $ev['volunteer']);
        $this->assertArrayHasKey('name', $ev['volunteer']);

        // Sensitive fields absent — checked in the JSON-decoded output (real attack surface).
        $this->assertArrayNotHasKey('api_token',     $ev['volunteer'], 'api_token must not be in expanded volunteer JSON');
        $this->assertArrayNotHasKey('calendar_hash', $ev['volunteer'], 'calendar_hash must not be in expanded volunteer JSON');
        $this->assertArrayNotHasKey('recovery',      $ev['volunteer'], 'recovery must not be in expanded volunteer JSON');
        $this->assertArrayNotHasKey('latitude',      $ev['volunteer'], 'latitude must not be in expanded volunteer JSON');
        $this->assertArrayNotHasKey('longitude',     $ev['volunteer'], 'longitude must not be in expanded volunteer JSON');

        // Sentinel values must not appear anywhere in the raw JSON string.
        $json = json_encode($expanded);
        $this->assertStringNotContainsString($this->apiToken,     $json, 'api_token sentinel must not appear in JSON output');
        $this->assertStringNotContainsString($this->calendarHash, $json, 'calendar_hash sentinel must not appear in JSON output');
        $this->assertStringNotContainsString($this->recovery,     $json, 'recovery sentinel must not appear in JSON output');

        // Email must be null when showEmails=false.
        $this->assertNull($ev['volunteer']['email'], 'email must be null when showEmails is false');
    }
}
