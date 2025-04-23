<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\WikiSyncStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;

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
     */
    public function handle(PasswordChanged $event): void
    {
        $user = $event->user;
        $oldpw = $event->oldPassword;

        if ($user->wiki_sync_status !== WikiSyncStatus::Created) {
            Log::info("No wiki account for '".$user->username."' - not attempting to change password");

            return;
        }

        try {
            // Create a new MediaWiki client with the Action API using v3 methods
            $apiUrl = env('WIKI_URL').'/api.php';
            $auth = new UserAndPassword($user->mediawiki, $oldpw);
            $api = new ActionApi($apiUrl, $auth);
            
            $token = $api->getToken('csrf');

            // The Mediawiki new password is the Laravel hashed password.
            $params = [
                'changeauthrequest' => 'MediaWiki\Auth\PasswordAuthenticationRequest',
                'password' => $user->password,
                'retype' => $user->password,
                'changeauthtoken' => $token
            ];
            
            $changePasswordRequest = ActionRequest::simplePost('changeauthenticationdata', $params);

            $api->request($changePasswordRequest);
        } catch (\Exception $ex) {
            Log::error("Failed to changed password for user '".$user->mediawiki."' in mediawiki: ".$ex->getMessage());
        }
    }
}
