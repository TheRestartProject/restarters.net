<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Notifications\ModerationEvent;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

use App\Party;
use App\User;

class ModerateEventEmailTest extends TestCase
{
    /** @test */
    public function a_moderation_email_is_sent_to_admins_when_an_event_is_created()
    {
        Notification::fake();

        $admins = factory(User::class, 5)->states('Administrator')->create();

        $event = factory(Party::class)->create();

        $arr = [
            'event_venue' => $event->venue,
            'event_url' => url('/party/view/'.$event->id),
        ];

        Notification::send($admins, new ModerationEvent($arr));

        Notification::assertSentTo(
            [$admins], ModerationEvent::class
        );
    }
}
