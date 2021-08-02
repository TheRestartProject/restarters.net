<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use App\Events\UserFollowedGroup;
use App\EventsUsers;
use App\Party;
use App\User;
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
        if (! config('restarters.features.discourse_integration')) {
            return;
        }

        // Get the event.
        $partyId = $event->party->idevents;

        $theParty = Party::find($partyId);

        // Get the user who created the event.
        $host = User::find(EventsUsers::where('event', $partyId)->get()[0]->user);

        if (empty($theParty)) {
            Log::error('Event not found');

            return;
        }

        try {
            // We want the host to create the message, so use their username.  The API key should
            // allow us to do this - see https://meta.discourse.org/t/how-can-an-api-user-create-posts-as-another-user/45968/3.
            $client = app('discourse-client', [
                'username' => $host->username,
            ]);

            // See https://meta.discourse.org/t/private-message-send-api/27593/21.
            $params = [
                'raw' => $theParty->free_text,
                'title' => $theParty->venue.' '.$theParty->event_date,
                'target_usernames' => $host->username,
                'archetype' => 'private_message',
            ];

            $endpoint = '/posts.json';

            Log::info('Creating event thread: '.json_encode($params));
            $response = $client->request(
                'POST',
                $endpoint,
                [
                    'form_params' => $params,
                ]
            );

            Log::info('Response status: '.$response->getStatusCode());
            Log::info('Response body: '.$response->getBody());

            // We want to save the discourse thread id in the event, so that we can invite people to it later
            // when they RSVP.
            $json = json_decode($response->getBody(), true);
            if (empty($json['topic_id'])) {
                throw new \Exception('Topic id not found in create response');
            }

            $theParty->discourse_thread = $json['topic_id'];
            $theParty->save();

            if (! $response->getStatusCode() === 200) {
                Log::error('Could not create event ('.$partyId.') thread: '.$response->getReasonPhrase());
            }
        } catch (\Exception $ex) {
            Log::error('Could not create event ('.$partyId.') thread: '.$ex->getMessage());
        }
    }
}
