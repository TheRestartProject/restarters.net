<?php

namespace App\Listeners;

use App\Events\EditGroup;
use App\Group;
use App\Helpers\Fixometer;
use App\Notifications\AdminWordPressEditGroupFailure;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Log;
use Notification;

class EditWordpressPostForGroup extends BaseEvent
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
     */
    public function handle(EditGroup $event): void
    {
        $id = $event->group->idgroups;

        $group = Group::find($id);

        if (! $group->eventsShouldPushToWordpress()) {
            Log::info('Groups in this network are not published to WordPress');

            return;
        }

        if ($group->archived_at) {
            // Suppress notifications for archived groups.
            return;
        }

        try {
            if (is_numeric($group->wordpress_post_id)) {
                // Read everything off the group, which the caller has already saved, rather than off the
                // event payload.  That keeps this in step with CreateWordpressPostForGroup - notably the
                // avatar, which the payload only ever held as a bare filename.
                $custom_fields = [
                    ['key' => 'group_city', 'value' => $group->area],
                    ['key' => 'group_country', 'value' => Fixometer::getCountryFromCountryCode($group->country_code)],
                    ['key' => 'group_website', 'value' => $group->website],
                    ['key' => 'group_hash', 'value' => $id],
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
                    $wpid = $this->wpClient->newPost($group->name, $group->free_text, $content);
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
