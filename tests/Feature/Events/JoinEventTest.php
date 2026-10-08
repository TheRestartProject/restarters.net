<?php

namespace Tests\Feature;

use App\Listeners\RemoveUserFromDiscourseThreadForEvent;
use Illuminate\Support\Facades\Queue;
use App\EventsUsers;
use App\Listeners\AddUserToDiscourseThreadForEvent;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use function PHPUnit\Framework\assertEquals;

class JoinEventTest extends TestCase
{
    public function testJoin(): void
    {
        Queue::fake();

        $this->withoutExceptionHandling();

        $user = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup('Test Group', 'https://therestartproject.org', 'London', 'Some text.', true, true);
        $idevents = $this->createEvent($idgroups, 'tomorrow');

        // Joining should trigger adding to the Discourse thread.  Fake one.
        $event = \App\Party::find($idevents);
        $event->discourse_thread = 123;
        assertEquals(1, $event->volunteers);
        $event->save();

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) use ($event, $user) {
            if ($job->class == AddUserToDiscourseThreadForEvent::class) {
                return true;
            }
        });

        // Join.  Should be marked as attending, and also prompted to follow the group (which we haven't).
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);
        $response = $this->post('/api/v2/events/'.$event->idevents.'/attendees/me?api_token='.$user->api_token);
        $response->assertSuccessful();
        $this->assertTrue($response->json('data.prompt_follow_group'));
        $attending = EventsUsers::where('event', $event->idevents)->where('user', $user->id)->get()->first();
        $this->assertEquals($user->id, $attending->user);

        // Should now show attendance on the event.
        $response = $this->get('/api/v2/events/'.$event->idevents);
        $response->assertSuccessful();
        $this->assertTrue($response->json('data.attending'));

        // Should show in count
        $event->refresh();
        assertEquals(2, $event->volunteers);

        // Say we can't attend.
        $response = $this->delete('/api/v2/events/'.$event->idevents.'/attendees/me?api_token='.$user->api_token);
        $response->assertSuccessful();

        $response = $this->get('/api/v2/events/'.$event->idevents);
        $response->assertSuccessful();
        $this->assertFalse($response->json('data.attending'));

        $event->refresh();
        assertEquals(1, $event->volunteers);

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) use ($event, $user) {
            if ($job->class == RemoveUserFromDiscourseThreadForEvent::class) {
                return true;
            }
        });
    }

    public function testJoinInvalid(): void {
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->post('/api/v2/events/-1/attendees/me?api_token='.$user->api_token);
    }
}
