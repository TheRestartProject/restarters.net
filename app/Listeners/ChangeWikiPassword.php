<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\Services\WikiHttpClient;
use App\User;
use App\WikiSyncStatus;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Addwiki\Mediawiki\Api\Client\Action\ActionApi;
use Addwiki\Mediawiki\Api\Client\Action\Request\ActionRequest;
use Addwiki\Mediawiki\Api\Client\Auth\UserAndPassword;

class ChangeWikiPassword extends BaseEvent
{
    use InteractsWithQueue;

    // If this is lost the user's wiki password stays the old one for good, and every later login to the wiki
    // fails. The wiki has turned it down with 429 and 503 when busy, so keep trying for a couple of hours. Each
    // retry is a release rather than a thrown exception, which the queue worker would report every time.
    public const RETRY_DELAYS = [60, 300, 1800, 3600];

    public $tries = 5;

    // Longer than all the retries, plus time in the queue.
    private const PENDING_SECONDS = 3 * 3600;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    // In the cache, so the web side and the queue worker must share one: they do while live runs both on one
    // machine with the file cache.
    public static function markPending(User $user): void
    {
        Cache::put(self::pendingKey($user), true, self::PENDING_SECONDS);
    }

    public static function isPending(User $user): bool
    {
        return Cache::has(self::pendingKey($user));
    }

    private static function pendingKey(User $user): string
    {
        return 'wiki_password_change_pending_'.$user->id;
    }

    /**
     * Handle the event.
     */
    public function handle(PasswordChanged $event): void
    {
        // Re-read from the database when the queued job was unserialised.
        $user = $event->user;

        // An account found out of sync may have been a login that got in before this change: try it anyway.
        if (! in_array($user->wiki_sync_status, [WikiSyncStatus::Created, WikiSyncStatus::PasswordOutOfSync])) {
            Log::info("No wiki account for '".$user->username."' - not attempting to change password");
            Cache::forget(self::pendingKey($user));

            return;
        }

        try {
            $this->change($user, $event->oldPassword);
            $this->synced($user);
        } catch (\Throwable $ex) {
            if (LogInToWiki::isWrongPassword($ex)) {
                // The old password doesn't work. An earlier try may have changed it before failing; otherwise the
                // wiki was already out of sync.
                try {
                    $this->api($user->mediawiki, $user->password)->getToken('csrf');
                    $this->synced($user);

                    return;
                } catch (\Throwable $ex2) {
                    if (LogInToWiki::isWrongPassword($ex2)) {
                        Cache::forget(self::pendingKey($user));
                        LogInToWiki::passwordOutOfSync($user);

                        return;
                    }

                    $ex = $ex2;
                }
            }

            // Probably the wiki being busy or down.
            $this->tryAgainLater($user, $ex);
        }
    }

    private function change(User $user, string $oldPassword): void
    {
        $api = $this->api($user->mediawiki, $oldPassword);
        $token = $api->getToken('csrf');

        // The Mediawiki new password is the Laravel hashed password.
        $api->request(ActionRequest::simplePost('changeauthenticationdata', [
            'changeauthrequest' => 'MediaWiki\Auth\PasswordAuthenticationRequest',
            'password' => $user->password,
            'retype' => $user->password,
            'changeauthtoken' => $token,
        ]));
    }

    private function synced(User $user): void
    {
        Cache::forget(self::pendingKey($user));

        if ($user->wiki_sync_status != WikiSyncStatus::Created) {
            $user->wiki_sync_status = WikiSyncStatus::Created;
            $user->save();
        }
    }

    private function tryAgainLater(User $user, \Throwable $ex): void
    {
        // The sync queue (used off production) runs it once, with no later.
        if ($this->job && ! $this->job instanceof SyncJob && $this->attempts() < $this->tries) {
            $this->release(self::RETRY_DELAYS[$this->attempts() - 1]);

            return;
        }

        Log::warning('Could not change the wiki password', ['wiki_user' => $user->mediawiki, 'error' => $ex->getMessage()]);
        Cache::forget(self::pendingKey($user));
        LogInToWiki::passwordOutOfSync($user);
    }

    private function api(string $username, string $password): ActionApi
    {
        return new ActionApi(env('WIKI_URL').'/api.php', new UserAndPassword($username, $password), WikiHttpClient::make());
    }
}
