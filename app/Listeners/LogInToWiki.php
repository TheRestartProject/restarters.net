<?php

namespace App\Listeners;

use App\WikiSyncStatus;
use Cookie;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Client\MediaWiki;
use Addwiki\Mediawiki\Api\Service\UserCreator;

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
    public function __construct(Request $request, ?UserCreator $mediawikiUserCreator)
    {
        $this->request = $request;
        $this->wikiUserCreator = $mediawikiUserCreator;
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            // If Wiki integration is not available, just return without error
            if ($this->wikiUserCreator === null) {
                Log::info("Wiki integration not available - skipping wiki login");
                return;
            }

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
        } catch (\Exception $ex) {
            // Log the error but don't let it break the user's login
            Log::error("Wiki login failed but user login will continue: " . $ex->getMessage());
        }
    }

    protected function createUserInWiki($user)
    {
        try {
            if (!$this->wikiUserCreator) {
                Log::error("Wiki UserCreator not available - cannot create user '".$user->username."' in mediawiki");
                return;
            }

            // Mediawiki does strange things with underscores.
            $mediawikiUsername = str_replace('_', '-', $user->username);
            $this->wikiUserCreator->create($mediawikiUsername, $user->password, $user->email);

            $user->wiki_sync_status = WikiSyncStatus::Created;
            $user->mediawiki = $mediawikiUsername;
            $user->save();
        } catch (\Throwable $ex) {
            Log::error("Failed to create new account for user '".$user->username."' in mediawiki: ".$ex->getMessage());
        }
    }

    protected function logUserIn($user)
    {
        try {
            Log::info("Log in to wiki $user->mediawiki");
            $mw = MediaWiki::newFromEndpoint(
                env('WIKI_URL').'/api.php',
                new UserAndPassword($user->mediawiki, $user->password)
            );
            $api = $mw->action();
            $api->getToken('csrf');
            Log::info("Logged in to wiki $user->mediawiki");

            $cookieJar = $api->getClient()->getConfig('cookies');
            $cookieJarArray = $cookieJar->toArray();

            if (! empty($cookieJarArray)) {
                foreach ($cookieJarArray as $cookie) {
                    Cookie::queue(Cookie::make($cookie['Name'], $cookie['Value'], $cookie['Expires']));
                }
            }
        } catch (\Throwable $ex) {
            Log::error("Failed to log user '".$user->mediawiki."' in to mediawiki: ".$ex->getMessage());
        }
    }
}
