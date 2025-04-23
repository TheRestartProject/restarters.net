<?php

namespace App\Listeners;

use App\Events\EventImagesUploaded;
use App\Helpers\Fixometer;
use App\Notifications\AdminModerationEventPhotos;
use App\Models\Party;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendAdminModerateEventPhotosNotification extends BaseEvent
{
    /**
     * Ensure uploads within this timespan (in minutes) will only result in one notification being sent
     */
    const THROTTLE_MINUTES = 15;

    /**
     * Don't notify the current user that they've uploaded photos
     */
    const IGNORE_SELF = true;

    /**
     * @var EventImagesUploaded
     */
    protected $event;

    /**
     * @var Party
     */
    protected $party;

    public function handle(EventImagesUploaded $event): void
    {
        $this->event = $event;
        $this->party = $event->party;

        if ($this->party->theGroup->archived_at) {
            // Suppress notifications for archived groups.
            return;
        }

        Fixometer::usersWhoHavePreference('admin-moderate-event-photos')->each(function (User $user) {
            if ($this->shouldSendNotification($user)) {
                $user->notify(new AdminModerationEventPhotos([
                    'event_id' => $this->party->idevents,
                    'event_venue' => $this->party->venue,
                    'event_url' => url('/party/view/'.$this->party->idevents),
                ]));
            }
        });
    }

    protected function shouldSendNotification(User $user)
    {
        if (self::IGNORE_SELF && $user->id == $this->event->auth_user_id) {
            return false;
        }

        $notifications = DB::table('notifications')->where([
            'type' => AdminModerationEventPhotos::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ])
            ->where('created_at', '>=', Carbon::now()->subMinutes(self::THROTTLE_MINUTES))
            ->get();

        foreach ($notifications as $sent) {
            $data = json_decode($sent->data);

            if (! empty($data->event_id) && $data->event_id == $this->party->idevents) {
                return false; // User just received a notification
            }
        }

        return true;
    }
}
