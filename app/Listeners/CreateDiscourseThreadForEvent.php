<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use App\Events\UserFollowedGroup;
use App\Party;
use App\User;
use App\EventsUsers;
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

        // Get the event.
        $partyId = $event->party->idevents;

        $theParty = Party::find($partyId);

        // Get the user who created the event.
        $host = User::find(EventsUsers::where('event', $partyId)->get()[0]->user);

        if (empty($theParty)) {
            Log::error("Event not found");
            return;
        }

        // add user to the network groups for the group the user followed.
        try {
            $client = app('discourse-client');

            // See https://meta.discourse.org/t/private-message-send-api/27593/21.
            $params = [
                'raw' => $theParty->venue,
                'title' => $theParty->venue,
                'target_usernames' => $host->username,
                'archetype' => 'private_message'
            ];

            $endpoint = '/posts.json';

            Log::info('Creating event thread: ' . json_encode($params));
            $response = $client->request(
                'POST',
                $endpoint,
                [
                    'form_params' => $params
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
