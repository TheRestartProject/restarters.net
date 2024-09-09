<?php

namespace App\Listeners;

use App\Events\EventDeleted;
use App\Helpers\Fixometer;
use App\Notifications\DeleteEventFromWordpressFailed;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Log;
use Notification;

class DeleteEventFromWordPress extends BaseEvent
{
    protected $wpClient;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(WordpressClient $wpClient)
    {
        $this->wpClient = $wpClient;
    }

    /**
     * Handle the event.
     *
     * @param  EventDeleted  $event
     * @return void
     */
    public function handle(EventDeleted $event)
    {
        // Slightly confusing name clash - usually we refer to community repair events as 'events' in the system.  Here explicitly calling it repairEvent.

        $repairEvent = $event->repairEvent;

        if ($repairEvent->group->archived_at) {
            // Suppress notifications for archived groups.
            return;
        }

        try {
            if ($repairEvent->shouldPushToWordPress() && ! empty($repairEvent->wordpress_post_id)) {
                $this->wpClient->deletePost($repairEvent->wordpress_post_id);
            }
        } catch (\Exception $ex) {
            Log::error('An error occurred during Wordpress event deletion: '.$ex->getMessage());

            $usersToNotify = Fixometer::usersWhoHavePreference('delete-event-notification');

            Notification::send($usersToNotify, new DeleteEventFromWordpressFailed([
                'event_venue' => $repairEvent->venue,
                'group_name' => $repairEvent->theGroup->name,
            ]));
        }
    }
}
