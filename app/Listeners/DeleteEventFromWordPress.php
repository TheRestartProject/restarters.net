<?php

namespace App\Listeners;

use App\Events\EventDeleted;
use App\Notifications\DeleteEventFromWordpressFailed;
use FixometerHelper;

use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Notification;

class DeleteEventFromWordPress
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

        try {
            if ($repairEvent->shouldPushToWordPress() && !empty($repairEvent->wordpress_post_id)) {
                $this->wpClient->deletePost($repairEvent->wordpress_post_id);
            }
        } catch (\Exception $ex) {
            Log::error("An error occurred during Wordpress event deletion: " . $ex->getMessage());

            $usersToNotify = FixometerHelper::usersWhoHavePreference('delete-event-notification');

            Notification::send($usersToNotify, new DeleteEventFromWordpressFailed([
                'event_venue' => $repairEvent->venue,
                'group_name' => $repairEvent->theGroup->name,
            ]));
        }
    }
}
