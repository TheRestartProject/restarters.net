<?php

namespace App\Listeners;

use App\Events\EditEvent;
use App\Group;
use App\Helpers\Fixometer;
use App\Notifications\AdminWordPressEditEventFailure;
use App\Party;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Log;
use Notification;

class EditWordpressPostForEvent
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
     * @param  EditEvent  $event
     * @return void
     */
    public function handle(EditEvent $event)
    {
        $id = $event->party->idevents;
        $data = $event->data;

        $theParty = Party::find($id);

        if (! $theParty->shouldPushToWordpress()) {
            Log::info('Events for groups in this network are not published');

            return;
        }

        try {
            if (is_numeric($theParty->wordpress_post_id)) {
                $startTimestamp = strtotime($theParty->event_start_utc);
                $endTimestamp = strtotime($theParty->event_end_utc);

                $group = Group::where('idgroups', $theParty->group)->first();

                $custom_fields = [
                    ['key' => 'party_grouphash', 'value' => $data['group']],
                    ['key' => 'party_groupcountry', 'value' => $group->country],
                    ['key' => 'party_groupcity', 'value' => $group->area],
                    ['key' => 'party_venue', 'value' => $data['venue']],
                    ['key' => 'party_location', 'value' => $data['location']],
                    ['key' => 'party_time', 'value' => $theParty->getEventStartEndLocal()],
                    ['key' => 'party_date', 'value' => $theParty->event_date_local],
                    ['key' => 'party_timestamp', 'value' => $startTimestamp],
                    ['key' => 'party_timestamp_end', 'value' => $endTimestamp],
                    ['key' => 'party_timezone', 'value' => $theParty->timezone],
                    ['key' => 'party_stats', 'value' => $id],
                    ['key' => 'party_lat', 'value' => $data['latitude']],
                    ['key' => 'party_lon', 'value' => $data['longitude']],
                    ['key' => 'party_online', 'value' => $data['online'] ?? 0],
                ];

                $content = [
                    'post_type' => 'party',
                    'post_title' => ! empty($data['venue']) ? $data['venue'] : $data['location'],
                    'post_content' => $data['free_text'],
                    'custom_fields' => $custom_fields,
                ];

                // we need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                $thePost = $this->wpClient->getPost($theParty->wordpress_post_id);

                if (isset($thePost['custom_fields'])) {
                    foreach ($thePost['custom_fields'] as $field) {
                        foreach ($custom_fields as $k => $set_field) {
                            if ($field['key'] == $set_field['key']) {
                                $custom_fields[$k]['id'] = $field['id'];
                            }
                        }
                    }
                }

                $content['custom_fields'] = $custom_fields;
                $this->wpClient->editPost($theParty->wordpress_post_id, $content);
            }
        } catch (\Exception $e) {
            Log::error('An error occurred during Wordpress event update: '.$e->getMessage());
            $notify_users = Fixometer::usersWhoHavePreference('admin-edit-wordpress-event-failure');
            Notification::send($notify_users, new AdminWordPressEditEventFailure([
                'event_venue' => $theParty->venue,
                'event_url' => url('/party/edit/'.$theParty->idevents),
            ]));
        }
    }
}
