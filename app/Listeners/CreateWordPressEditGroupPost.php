<?php

namespace App\Listeners;

use App\Events\EditGroup;
use App\Group;
use App\Notifications\AdminWordPressEditGroupFailure;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use FixometerHelper;
use Notification;

class CreateWordPressEditGroupPost
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
     * @param  EditGroup  $event
     * @return void
     */
    public function handle(EditGroup $event)
    {

      // Set event variable
      $id = $event->group->idgroups;
      $data = $event->data;

      // Define model
      $group = Group::find($id);

      if ( !empty($group) && env('APP_ENV') != 'development' && env('APP_ENV') != 'local' ){

        try {

          if( is_numeric($group->wordpress_post_id) ){

            // TODO: host.  Groups don't just have one host.  Is host
            // displayed on the front-end anywhere?
            // TODO: receiving area field from posted data.  It's currently not in the interface.

            $custom_fields = array(
              array('key' => 'group_city',            'value' => $group->area),
              array('key' => 'group_website',         'value' => $group->website),
              array('key' => 'group_hash',            'value' => $id),
              array('key' => 'group_avatar_url',      'value' => $data['group_avatar']),
              array('key' => 'group_latitude',        'value' => $group->latitude),
              array('key' => 'group_longitude',       'value' => $group->longitude),
            );

            /** Start WP XML-RPC **/
            $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
            $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

            $content = array(
              'post_type' => 'group',
              'post_title' => $group->name,
              'post_content' => $group->free_text,
              'custom_fields' => $custom_fields
            );

            $wpid = $wpClient->newPost($data['name'], $data['free_text'], $content);
            $group->wordpress_post_id = $wpid;
            $group->save();

          }

        } catch (\Exception $e) {

          $notify_users = FixometerHelper::usersWhoHavePreference('admin-edit-wordpress-group-failure');
          Notification::send($notify_users, new AdminWordPressEditGroupFailure([
            'group_name' => $group->name,
            'group_url' => url('/group/edit/'.$group->idgroups),
          ]));

        }

      }

    }

}
