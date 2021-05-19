<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use App\Events\UserFollowedGroup;
use App\Party;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;


class CreateDiscourseThreadForEvent
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
     * @param  ApproveEvent  $event
     * @return void
     */
    public function handle(ApproveEvent $event)
    {
        if ( ! config('restarters.features.discourse_integration')) {
            return;
        }

        // Get the user who created the event.
        $user = $event->user;

        // Get the event.
        $partyId = $event->party->idevents;

        $theParty = Party::find($partyId);

        if (empty($theParty)) {
            Log::error("Event not found");
            return;
        }

        // add user to the network groups for the group the user followed.
        try {
            $client = app('discourse-client');

            // see https://meta.discourse.org/t/sync-sso-user-data-with-the-sync-sso-route/84398 for details on the sync_sso route.
            $sso_secret = config('discourse-api.sso_secret');

            // See https://meta.discourse.org/t/private-message-send-api/27593/21.
            $params = [
                'raw' => $theParty->venue,
                'title' => $theParty->venue,
                'target_usernames' => $user->name,
                'archetype' => 'private_message'
            ];
            $sso_payload = base64_encode(http_build_query($params));
            $sig = hash_hmac( 'sha256', $sso_payload, $sso_secret );

            $endpoint = '/posts';

            Log::info('Creating event thread: ' . json_encode($params));
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

            Log::info('Response status: ' . $response->getStatusCode());
            Log::info('Response body: ' . $response->getBody());

            if ( ! $response->getStatusCode() === 200) {
                Log::error('Could not create event (' . $partyId . ') thread: '.$response->getReasonPhrase());
            }
        } catch (\Exception $ex) {
            Log::error('Could not create event (' . $partyId . ') thread: '.$ex->getMessage());
        }
    }
}
