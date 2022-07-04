<?php

namespace Tests\Feature;

use App\Notifications\AdminModerationEvent;
use App\Party;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ModerationEventEmailTest extends TestCase
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

        Notification::send($admins, new AdminModerationEvent($arr));

        Notification::assertSentTo(
            [$admins], AdminModerationEvent::class
        );
    }
}
