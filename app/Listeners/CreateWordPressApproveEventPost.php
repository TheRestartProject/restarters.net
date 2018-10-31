<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;
use App\Party;

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
      if ( empty($event->event) )

      try {

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
            array('key' => 'party_date',            'value' => $wp_date),
            array('key' => 'party_timestamp',       'value' => $theParty->event_timestamp),
            array('key' => 'party_timestamp_end',   'value' => $theParty->event_end_timestamp),
            array('key' => 'party_stats',           'value' => $id),
            array('key' => 'party_lat',             'value' => $latitude),
            array('key' => 'party_lon',             'value' => $longitude)
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

          $theParty = Party::find($id);
          $theParty->wordpress_post_id = $wpid;
          $theParty->save();
          // }


        } catch (\Exception $e) {

          $notify_users = FixometerHelper::usersWhoHavePreference('admin-approve-wordpress-event-failure');
          Notification::send($notify_users, new AdminApproveWordpressEventFailure([
              'event_venue' => $theParty->venue,
              'event_url' => url('/party/edit/'.$theParty->idevents),
          ]));

      }
    }
}
