<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;
use Illuminate\Http\Request;

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
        ////setup cookie
        //  // $CookieFilePath = tempnam("/tmp", "TMP0");
        //  // $expire = 60*60*24*14 + time();
        //  // $CookiePrefix = 'media_wiki';

        //$ch = curl_init();

        ////Token fields
        //$get_token_fields = 'action=query&meta=tokens&type=login&format=json';

        ////Mediawiki url will look something like this https://www.mediawiki.org/w/api.php
        //curl_setopt($ch, CURLOPT_URL, env('MEDIAWIKI_API_URL'));

        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch,CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, "$get_token_fields");

        //// curl_setopt($ch, CURLOPT_COOKIEJAR, $CookieFilePath);
        //// curl_setopt($ch, CURLOPT_COOKIEFILE, $CookieFilePath);

        ////Retrieve login token for CSRF protection
        //  try {
        //    $login_html = curl_exec($ch);

        //    if(isset(json_decode($login_html)->query->tokens->logintoken)) {
        //      $login_token = json_decode($login_html)->query->tokens->logintoken;
        //    } else {
        //      $login_token = null;
        //    }
        //  } catch (\Exception $e) {
        //    $login_token = null;
        //  }

        //$get_token_fields = 'action=checktoken&type=csrf&token='.urlencode($login_token).'&format=json';
        //// $get_token_fields = 'action=query&meta=siteinfo&format=json';

        //curl_setopt($ch, CURLOPT_POSTFIELDS, "$get_token_fields");

        //dd(json_decode(curl_exec($ch)));


        ////Check that the login token has been retrieved successfully
        //  if (!is_null($login_token)) {

        //    // // cookie must be set using session id from first response
        //    // $_SESSION["logintoken"] = $login_token;
        //    // //How can I get sessionid?
        //    // $sessionid = session_id();
        //    // $_SESSION["sessionid"] = $sessionid;
        //    //
        //    // setcookie("${CookiePrefix}_Session",$sessionid , $expire, '/', env('MEDIAWIKI_API_URL'));
        //    // setcookie("${CookiePrefix}UserName",$this->request->input('email'), $expire,'/',env('MEDIAWIKI_API_URL'));
        //    // setcookie("${CookiePrefix}Token", $_SESSION["logintoken"], $expire, '/', env('MEDIAWIKI_API_URL'));

        //    try {

        //      $post_fields = 'logintoken='.$login_token.'&username='.$this->request->input('email').'&password='.$this->request->input('password');

        //      curl_setopt($ch, CURLOPT_URL, env('MEDIAWIKI_API_URL').'?action=clientlogin&loginreturnurl='.env('MEDIAWIKI_API_URL').'&format=json');
        //      // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //      //        'Content-Type: application/x-www-form-urlencoded',
        //      //         'Content-Length: ' .strlen($post_fields))
        //      // );

        //      curl_setopt($ch,CURLOPT_POSTFIELDS, $post_fields);
        //      // curl_setopt($ch, CURLOPT_COOKIE, "${CookiePrefix}_session=$sessionid");

        //      // curl_setopt($ch, CURLOPT_COOKIEJAR, $CookieFilePath);
        //      // curl_setopt($ch, CURLOPT_COOKIEFILE, $CookieFilePath);

        //      $output = curl_exec($ch);
        //      dd(json_decode($output));
        //    } catch (\Exception $e) {
        //      dd($e);
        //    }

        //  }

        //curl_close($ch);

        $u = User::find($event->user->id);
        $u->number_of_logins += 1;
        $u->save();
    }
}
