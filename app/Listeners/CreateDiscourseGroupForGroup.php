<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use App\EventsUsers;
use App\Party;
use App\User;
use App\UserGroups;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

class CreateDiscourseGroupForGroup
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
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        // Get the host who created the group.
        $group = $event->group;
        $member = UserGroups::where('group', $group->idgroups)->first();
        $host = User::find($member->user);

        if (empty($host)) {
            Log::error('Could not find host of group');
            return;
        }

        try {
            // We want to internationalise the message.  Use the languages of any networks that the group
            // is in.
            $text = '';
            $langs = [];

            foreach ($group->networks as $network) {
                $lang = $network->default_language;

                if (!in_array($lang, $langs)) {
                    $text .= Lang::get('groups.discourse_title',[
                        'group' => $group->name,
                        'link' => env('APP_URL') . '/group/view/' . $group->idgroups,
                        'help' => 'https://talk.restarters.net'
                    ],$lang);

                    $langs[] = $lang;
                }
            }

            // We want the host to create the group, so use their username.  The API key should
            // allow us to do this - see https://meta.discourse.org/t/how-can-an-api-user-create-posts-as-another-user/45968/3.
            $client = app('discourse-client', [
                'username' => env('DISCOURSE_APIUSER'),
            ]);

            // Restricted characters allowed in name, and only 25 characters.
            $name = str_replace(' ', '_', $group->name);
            $name = preg_replace("/[^A-Za-z0-9_]/", '', $name);
            $name = substr($name, 0, 25);

            $params = [
                'group' => [
                    'name' => $name,
                    'full_name' => $group->name,
                    'mentionable_level' => 3,
                    'messageable_level' => 99,
                    'visibility_level' => 0,
                    'members_visibility_level' => 0,
                    'automatic_membership_email_domains' => null,
                    'automatic_membership_retroactive' => false,
                    'primary_group' => false,
                    'flair_url' => $group->groupImagePath(),
                    'flair_bg_color' => null,
                    'flair_color' => null,
                    'bio_raw' => $text,
                    'public_admission' => true,
                    'public_exit' => true,
                    'default_notification_level' => 3,
                    'publish_read_state' => true,
                    'owner_usernames' => $host->username
                ]
            ];

            $endpoint = '/admin/groups.json';

            Log::info('Creating group : '.json_encode($params));
            $response = $client->request(
                'POST',
                $endpoint,
                [
                    'form_params' => $params,
                ]
            );

            Log::info('Response status: '.$response->getStatusCode());
            Log::info('Response body: '.$response->getBody());

            if (! $response->getStatusCode() === 200) {
                Log::error('Could not create group ('.$group->idgroups.') thread: '.$response->getReasonPhrase());
            } else {
                // We want to save the discourse thread id in the group, so that we can invite people to it later
                // when they join.
                $json = json_decode($response->getBody(), true);
                if (empty($json['basic_group'])) {
                    throw new \Exception('Group not found in create response');
                }

                $group->discourse_group = $name;
                $group->save();
            }
        } catch (\Exception $ex) {
            Log::error('Could not create group ('.$group->idgroups.') thread: '.$ex->getMessage());
        }
    }
}
