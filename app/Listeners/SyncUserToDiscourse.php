<?php

namespace App\Listeners;

use App\Events\UserEmailUpdated;

class SyncUserToDiscourse
{
    /**
     * @param UserUpdated $event
     */
    public function handle(UserEmailUpdated $event)
    {
        $user = $event->user;

        if (config('restarters.features.discourse_integration') === false)
            return;

        if ( ! $user->isDirty('email'))
            return;

        try {
            // Only sync if the email actually changed.
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
            ];
            $sso_payload = base64_encode( http_build_query( $sso_params ) );
            $sig = hash_hmac( 'sha256', $sso_payload, $sso_secret );

            $endpoint = '/admin/users/sync_sso';

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

            if ( ! $response->getStatusCode() === 200) {
                Log::error('Could not sync '.$user->id.' to Discourse: '.$response->getReasonPhrase());
            }
        } catch (\Exception $ex) {
            Log::error('Could not sync '.$user->id.' to Discourse: '.$ex->getMessage());
        }
    }
}
