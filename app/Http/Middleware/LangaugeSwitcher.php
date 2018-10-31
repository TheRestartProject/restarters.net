<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Illuminate\Support\Facades\Config;
use LaravelLocalization;
use Jenssegers\Agent\Agent;

class LangaugeSwitcher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ( session('locale') ) {

          App::setLocale(session('locale'));
          LaravelLocalization::setLocale(session('locale'));

        } else {
          $agent = new Agent();
          foreach( $languages = $agent->languages() as $language => $value ){
            if( in_array($value, LaravelLocalization::getSupportedLanguagesKeys()) ){

              App::setLocale(session('locale', $value));
              LaravelLocalization::setLocale(session('locale', $value));

            }
          }
        }
        return $next($request);
    }




    // $language = $agent->languages(); // Browser language
    //
    // $browser = $agent->browser(); // Get User Browser
    // $version = $agent->version($browser); // Get Browser Version
    //
    // $platform = $agent->platform(); // Get User OS
    // $version = $agent->version($platform); // Get User OS version



}
