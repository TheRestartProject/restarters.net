<?php

namespace App\Listeners;

use App\Events\UserLanguageUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SyncLanguageSettingsToDiscourse
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
     * @param  UserLanguageUpdated  $event
     * @return void
     */
    public function handle(UserLanguageUpdated $event)
    {
        // Only sync if Discourse integration is enabled.
        if (config('restarters.features.discourse_integration') === false)
            return;

        $user = $event->user;

        // Only sync if the language actually changed.
        if ( ! $user->isDirty('language'))
            return;

        try {
            $client = app('discourse-client');

            $endpoint = "/users/by-external/{$user->id}.json";
            $response = $client->request(
                'GET',
                $endpoint
            );

            if ( $response->getStatusCode() !== 200) {
                Log::error('Could not sync '.$user->id.' language to Discourse: '.$response->getReasonPhrase());
            }

            $json = json_decode($response->getBody()->getContents(), true);
            if (empty($json['user']))
                throw new \Exception("User {$user->id} not found in Discourse");

            $userName = $json['user']['username'];

            // TODO: Discourse doesn't have e.g. fr-BE, so just going with main locale.
            $locale = explode('-', $user->language)[0];

            $endpoint = "/u/{$userName}.json";
            $response = $client->request(
                'PUT',
                $endpoint,
                ['form_params' => [
                    'locale' => $locale,
                ]]
            );
        } catch (\Exception $ex) {
            dd($ex);
            Log::error('Could not sync '.$user->id.' language to Discourse: '.$ex->getMessage());
        }
    }
}
