<?php

namespace App\Listeners;

use App\WikiSyncStatus;
use Cookie;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mediawiki\Api\ApiUser;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\Service\UserCreator;

// Don't extend BaseEvent - we don't want to queue because this needs to happen before we return to the client.
class LogInToWiki
{
    // We use the Laravel hashed password as the mediawiki password.  This means we can log in to the wiki
    // if we need to, which we wouldn't always be able to do if we used the original password (for example
    // during password reset).  It also avoids Mediawiki complaining about the password being a common one.

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
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user->wiki_sync_status == WikiSyncStatus::CreateAtLogin) {
            Log::info("Need to create " . $user->name);
            $this->createUserInWiki($user);
            $user->refresh();
        }

        if (! is_null($user->mediawiki) && ! empty($user->mediawiki) &&
            $user->wiki_sync_status == WikiSyncStatus::Created) {
            $this->logUserIn($user);
        }
    }

    protected function createUserInWiki($user)
    {
        try {
            // Mediawiki does strange things with underscores.
            $mediawikiUsername = str_replace('_', '-', $user->username);
            $this->wikiUserCreator->create($mediawikiUsername, $user->password, $user->email);

            $user->wiki_sync_status = WikiSyncStatus::Created;
            $user->mediawiki = $mediawikiUsername;
            $user->save();
        } catch (\Exception $ex) {
            Log::error("Failed to create new account for user '".$user->username."' in mediawiki: ".$ex->getMessage());
        }
    }

    protected function logUserIn($user)
    {
        try {
            Log::info("Log in to wiki $user->mediawiki");
            $api = MediawikiApi::newFromApiEndpoint(env('WIKI_URL').'/api.php');
            $api->login(new ApiUser($user->mediawiki, $user->password));
            Log::info("Logged in to wiki $user->mediawiki");

            // NGM: it appears that right from the beginning MediawikiApi->getClient access modifier was changed
            // in our vendor folder from private to public in order to access the underlying client for the cookies.
            // This is really bad, as obviously when we update the package via composer the method changes back to private.
            // Stuck with that until we can figure out an alternative way to access the client.
            $cookieJar = $api->getClient()->getConfig('cookies');
            $cookieJarArray = $cookieJar->toArray();

            if (! empty($cookieJarArray)) {
                foreach ($cookieJarArray as $cookie) {
                    Cookie::queue(Cookie::make($cookie['Name'], $cookie['Value'], $cookie['Expires']));
                }
            }
        } catch (\Exception $ex) {
            Log::error("Failed to log user '".$user->mediawiki."' in to mediawiki: ".$ex->getMessage());
        }
    }
}
