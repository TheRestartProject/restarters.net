<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App;
use Auth;
use Closure;
use Illuminate\Support\Facades\Config;
use Jenssegers\Agent\Agent;
use LaravelLocalization;

class LanguageSwitcher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // A query string parameter of locale overrides all other places to attempt to determine the locale.
        if ($request->exists('locale')) {
            $locale = $request->get('locale');

            if (in_array($locale, LaravelLocalization::getSupportedLanguagesKeys())) {
                $this->setLocale($locale);
            }
        // We have a 'UT' locale for testing.
        } elseif (session('locale') && session('locale') != 'UT') {
            // Otherwise, check if locale session already exists.
            // If it does, continue to maintain that locale.
            App::setLocale(session('locale'));
            LaravelLocalization::setLocale(session('locale'));
        } else {
            // OK, we know nothing about preferred locale.
            // Check if anything provided by the browser.
            $agent = new Agent();

            // Check to see whether any default languages exist on browser
            if (! empty($agent->languages())) {
                // Loop through all languages until we hit a match
                foreach ($agent->languages() as $locale) {
                    // Check to see what supported languages there are before committing
                    if (in_array($locale, LaravelLocalization::getSupportedLanguagesKeys())) {
                        $this->setLocale($locale);
                        // Break loop and continue with next request
                        return $next($request);
                    }
                }
            }
        }

        return $next($request);
    }

    protected function setLocale($locale)
    {
        // Store in session
        session()->put('locale', $locale);

        // Set locale on web app
        App::setLocale($locale);

        // Set locale on localisation package
        LaravelLocalization::setLocale($locale);

        // Set in database
        if (! Auth::guest()) {
            Auth::user()->update([
                'language' => $locale,
            ]);
        }
    }

    // $language = $agent->languages(); // Browser language
    //
    // $browser = $agent->browser(); // Get User Browser
    // $version = $agent->version($browser); // Get Browser Version
    //
    // $platform = $agent->platform(); // Get User OS
    // $version = $agent->version($platform); // Get User OS version
}
