<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use App\Group;
use App\Helpers\Fixometer;
use App\Notifications\AdminWordPressCreateGroupFailure;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Log;
use Notification;

class CreateWordpressPostForGroup extends BaseEvent
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

        if (empty($group)) {
            Log::error('Group not found');

            return;
        }

        $group->approved = true;
        $group->save();

        if (! $group->eventsShouldPushToWordpress()) {
            Log::info('Approved - but groups in this network are not published to WordPress');

            return;
        }

        try {
            $this->createGroupOnWordpress($group);
        } catch (\Exception $e) {
            Log::error('An error occurred during Wordpress group creation: '.$e->getMessage());

            $notify_users = Fixometer::usersWhoHavePreference('admin-approve-wordpress-group-failure');
            Notification::send($notify_users, new AdminWordPressCreateGroupFailure([
            'group_name' => $group->name,
            'group_url' => url('/group/edit/'.$group->idgroups),
            ]));
        }
    }

    /**
     * @param $group
     * @param $id
     */
    public function createGroupOnWordpress($group): void
    {
        if (!$group->wordpress_post_id) {
            $custom_fields = [
                ['key' => 'group_city', 'value' => $group->area],
                ['key' => 'group_country', 'value' => $group->country],
                ['key' => 'group_website', 'value' => $group->website],
                ['key' => 'group_hash', 'value' => $group->idgroups],
                ['key' => 'group_avatar_url', 'value' => $group->groupImagePath()],
                ['key' => 'group_latitude', 'value' => $group->latitude],
                ['key' => 'group_longitude', 'value' => $group->longitude],
            ];

            $content = [
                'post_type' => 'group',
                'post_title' => $group->name,
                'post_content' => $group->free_text,
                'custom_fields' => $custom_fields,
            ];

            $wpid = $this->wpClient->newPost($group->name, $group->free_text, $content);

            $group->update(['wordpress_post_id' => $wpid]);
        }
    }
}
