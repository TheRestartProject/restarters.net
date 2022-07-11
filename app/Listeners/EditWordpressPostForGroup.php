<?php

namespace App\Listeners;

use App\Events\EditGroup;
use App\Group;
use App\Helpers\Fixometer;
use App\Notifications\AdminWordPressEditGroupFailure;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Log;
use Notification;

class EditWordpressPostForGroup
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
     * @param  EditGroup  $event
     * @return void
     */
    public function handle(EditGroup $event)
    {
        $id = $event->group->idgroups;
        $data = $event->data;

        $group = Group::find($id);

        if (! $group->eventsShouldPushToWordpress()) {
            Log::info('Groups in this network are not published to WordPress');

            return;
        }

        try {
            if (is_numeric($group->wordpress_post_id)) {
                $custom_fields = [
                    ['key' => 'group_city', 'value' => $group->area],
                    ['key' => 'group_country', 'value' => $group->country],
                    ['key' => 'group_website', 'value' => $data['website']],
                    ['key' => 'group_hash', 'value' => $id],
                    ['key' => 'group_avatar_url', 'value' => $data['group_avatar']],
                    ['key' => 'group_latitude', 'value' => $data['latitude']],
                    ['key' => 'group_longitude', 'value' => $data['longitude']],
                ];

                $content = [
                    'post_type' => 'group',
                    'post_title' => $data['name'],
                    'post_content' => $data['free_text'],
                    'custom_fields' => $custom_fields,
                ];

                if (! empty($group->wordpress_post_id)) {
                    // We need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                    $existingPost = $this->wpClient->getPost($group->wordpress_post_id);

                    if (isset($existingPost['custom_fields'])) {
                        foreach ($existingPost['custom_fields'] as $field) {
                            foreach ($custom_fields as $k => $set_field) {
                                if ($field['key'] == $set_field['key']) {
                                    $custom_fields[$k]['id'] = $field['id'];
                                }
                            }
                        }
                    }

                    $content['custom_fields'] = $custom_fields;
                    $this->wpClient->editPost($group->wordpress_post_id, $content);
                } else {
                    $wpid = $this->wpClient->newPost($data['name'], $data['free_text'], $content);
                    $group->wordpress_post_id = $wpid;
                    $group->save();
                }
            }
        } catch (\Exception $e) {
            Log::error('An error occurred during Wordpress group editing: '.$e->getMessage());
            $notify_users = Fixometer::usersWhoHavePreference('admin-edit-wordpress-group-failure');
            Notification::send($notify_users, new AdminWordPressEditGroupFailure([
            'group_name' => $group->name,
            'group_url' => url('/group/edit/'.$group->idgroups),
            ]));
            throw $e;
        }
    }
}
