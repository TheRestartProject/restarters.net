<?php

namespace App\Listeners;

use App\Events\UserFollowedGroup;
use App\Notifications\NewDiscourseMember;
use Illuminate\Support\Facades\Log;
use Notification;

class AddUserToDiscourseGroup
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
     * @param  UserFollowedGroup  $event
     * @return void
     */
    public function handle(UserFollowedGroup $event)
    {
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        // add user to the network groups for the group the user followed.
        $repairGroup = $event->group;
        $user = $event->user;

        try {
            $discourseGroups = [];
            foreach ($repairGroup->networks as $network) {
                if ($network->discourse_group !== null) {
                    $discourseGroups[] = $network->discourse_group;
                }
            }
            $discourseGroupsCommaSeparated = implode(',', $discourseGroups);

            $client = app('discourse-client');

            // see https://meta.discourse.org/t/sync-sso-user-data-with-the-sync-sso-route/84398 for details on the sync_sso route.
            $sso_secret = config('discourse-api.sso_secret');

            // We have to send all these details, even if they are not
            // being updated, as otherwise they are blanked in the Discourse
            // SSO values.  Discourse is currently configured to take only email from SSO values, but better not to blank them regardless.
            $sso_params = [
                'external_id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
                'name' => $user->name,
                'add_groups' => $discourseGroupsCommaSeparated,
            ];
            $sso_payload = base64_encode(http_build_query($sso_params));
            $sig = hash_hmac('sha256', $sso_payload, $sso_secret);

            $endpoint = '/admin/users/sync_sso';

            Log::info('Syncing: '.json_encode($sso_params));
            $response = $client->request(
                'POST',
                $endpoint,
                [
                    'form_params' => [
                        'sso' => $sso_payload,
                        'sig' => $sig,
                    ],
                ]
            );

            Log::info('Response status: '.$response->getStatusCode());
            Log::info('Response body: '.$response->getBody());

            if (! $response->getStatusCode() === 200) {
                Log::error('Could not sync user\'s ('.$user->id.') groups to Discourse: '.$response->getReasonPhrase());
            } else {
                Notification::send($user, new NewDiscourseMember([
                    'group_name' => $repairGroup->name
                ]));
            }
        } catch (\Exception $ex) {
            Log::error('Could not sync user\'s ('.$user->id.') groups to Discourse: '.$ex->getMessage());
        }
    }
}
