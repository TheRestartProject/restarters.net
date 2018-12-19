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
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ApproveEvent  $event
     * @return void
     */
    public function handle(ApproveEvent $event)
    {

       // Set event variable
        $id = $event->party->idevents;
        $data = $event->data;

       // Define model
        $theParty = Party::find($id);

        if (!empty($theParty)) {
            try {
                if (( env('APP_ENV') == 'development' || env('APP_ENV') == 'local' ) && isset($data['moderate']) && $data['moderate'] == 'approve') { // For testing purposes
                    $theParty->update(['wordpress_post_id' => 99999]);
                } elseif (( env('APP_ENV') != 'development' && env('APP_ENV') != 'local' ) && isset($data['moderate']) && $data['moderate'] == 'approve') {
                    $startTimestamp = strtotime($data['event_date'] . ' ' . $data['start']);
                    $endTimestamp = strtotime($data['event_date'] . ' ' . $data['end']);

                    $group = Group::where('idgroups', $data['group'])->first();

                  // if(env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {
                  /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                  //$Host = $Groups->findHost($group);
                    $custom_fields = array(
                    // array('key' => 'party_host',            'value' => $Host->hostname),
                    // array('key' => 'party_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' .$Host->path),
                    array('key' => 'party_grouphash',       'value' => $data['group']),
                    array('key' => 'party_venue',           'value' => $data['venue']),
                    array('key' => 'party_location',        'value' => $data['location']),
                    array('key' => 'party_time',            'value' => $data['start'] . ' - ' . $data['end']),
                    array('key' => 'party_groupcountry',    'value' => $group->country),
                    array('key' => 'party_date',            'value' => $data['event_date']),
                    array('key' => 'party_timestamp',       'value' => $startTimestamp),
                    array('key' => 'party_timestamp_end',   'value' => $endTimestamp),
                    array('key' => 'party_stats',           'value' => $id),
                    array('key' => 'party_lat',             'value' => $data['latitude']),
                    array('key' => 'party_lon',             'value' => $data['longitude'])
                    );

                  /** Start WP XML-RPC **/
                    $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                    $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                    $content = array(
                    'post_type' => 'party',
                    'custom_fields' => $custom_fields
                    );

                    $party_name = !empty($data['venue']) ? $data['venue'] : $data['location'];
                    $wpid = $wpClient->newPost($party_name, $data['free_text'], $content);

                    $theParty->update(['wordpress_post_id' => $wpid]);
                }

             // If WordPress message fails then send Notification to Admins with Preference
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
