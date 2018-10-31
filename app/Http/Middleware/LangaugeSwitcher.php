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
        // Check to see whether locale session already exists
        if ( session('locale') ) {

          // If it does, continue to maintian that locale
          App::setLocale(session('locale'));
          LaravelLocalization::setLocale(session('locale'));

        } else {

          // First visit, let's see what languages are behind the scenes
          $agent = new Agent();

          // Check to see whether any default languages exist on browser
          if( !empty( $agent->languages() ) ) {

            // Loop through all languages until we hit a match
            foreach( $agent->languages() as $language => $locale ){

              // Check to see what supported languages there are before committing
              if( in_array($locale, LaravelLocalization::getSupportedLanguagesKeys()) ){

                // Store in session
                session()->put('locale', $locale);

                // Set locale on web app
                App::setLocale($locale);

                // Set locale on localisation package
                LaravelLocalization::setLocale($locale);

                // Break loop and continue with next request
                return $next($request);

              }

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
