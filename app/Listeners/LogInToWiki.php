<?php

namespace App\Listeners;

use App\WikiSyncStatus;
use Cookie;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Service\UserCreator;

// Don't extend BaseEvent - we don't want to queue because this needs to happen before we return to the client.
class LogInToWiki
{
    // We use the Laravel hashed password as the mediawiki password.  This means we can log in to the wiki
    // if we need to, which we wouldn't always be able to do if we used the original password (for example
    // during password reset).  It also avoids Mediawiki complaining about the password being a common one.

    protected $wikiUserCreator;
    protected $request;

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
            
            // Create a new MediaWiki client with the Action API using v3 methods
            $apiUrl = env('WIKI_URL').'/api.php';
            $auth = new UserAndPassword($user->mediawiki, $user->password);
            $api = new ActionApi($apiUrl, $auth);
            
            Log::info("Logged in to wiki $user->mediawiki");

            // Note: In the new version of the library, we can't directly access the cookies
            // We'll try our best, but this may need to be revisited with a different approach
            try {
                $reflection = new \ReflectionClass($api);
                $clientProperty = $reflection->getProperty('client');
                $clientProperty->setAccessible(true);
                $client = $clientProperty->getValue($api);

                if ($client) {
                    $cookieJar = $client->getConfig('cookies');
                    if ($cookieJar) {
                        $cookieJarArray = $cookieJar->toArray();
                        
                        if (!empty($cookieJarArray)) {
                            foreach ($cookieJarArray as $cookie) {
                                Cookie::queue(Cookie::make($cookie['Name'], $cookie['Value'], $cookie['Expires']));
                            }
                        }
                    }
                }
            } catch (\Exception $ex) {
                Log::warning("Could not access MediaWiki cookies: " . $ex->getMessage());
                // Continue without cookies
            }
        } catch (\Exception $ex) {
            Log::error("Failed to log user '".$user->mediawiki."' in to mediawiki: ".$ex->getMessage());
        }
    }
}
