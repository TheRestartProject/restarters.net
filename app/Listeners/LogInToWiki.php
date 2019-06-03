<?php

namespace App\Listeners;

use App\User;
use App\WikiSyncStatus;

use Cookie;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\ApiUser;
use Mediawiki\Api\Service\UserCreator;

class LogInToWiki
{
    protected $wikiUserCreator;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request, UserCreator $mediawikiUserCreator)
    {
        $this->request = $request;
        $this->wikiUserCreator = $mediawikiUserCreator;
    }

    /**
     * Handle the event.
     *
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        $user = $event->user;

        // TODO: for all of this we need to move to restarters.net being the auth provider for Mediawiki.
        // This is currently a workaround.

        if ($user->wiki_sync_status == WikiSyncStatus::CreateAtLogin) {
            $this->createUserInWiki($user, $this->request->input('password'));
        }

        if (!is_null($user->mediawiki) && !empty($user->mediawiki) &&
            $user->wiki_sync_status == WikiSyncStatus::Created) {
            $this->logUserIn($user->mediawiki, $this->request->input('password'));
        }
    }

    protected function createUserInWiki($user, $password)
    {
        try {
            // Mediawiki does strange things with underscores.
            $mediawikiUsername = str_replace("_", "-", $user->username);
            $this->wikiUserCreator->create($mediawikiUsername, $password, $user->email);

            $user->wiki_sync_status = WikiSyncStatus::Created;
            $user->mediawiki = $mediawikiUsername;
            $user->save();
        } catch (\Exception $ex) {
            Log::error("Failed to create new account for user '" . $user->username . "' in mediawiki: " . $ex->getMessage());
        }
    }

    protected function logUserIn($wikiUsername, $password)
    {
        try {
            $api = MediawikiApi::newFromApiEndpoint(env('WIKI_URL').'/api.php');
            $api->login(new ApiUser($wikiUsername, $password));

            $cookieJar = $api->getClient()->getConfig('cookies');
            $cookieJarArray = $cookieJar->toArray();

            if (!empty($cookieJarArray)) {
                foreach ($cookieJarArray as $cookie) {
                    Cookie::queue(Cookie::make($cookie['Name'], $cookie['Value'], $cookie['Expires']));
                }
            }
        } catch (\Exception $ex) {
            Log::error("Failed to log user '" . $wikiUsername . "' in to mediawiki: " . $ex->getMessage());
        }
    }
}
