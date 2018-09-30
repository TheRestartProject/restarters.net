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

class LogSuccessfulLogin
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

        $u = User::find($event->user->id);
        $u->number_of_logins += 1;
        $u->save();

        if( !is_null($u->mediawiki) && !empty($u->mediawiki) ) {

          try {
            $api = MediawikiApi::newFromApiEndpoint( env('WIKI_URL').'/api.php' );
            $api->login( new ApiUser( $u->mediawiki, $this->request->input('password') ) );

            $cookieJar = $api->getClient()->getConfig('cookies');
            $cookieJarArray = $cookieJar->toArray();

            if( !empty($cookieJarArray) ) {

                foreach( $cookieJarArray as $cookie ){
                Cookie::queue(Cookie::make($cookie['Name'], $cookie['Value'], $cookie['Expires']));
                }

            }
          } catch (\Exception $ex) {
            Log::error("Failed to log user '" . $u->mediawiki . "' in to mediawiki: " . $ex->getMessage());
          }

        }

    }
}
