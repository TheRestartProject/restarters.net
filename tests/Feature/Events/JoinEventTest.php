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

class JoinEventTest extends TestCase
{
    public function testJoin()
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
        $event->save();

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) use ($event, $user) {
            if ($job->class == AddUserToDiscourseThreadForEvent::class) {
                return true;
            }
        });

        // Join.  Should get redirected, and also prompted to follow the group (which we haven't).
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);
        $response = $this->get('/party/join/'.$event->idevents);
        $this->assertTrue($response->isRedirection());
        $response->assertSessionHas('prompt-follow-group');
        $attending = EventsUsers::where('event', $event->idevents)->where('user', $user->id)->get()->first();
        $this->assertEquals($user->id, $attending->user);

        // Should now show attendance on the event page.
        $response = $this->get('/party/view/'.$event->idevents);
        $this->assertVueProperties($response, [
            [],
            [
                ':is-attending' => 'true',
            ],
        ]);

        // Say we can't attend.
        $this->followingRedirects();
        $response = $this->get('/party/cancel-invite/'.$event->idevents);
        $this->assertVueProperties($response, [
            [],
            [
                ':is-attending' => 'false',
            ],
        ]);

        Queue::assertPushed(\Illuminate\Events\CallQueuedListener::class, function ($job) use ($event, $user) {
            if ($job->class == RemoveUserFromDiscourseThreadForEvent::class) {
                return true;
            }
        });
    }

    public function testJoinInvalid() {
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->get('/party/join/-1');
        $response->assertSessionHas('danger');
        $this->assertTrue($response->isRedirection());
    }
}
