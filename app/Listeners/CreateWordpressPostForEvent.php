<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use App\Group;
use App\Helpers\Fixometer;
use App\Notifications\AdminWordPressCreateEventFailure;
use App\Party;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Log;
use Notification;

class CreateWordpressPostForEvent
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
     * @param  ApproveEvent  $event
     * @return void
     */
    public function handle(ApproveEvent $event)
    {
        $partyId = $event->party->idevents;

        $theParty = Party::find($partyId);

        if (empty($theParty)) {
            Log::error('Event not found');

            return;
        }

        $theParty->approved = true;
        $theParty->save();

        if (! $theParty->shouldPushToWordpress()) {
            Log::info('Approved - but events for groups in this network are not published to WordPress');

            return;
        }

        try {
            $this->createEventOnWordpress($theParty);
        } catch (\Exception $e) {
            Log::error('An error occurred during Wordpress event creation: '.$e->getMessage());
            $notify_users = Fixometer::usersWhoHavePreference('admin-approve-wordpress-event-failure');
            Notification::send($notify_users, new AdminWordPressCreateEventFailure([
                'event_venue' => $theParty->venue,
                'event_url' => url('/party/edit/'.$theParty->idevents),
                ]));
        }
    }

    /**
     * @param $theParty
     */
    public function createEventOnWordpress($theParty): void
    {
        if (!$theParty->wordpress_post_id) {
            $startTimestamp = strtotime($theParty->event_start_utc);
            $endTimestamp = strtotime($theParty->event_end_utc);

            $group = Group::where('idgroups', $theParty->group)->first();

            $custom_fields = [
                ['key' => 'party_grouphash', 'value' => $theParty->group],
                ['key' => 'party_venue', 'value' => $theParty->venue],
                ['key' => 'party_location', 'value' => $theParty->location],
                ['key' => 'party_time', 'value' => $theParty->getEventStartEndLocal()],
                ['key' => 'party_groupcountry', 'value' => $group->country],
                ['key' => 'party_groupcity', 'value' => $group->area],
                ['key' => 'party_date', 'value' => $theParty->event_date_local],
                ['key' => 'party_timestamp', 'value' => $startTimestamp],
                ['key' => 'party_timestamp_end', 'value' => $endTimestamp],
                ['key' => 'party_timezone', 'value' => $theParty->timezone],
                ['key' => 'party_stats', 'value' => $theParty->idevents],
                ['key' => 'party_lat', 'value' => $theParty->latitude],
                ['key' => 'party_lon', 'value' => $theParty->longitude],
                ['key' => 'party_online', 'value' => $theParty->online ?? 0],
            ];

            $content = [
                'post_type' => 'party',
                'custom_fields' => $custom_fields,
            ];

            $party_name = !empty($theParty->venue) ? $theParty->venue : $theParty->location;
            $wpid = $this->wpClient->newPost($party_name, $theParty->free_text, $content);

            $theParty->update(['wordpress_post_id' => $wpid]);
        }
    }
}
