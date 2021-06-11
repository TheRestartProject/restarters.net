<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use App\Group;
use App\Notifications\AdminWordPressCreateGroupFailure;
use FixometerHelper;

use HieuLe\WordpressXmlrpcClient\WordpressClient;
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
    public function __construct(WordpressClient $wpClient)
    {
        $this->wpClient = $wpClient;
    }

    /**
     * Handle the event.
     *
     * @param  ApproveGroup  $event
     * @return void
     */
    public function handle(ApproveGroup $event)
    {
        $id = $event->group->idgroups;
        $data = $event->data;

        $group = Group::find($id);

        if (!empty($group)) {
            Log::error("Group not found");
        }

        if ( ! $group->eventsShouldPushToWordpress()) {
            $group->update(['wordpress_post_id' => '99999']);
            Log::info("Approved - but groups in this network are not published to WordPress");
            return;
        }

        try {
            if (isset($data['moderate']) && $data['moderate'] == 'approve') {
                // TODO: host.  Groups don't just have one host.  Is host
                // displayed on the front-end anywhere?
                // TODO: receiving area field from posted data.  It's currently not in the interface.

                $custom_fields = [
                    ['key' => 'group_city', 'value' => $group->area],
                    ['key' => 'group_country', 'value' => $group->country],
                    ['key' => 'group_website', 'value' => $group->website],
                    ['key' => 'group_hash', 'value' => $id],
                    ['key' => 'group_avatar_url', 'value' => $data['group_avatar']],
                    ['key' => 'group_latitude', 'value' => $group->latitude],
                    ['key' => 'group_longitude', 'value' => $group->longitude],
                ];

                $content = array(
                    'post_type' => 'group',
                    'post_title' => $group->name,
                    'post_content' => $group->free_text,
                    'custom_fields' => $custom_fields
                );

                $wpid = $this->wpClient->newPost($group->name, $data['free_text'], $content);

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
