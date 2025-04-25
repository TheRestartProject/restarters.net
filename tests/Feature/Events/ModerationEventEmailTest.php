<?php

namespace Tests\Feature;

use App\Notifications\AdminModerationEvent;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ModerationEventEmailTest extends TestCase
{
    #[Test]
    public function a_moderation_email_is_sent_to_admins_when_an_event_is_created(): void
    {
        Notification::fake();

        $admins = User::factory()->count(5)->administrator()->create();

        $event = Party::factory()->create();

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
