<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Geocoder;
use App\Network;
use App\Notifications\AdminModerationEvent;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JoinEventTest extends TestCase
{
    public function testJoin()
    {
        $this->withoutExceptionHandling();

        $group = Group::factory()->create([
                                              'approved' => true
                                           ]);
        $event = Party::factory()->create(['group' => $group->idgroups]);

        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        // Join.  Should get redirected, and also prompted to follow the group (which we haven't).
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
