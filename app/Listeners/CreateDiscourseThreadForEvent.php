<?php

namespace App\Listeners;

use App\Events\ApproveEvent;
use App\EventsUsers;
use App\Party;
use App\User;
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

        if (empty($theParty)) {
            Log::error('Event not found');

            return;
        }

        // Get the user who created the event.
        $users = EventsUsers::where('event', $partyId)->get();

        if (!count($users)) {
            Log::error("Can't find event creator");
        } else {
            $host = User::find($users[0]->user);

            try {
                // We want the host to create the message, so use their username.  The API key should
                // allow us to do this - see https://meta.discourse.org/t/how-can-an-api-user-create-posts-as-another-user/45968/3.
                $client = app('discourse-client', [
                    'username' => $host->username,
                ]);

                // Construct an intro.
                $group = $theParty->theGroup;
                $intro = __('events.discourse_intro', [
                    'name' => $theParty->venue,
                    'url' => url('/party/view/' . $theParty->idevents),
                    'start' => $theParty->getFormattedLocalStart('D jS M, H:i'),
                    'end' => $theParty->getFormattedLocalEnd('H:i'),
                    'groupname' => $group->name,
                    'groupurl' => url('/group/' . $group->id),
                ], $host->language);

                // See https://meta.discourse.org/t/private-message-send-api/27593/21.
                $params = [
                    'raw' => $intro,
                    'title' => $theParty->venue.' '.$theParty->event_date_local,
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

                if (! $response->getStatusCode() === 200) {
                    Log::error('Could not create event ('.$partyId.') thread: '.$response->getReasonPhrase());
                } else {
                    // We want to save the discourse thread id in the event, so that we can invite people to it later
                    // when they RSVP.
                    $json = json_decode($response->getBody(), true);
                    if (empty($json['topic_id'])) {
                        throw new \Exception('Topic id not found in create response');
                    }

                    $theParty->discourse_thread = $json['topic_id'];
                    $theParty->save();
                }
            } catch (\Exception $ex) {
                Log::error('Could not create event ('.$partyId.') thread: '.$ex->getMessage());
            }
        }
    }
}
