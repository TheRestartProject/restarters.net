<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\WikiSyncStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mediawiki\Api\ApiUser;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;

class ChangeWikiPassword extends BaseEvent
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  PasswordChanged  $event
     * @return void
     */
    public function handle(PasswordChanged $event)
    {
        $user = $event->user;
        $oldpw = $event->oldPassword;

        if ($user->wiki_sync_status !== WikiSyncStatus::Created) {
            Log::info("No wiki account for '".$user->username."' - not attempting to change password");

            return;
        }

        try {
            $api = MediawikiApi::newFromApiEndpoint(env('WIKI_URL').'/api.php');

            $api->login(new ApiUser($user->mediawiki, $oldpw));
            $token = $api->getToken('csrf');

            // The Mediawiki new password is the Laravel hashed password.
            $changePasswordRequest = FluentRequest::factory()
                                   ->setAction('changeauthenticationdata')
                                   ->setParam('changeauthrequest', 'MediaWiki\Auth\PasswordAuthenticationRequest')
                                   ->setParam('password', $user->password)
                                   ->setParam('retype', $user->password)
                                   ->setParam('changeauthtoken', $token);
            $api->postRequest($changePasswordRequest);
        } catch (\Exception $ex) {
            Log::error("Failed to changed password for user '".$user->mediawiki."' in mediawiki: ".$ex->getMessage());
        }
    }
}
