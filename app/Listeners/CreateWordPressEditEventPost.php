<?php

namespace App\Listeners;

use App\Events\EditEvent;
use App\Party;
use App\Notifications\AdminWordPressEditEventFailure;
use FixometerHelper;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Notification;

class CreateWordPressEditEventPost
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
     * @param  EditEvent  $event
     * @return void
     */
    public function handle(EditEvent $event)
    {

      // Set event variable
      $id = $event->party->idevents;
      $data = $event->data;

      // Define model
      $theParty = Party::find($id);

      if ( !empty($theParty) && env('APP_ENV') != 'development' && env('APP_ENV') != 'local' ){

        try {

          if( is_numeric($theParty->wordpress_post_id) ){

            $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
            $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

            /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
            // $theParty = $Party->findThis($id);
            //$Host = $Groups->findHost($data['group']);
            $custom_fields = array(
              //array('key' => 'party_host',            'value' => $Host->hostname),
              //array('key' => 'party_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' . $Host->path),
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


            $content = array(
              'post_type' => 'party',
              'post_title' => !empty($data['venue']) ? $data['venue'] : $data['location'],
              'post_content' => $data['free_text'],
              'custom_fields' => $custom_fields
            );


            // we need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
            $thePost = $wpClient->getPost($theParty->wordpress_post_id);

            foreach( $thePost['custom_fields'] as $i => $field ){
              foreach( $custom_fields as $k => $set_field){
                if($field['key'] == $set_field['key']){
                  $custom_fields[$k]['id'] = $field['id'];
                }
              }
            }

            $content['custom_fields'] = $custom_fields;
            $wpClient->editPost($theParty->wordpress_post_id, $content);

          }

        } catch (\Exception $e) {
            Log::error("An error occurred during Wordpress event update: " . $e->getMessage());
          $notify_users = FixometerHelper::usersWhoHavePreference('admin-edit-wordpress-event-failure');
          Notification::send($notify_users, new AdminWordPressEditEventFailure([
            'event_venue' => $theParty->venue,
            'event_url' => url('/party/edit/'.$theParty->idevents),
          ]));

        }

      }

    }

}
