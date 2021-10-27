<?php

namespace App\Listeners;

use App\Events\ApproveGroup;
use App\EventsUsers;
use App\Party;
use App\User;
use App\UserGroups;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

class CreateDiscourseThreadForGroup
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

            foreach ($group->networks as $network) {
                $lang = $network->default_language;
                $text .= Lang::get('groups.discourse_title',[
                    'group' => $group->name,
                    'link' => env('APP_URL') . '/group/view/' . $group->idgroups,
                    'help' => 'https://talk.restarters.net'
                ],$lang);
            }

            // We want the host to create the message, so use their username.  The API key should
            // allow us to do this - see https://meta.discourse.org/t/how-can-an-api-user-create-posts-as-another-user/45968/3.
            $client = app('discourse-client', [
                'username' => $host->username,
            ]);

            // See https://meta.discourse.org/t/private-message-send-api/27593/21.
            $params = [
                'raw' => $text,
                'title' => $group->name,
                'target_usernames' => $host->username,
                'archetype' => 'private_message',
            ];

            $endpoint = '/posts.json';

            Log::info('Creating group thread: '.json_encode($params));
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
                if (empty($json['topic_id'])) {
                    throw new \Exception('Topic id not found in create response');
                }

                $group->discourse_thread = $json['topic_id'];
                $group->save();
            }
        } catch (\Exception $ex) {
            Log::error('Could not create group ('.$group->idgroups.') thread: '.$ex->getMessage());
        }
    }
}
