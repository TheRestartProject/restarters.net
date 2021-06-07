<?php

namespace App\Listeners;

use App\Events\UserEmailUpdated;
use App\Events\UserLanguageUpdated;
use App\Events\UserRegistered;

use Illuminate\Support\Facades\Log;
use Mediawiki\Api\ApiUser;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;

class DiscourseUserEventSubscriber
{
    private $discourseClient;

    public function __construct()
    {
        // NGM: preferable to switch to https://github.com/pnoeric/discourse-api-php ?
        $this->discourseClient = app('discourse-client');
    }

    public function onUserEmailUpdated(UserEmailUpdated $event)
    {
        $user = $event->user;

        // Only sync if the email actually changed.
        if ( ! $user->isDirty('email')) {
            return;
        }

        try {
            $this->syncSso($user);
        } catch (\Exception $ex) {
            Log::error('Could not sync '.$user->id.' to Discourse: '.$ex->getMessage());
        }
    }

    public function onUserLanguageUpdated(UserLanguageUpdated $event)
    {
        $user = $event->user;

        // Only sync if the language actually changed.
        if ( ! $user->isDirty('language')) {
            return;
        }

        try {
            // Sync to Discourse.
            $endpoint = "/users/by-external/{$user->id}.json";
            $response = $this->discourseClient->request(
                'GET',
                $endpoint
            );

            if ( $response->getStatusCode() !== 200) {
                Log::error('Could not sync '.$user->id.' language to Discourse: '.$response->getReasonPhrase());
            }

            $json = json_decode($response->getBody()->getContents(), true);
            if (empty($json['user'])) {
                throw new \Exception("User {$user->id} not found in Discourse");
            }

            $userName = $json['user']['username'];

            // Discourse doesn't have e.g. fr-BE, so just going with main locale.
            $locale = explode('-', $user->language)[0];

            $endpoint = "/u/{$userName}.json";
            $this->discourseClient->request(
                'PUT',
                $endpoint,
                ['form_params' => [
                    'locale' => $locale,
                ]]
            );

            // Also sync to wiki, which doesn't have fr-BE either.
            try{
                $api = MediawikiApi::newFromApiEndpoint(env('WIKI_URL').'/api.php');
                $token = session('mediawiki_token');
                Log::info("Got MW token $token");

                // MediawikiApi doesn't expose logging in via a token, but we can do this via a post.
                $result = $api->postRequest( new SimpleRequest( 'login', [
                    'lgname' => $userName,
                    'lgpassword' => session('mediawiki_password'),
                    'lgtoken' => $api->getToken('login')
                ]));

                Log::info("Login returned " . var_export($result, TRUE));

                // We can only change the password of the currently logged in user.  Fortunately we logged in to the
                // wiki when we logged in to Laravel.  That's just as well, since the password is hashed and not
                // accessible.
                $token = $api->getToken('csrf');

                $changeLanguageRequest = FluentRequest::factory()
                    ->setAction('options')
                    ->setParam('token', $token)
                    ->setParam('optionname', 'language')
                    ->setParam('optionvalue', $locale);
                $api->postRequest($changeLanguageRequest);
                Log::info("Changed language for user '$userName' in mediawiki to $locale");
            } catch (\Exception $ex) {
                Log::error("Failed to change language for user '$userName' in mediawiki to $locale: " . $ex->getMessage());
            }
        } catch (\Exception $ex) {
            Log::error('Could not sync '.$user->id.' language to Discourse: '.$ex->getMessage());
        }
    }

    public function onUserRegistered(UserRegistered $event)
    {
        $user = $event->user;

        try {
            $this->syncSso($user);
        } catch (\Exception $ex) {
            Log::error('Could not sync '.$user->id.' to Discourse: '.$ex->getMessage());
        }
    }

    protected function syncSso($user)
    {
        $endpoint = '/admin/users/sync_sso';

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

        $response = $this->discourseClient->request(
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
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        if (config('restarters.features.discourse_integration') === true) {
            $events->listen(
                'App\Events\UserEmailUpdated',
                'App\Listeners\DiscourseUserEventSubscriber@onUserEmailUpdated'
            );

            $events->listen(
                'App\Events\UserLanguageUpdated',
                'App\Listeners\DiscourseUserEventSubscriber@onUserLanguageUpdated'
            );

            $events->listen(
                'App\Events\UserRegistered',
                'App\Listeners\DiscourseUserEventSubscriber@onUserRegistered'
            );
        }
    }
}
