<?php

namespace App\Listeners;

use App\Events\EditGroup;
use App\Group;
use App\Notifications\AdminWordPressEditGroupFailure;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
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

        if (!empty($group) && env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {
            try {
                if (is_numeric($group->wordpress_post_id)) {
                    $custom_fields = array(
                    array('key' => 'group_city',            'value' => $group->area),
                    //                                    array('key' => 'group_host',            'value' => $Host->hostname),
                    array('key' => 'group_website',         'value' => $data['website']),
                    //array('key' => 'group_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' . $Host->path),
                    array('key' => 'group_hash',            'value' => $id),
                    array('key' => 'group_avatar_url',      'value' => $data['group_avatar']),
                    array('key' => 'group_latitude',        'value' => $data['latitude']),
                    array('key' => 'group_longitude',       'value' => $data['longitude']),
                    );

                    /** Start WP XML-RPC **/
                    $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                    $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                    $content = array(
                      'post_type' => 'group',
                      'post_title' => $data['name'],
                      'post_content' => $data['free_text'],
                      'custom_fields' => $custom_fields
                    );


                    if (!empty($group->wordpress_post_id)) {
                            // We need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                            $existingPost = $wpClient->getPost($group->wordpress_post_id);

                        foreach ($existingPost['custom_fields'] as $i => $field) {
                            foreach ($custom_fields as $k => $set_field) {
                                if ($field['key'] == $set_field['key']) {
                                    $custom_fields[$k]['id'] = $field['id'];
                                }
                            }
                        }

                          $content['custom_fields'] = $custom_fields;
                          $wpClient->editPost($group->wordpress_post_id, $content);
                    } else {
                        $wpid = $wpClient->newPost($data['name'], $data['free_text'], $content);
                        $group->wordpress_post_id = $wpid;
                        $group->save();
                    }
                }
            } catch (\Exception $e) {
                Log::error("An error occurred during Wordpress group editing: " . $e->getMessage());
                $notify_users = FixometerHelper::usersWhoHavePreference('admin-edit-wordpress-group-failure');
                Notification::send($notify_users, new AdminWordPressEditGroupFailure([
                'group_name' => $group->name,
                'group_url' => url('/group/edit/'.$group->idgroups),
                ]));
            }
        }
    }
}
