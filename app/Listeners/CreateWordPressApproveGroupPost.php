<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use App\Group;
use App\Notifications\AdminWordPressCreateGroupFailure;
use FixometerHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Notification;

class CreateWordPressApproveGroupPost
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
     * @param  ApproveGroup  $event
     * @return void
     */
    public function handle(ApproveGroup $event)
    {

      // Set event variable
        $id = $event->group->idgroups;
        $data = $event->data;

      // Define model
        $group = Group::find($id);

        if (!empty($group)) {
            try {
                if (( env('APP_ENV') == 'development' || env('APP_ENV') == 'local' ) && isset($data['moderate']) && $data['moderate'] == 'approve') { // For testing purposes
                    $group->update(['wordpress_post_id' => 99999]);
                } elseif (( env('APP_ENV') != 'development' && env('APP_ENV') != 'local' ) && isset($data['moderate']) && $data['moderate'] == 'approve') {
                    // TODO: host.  Groups don't just have one host.  Is host
                    // displayed on the front-end anywhere?
                    // TODO: receiving area field from posted data.  It's currently not in the interface.

                    $custom_fields = array(
                    array('key' => 'group_city',            'value' => $group->area),
                    array('key' => 'group_country',            'value' => $group->country),
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

                    $wpid = $wpClient->newPost($group->name, $data['free_text'], $content);

                    $group->update(['wordpress_post_id' => $wpid]);
                }
            } catch (\Exception $e) {
                Log::error("An error occurred during Wordpress group creation: " . $e->getMessage());

                $notify_users = FixometerHelper::usersWhoHavePreference('admin-approve-wordpress-group-failure');
                Notification::send($notify_users, new AdminWordPressCreateGroupFailure([
                'group_name' => $group->name,
                'group_url' => url('/group/edit/'.$group->idgroups),
                ]));
            }
        }
    }
}
