<?php

namespace App\Listeners;

use App\Events\PasswordChanged;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;


class ChangeWikiPassword
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

        if ($user->wiki_sync_status !== WikiSyncStatus::Created) {
            Log::info("No wiki account for '". $user->username."' - not attempting to change password");
            return;
        }

        try{
            $api = MediawikiApi::newFromApiEndpoint(env('WIKI_URL').'/api.php');
            $api->login(new ApiUser($user->mediawiki, $this->request->input('current-password')));
            $token = $api->getToken('csrf');

            $changePasswordRequest = FluentRequest::factory()
                                   ->setAction('changeauthenticationdata')
                                   ->setParam('changeauthrequest', 'MediaWiki\Auth\PasswordAuthenticationRequest')
                                   ->setParam('password', $this->request->input('new-password'))
                                   ->setParam('retype', $this->request->input('new-password'))
                                   ->setParam('changeauthtoken', $token);
            $result = $api->postRequest($changePasswordRequest);
        } catch (\Exception $ex) {
            Log::error("Failed to changed password for user '" . $user->mediawiki . "' in mediawiki: " . $ex->getMessage());
        }
    }
}
