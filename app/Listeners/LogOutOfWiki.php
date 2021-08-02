<?php

namespace App\Listeners;

use App\User;
use App\WikiSyncStatus;
use Cookie;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

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
     *
     * @param  Logout  $event
     * @return void
     */
    public function handle(Logout $event)
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
