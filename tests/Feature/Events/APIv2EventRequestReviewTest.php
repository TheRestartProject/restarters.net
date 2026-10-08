<?php

namespace Tests\Feature\Events;

use App\EventsUsers;
use App\Group;
use App\Notifications\EventRepairs;
use App\Party;
use App\Role;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * POST /api/v2/events/{id}/request-review — the API replacement for the old
 * "Request reviews" modal (GET /party/contribution/{id} →
 * PartyController::getContributions), which emails confirmed attendees asking
 * them to review the event's repairs.
 */
class APIv2EventRequestReviewTest extends TestCase
{
    private function makeEventWithHostAndAttendees(): array
    {
        $group = Group::factory()->create();
        $host = User::factory()->host()->create(['api_token' => 'rr-host']);
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $event = Party::factory()->create(['group' => $group->idgroups]);

        // Two confirmed restarter attendees.
        $r1 = User::factory()->restarter()->create();
        $r2 = User::factory()->restarter()->create();
        foreach ([$r1, $r2] as $r) {
            EventsUsers::create([
                'event' => $event->idevents,
                'user' => $r->id,
                'status' => 1,
                'role' => Role::RESTARTER,
            ]);
        }

        return [$event, $host, [$r1, $r2]];
    }

    public function testHostCanRequestReviewAndConfirmedRestartersAreNotified(): void
    {
        Notification::fake();
        [$event, $host, $restarters] = $this->makeEventWithHostAndAttendees();

        $this->actingAs($host);
        $response = $this->postJson('/api/v2/events/'.$event->idevents.'/request-review?api_token=rr-host');

        $response->assertSuccessful();
        $this->assertEquals(2, $response->json('data.requested'));

        Notification::assertSentTo($restarters[0], EventRepairs::class);
        Notification::assertSentTo($restarters[1], EventRepairs::class);
    }

    public function testNonHostCannotRequestReview(): void
    {
        $this->withExceptionHandling();
        Notification::fake();
        [$event] = $this->makeEventWithHostAndAttendees();

        $stranger = User::factory()->restarter()->create(['api_token' => 'rr-stranger']);
        $this->actingAs($stranger);
        $response = $this->postJson('/api/v2/events/'.$event->idevents.'/request-review?api_token=rr-stranger');

        $response->assertStatus(403);
        Notification::assertNothingSent();
    }
}
