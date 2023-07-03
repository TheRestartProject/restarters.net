<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Geocoder;
use App\Network;
use App\Notifications\AdminModerationEvent;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\Services\DiscourseService;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JoinEventTest extends TestCase
{
    public function testJoin()
    {
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
        $this->instance(
            DiscourseService::class,
            \Mockery::mock(DiscourseService::class, function ($mock) {
                $mock->shouldReceive('addUserToPrivateMessage')->once();
            })
        );

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
    }

    public function testJoinInvalid() {
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $response = $this->get('/party/join/-1');
        $response->assertSessionHas('danger');
        $this->assertTrue($response->isRedirection());
    }
}
