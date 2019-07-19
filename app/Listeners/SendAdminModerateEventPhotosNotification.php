<?php

namespace App\Listeners;

use App\Events\EventImagesUploaded;
use App\Helpers\FixometerHelper;
use App\Notifications\AdminModerationEventPhotos;
use App\Party;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendAdminModerateEventPhotosNotification
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
     * @var EventImagesUploaded $event
     */
    protected $event;

    /**
     * @var Party
     */
    protected $party;

    /**
     * @param EventImagesUploaded $event
     */
    public function handle(EventImagesUploaded $event)
    {
        $this->event = $event;
        $this->party = $event->party;

        FixometerHelper::usersWhoHavePreference('admin-moderate-event-photos')->each(function (User $user) {
            if ($this->shouldSendNotification($user)) {
                Notification::send($user, new AdminModerationEventPhotos([
                    'event_id' => $this->party->idevents,
                    'event_venue' => $this->party->venue,
                    'event_url' => url('/party/edit/' . $this->party->idevents),
                ]));
            }
        });
    }

    protected function shouldSendNotification(User $user)
    {
        if (self::IGNORE_SELF) {
            if ($user->id == $this->event->auth_user_id) {
                return false;
            }
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

            if (!empty($data->event_id) && $data->event_id == $this->party->idevents) {
                return false; // User just received a notification
            }
        }

        return true;
    }
}