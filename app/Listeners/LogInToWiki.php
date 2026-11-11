<?php

namespace App\Listeners;

use App\WikiSyncStatus;
use Cookie;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\Action\Exception\UsageException;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;
use Addwiki\Mediawiki\Api\Service\UserCreator;
use App\Services\WikiHttpClient;
use GuzzleHttp\Cookie\CookieJar;

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
            Log::error('Wiki login failed but user login will continue', ['error' => $ex->getMessage()]);
        }
    }

    protected function createUserInWiki($user)
    {
        try {
            if (!$this->wikiUserCreator) {
                Log::error('Wiki UserCreator not available - cannot create the user in mediawiki', ['user' => $user->id]);
                return;
            }

            // Mediawiki does strange things with underscores.
            $mediawikiUsername = str_replace('_', '-', $user->username);
            $this->wikiUserCreator->create($mediawikiUsername, $user->password, $user->email);

            $user->wiki_sync_status = WikiSyncStatus::Created;
            $user->mediawiki = $mediawikiUsername;
            $user->save();
        } catch (\Throwable $ex) {
            Log::error('Failed to create a new account in mediawiki', ['user' => $user->id, 'username' => $user->username, 'error' => $ex->getMessage()]);
        }
    }

    protected function logUserIn($user)
    {
        try {
            Log::info("Log in to wiki $user->mediawiki");

            $cookieJar = new CookieJar();
            $auth = new UserAndPassword($user->mediawiki, $user->password);
            $api = new ActionApi(env('WIKI_URL').'/api.php', $auth, WikiHttpClient::make($cookieJar));
            $api->getToken('csrf');
            Log::info("Logged in to wiki $user->mediawiki");

            $cookieJarArray = $cookieJar->toArray();

            if (! empty($cookieJarArray)) {
                foreach ($cookieJarArray as $cookie) {
                    Cookie::queue(Cookie::make($cookie['Name'], $cookie['Value'], $cookie['Expires']));
                }
            }
        } catch (\Throwable $ex) {
            if (self::isWrongPassword($ex) && ChangeWikiPassword::isPending($user)) {
                // The wiki has the old password until the queued change reaches it.
                Log::info("Wiki password change for $user->mediawiki not done yet - not logging in to the wiki");
            } elseif (self::isWrongPassword($ex)) {
                self::passwordOutOfSync($user);
            } else {
                Log::error('Failed to log user in to the wiki', ['wiki_user' => $user->mediawiki, 'error' => $ex->getMessage()]);
            }
        }
    }

    /**
     * The wiki turned down the password, which is the user's password hash here.
     */
    public static function isWrongPassword(\Throwable $ex): bool
    {
        return $ex instanceof UsageException && $ex->getApiCode() === 'login-Failed';
    }

    /**
     * The user's wiki password is no longer the hash of their password here: a change of password didn't reach
     * the wiki. We can't put it right from here (the API only lets a user change their own password, and that
     * needs the old one), so it's reported once and the user is left out of wiki login and password sync until
     * the password is reset on the wiki - rather than failing again at every login.
     */
    public static function passwordOutOfSync($user): void
    {
        if ($user->wiki_sync_status == WikiSyncStatus::PasswordOutOfSync) {
            return;
        }

        $user->wiki_sync_status = WikiSyncStatus::PasswordOutOfSync;
        $user->save();

        Log::error('Wiki password out of sync: reset it on the wiki to the password hash here, then set wiki_sync_status back to '.WikiSyncStatus::Created, [
            'user' => $user->id,
            'wiki_user' => $user->mediawiki,
        ]);
    }
}
