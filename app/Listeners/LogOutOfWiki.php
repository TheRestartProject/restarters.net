<?php

namespace App\Listeners;

use Cookie;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Don't extend BaseEvent - we don't want to queue because this needs to happen before we return to the client.
class LogOutOfWiki
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
    public function handle(Logout $event): void
    {
        $user = $event->user;

        try {
            foreach ($this->mediawikiCookieNames as $cookieName) {
                $cookieFullName = config('restarters.wiki.cookie_prefix').'_'.$cookieName;
                \Cookie::queue(\Cookie::forget($cookieFullName));
            }
        } catch (\Exception $ex) {
            Log::error("Failed to log user '".$user->mediawiki."' out of mediawiki: ".$ex->getMessage());
        }
    }

    // Mediawiki sets these cookies at login, prefixed by the name of the wiki DB.
    private $mediawikiCookieNames = [
        'mw__session',
        'mw_Token',
        'mw_UserID',
        'mw_UserName',
    ];
}
