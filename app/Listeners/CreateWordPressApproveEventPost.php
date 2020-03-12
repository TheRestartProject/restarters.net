<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;
use App\Notifications\AdminWordPressCreateEventFailure;
use App\Party;
use App\Group;
use Illuminate\Support\Facades\Log;
use FixometerHelper;

class CreateWordPressApproveEventPost
{
    protected $wpClient;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
        $this->wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));
    }

    /**
     * Handle the event.
     *
     * @param  ApproveEvent  $event
     * @return void
     */
    public function handle(ApproveEvent $event)
    {
        $id = $event->party->idevents;
        $data = $event->data;

        $theParty = Party::find($id);

        if (!empty($theParty)) {
            try {
                // For testing purposes
                if (( env('APP_ENV') == 'development' || env('APP_ENV') == 'local' ) && isset($data['moderate']) && $data['moderate'] == 'approve') {
                    $theParty->update(['wordpress_post_id' => 99999]);
                } elseif (isset($data['moderate']) && $data['moderate'] == 'approve') {
                    $startTimestamp = strtotime($data['event_date'] . ' ' . $data['start']);
                    $endTimestamp = strtotime($data['event_date'] . ' ' . $data['end']);

                    $group = Group::where('idgroups', $data['group'])->first();

                    $custom_fields = [
                        ['key' => 'party_grouphash', 'value' => $data['group']],
                        ['key' => 'party_venue', 'value' => $data['venue']],
                        ['key' => 'party_location', 'value' => $data['location']],
                        ['key' => 'party_time', 'value' => $data['start'] . ' - ' . $data['end']],
                        ['key' => 'party_groupcountry', 'value' => $group->country],
                        ['key' => 'party_groupcity', 'value' => $group->area],
                        ['key' => 'party_date', 'value' => $data['event_date']],
                        ['key' => 'party_timestamp', 'value' => $startTimestamp],
                        ['key' => 'party_timestamp_end', 'value' => $endTimestamp],
                        ['key' => 'party_stats', 'value' => $id],
                        ['key' => 'party_lat', 'value' => $data['latitude']],
                        ['key' => 'party_lon', 'value' => $data['longitude']]
                    ];

                    $content = [
                        'post_type' => 'party',
                        'custom_fields' => $custom_fields
                    ];

                    $party_name = !empty($data['venue']) ? $data['venue'] : $data['location'];
                    $wpid = $this->wpClient->newPost($party_name, $data['free_text'], $content);

                    $theParty->update(['wordpress_post_id' => $wpid]);
                }
            } catch (\Exception $e) {
                 Log::error("An error occurred during Wordpress event creation: " . $e->getMessage());
                 $notify_users = FixometerHelper::usersWhoHavePreference('admin-approve-wordpress-event-failure');
                 Notification::send($notify_users, new AdminWordPressCreateEventFailure([
                    'event_venue' => $theParty->venue,
                    'event_url' => url('/party/edit/'.$theParty->idevents),
                 ]));
            }
        }
    }
}
