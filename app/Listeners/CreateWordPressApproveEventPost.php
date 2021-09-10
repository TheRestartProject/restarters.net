<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use App\Group;
use App\Network;
use App\Notifications\AdminWordPressCreateEventFailure;
use App\Party;
use App\Helpers\Fixometer;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Notification;

class CreateWordPressApproveEventPost
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

        if (! $theParty->shouldPushToWordpress()) {
            $theParty->update(['wordpress_post_id' => '99999']);
            Log::info('Approved - but events for groups in this network are not published to WordPress');

            return;
        }

        try {
            $startTimestamp = strtotime($event->event_date.' '.$event->start);
            $endTimestamp = strtotime($event->event_date.' '.$event->end);

            $group = Group::where('idgroups', $event->group)->first();

            $custom_fields = [
                ['key' => 'party_grouphash', 'value' => $event->group],
                ['key' => 'party_venue', 'value' => $event->venue],
                ['key' => 'party_location', 'value' => $event->location],
                ['key' => 'party_time', 'value' => $event->start.' - '.$event->end],
                ['key' => 'party_groupcountry', 'value' => $group->country],
                ['key' => 'party_groupcity', 'value' => $group->area],
                ['key' => 'party_date', 'value' => $event->event_date],
                ['key' => 'party_timestamp', 'value' => $startTimestamp],
                ['key' => 'party_timestamp_end', 'value' => $endTimestamp],
                ['key' => 'party_stats', 'value' => $partyId],
                ['key' => 'party_lat', 'value' => $event->latitude],
                ['key' => 'party_lon', 'value' => $event->longitude],
                ['key' => 'party_online', 'value' => $event->online ?? 0],
            ];

            $content = [
                'post_type' => 'party',
                'custom_fields' => $custom_fields,
            ];

            $party_name = ! empty($event->venue) ? $event->venue : $event->location;
            $wpid = $this->wpClient->newPost($party_name, $event->free_text, $content);

            $theParty->update(['wordpress_post_id' => $wpid]);
        } catch (\Exception $e) {
            Log::error('An error occurred during Wordpress event creation: '.$e->getMessage());
            $notify_users = Fixometer::usersWhoHavePreference('admin-approve-wordpress-event-failure');
            Notification::send($notify_users, new AdminWordPressCreateEventFailure([
                'event_venue' => $theParty->venue,
                'event_url' => url('/party/edit/'.$theParty->idevents),
                ]));
        }
    }
}
