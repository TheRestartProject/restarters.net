<?php

namespace App\Listeners;

use App\User;
use Cookie;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;

use \Mediawiki\Api\ApiUser;
use \Mediawiki\Api\MediawikiApi;

class LogInToWiki
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
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        $user = $event->user;

        if (!is_null($user->mediawiki) && !empty($user->mediawiki)) {
            try {
                $api = MediawikiApi::newFromApiEndpoint(env('WIKI_URL').'/api.php');
                $api->login(new ApiUser($user->mediawiki, $this->request->input('password')));

                $cookieJar = $api->getClient()->getConfig('cookies');
                $cookieJarArray = $cookieJar->toArray();

                if (!empty($cookieJarArray)) {
                    foreach ($cookieJarArray as $cookie) {
                        Cookie::queue(Cookie::make($cookie['Name'], $cookie['Value'], $cookie['Expires']));
                    }
                }
            } catch (\Exception $ex) {
                Log::error("Failed to log user '" . $user->mediawiki . "' in to mediawiki: " . $ex->getMessage());
            }
        }
    }
}
