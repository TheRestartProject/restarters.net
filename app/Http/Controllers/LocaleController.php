<?php

namespace App\Http\Controllers;

use App;
use Auth;
use LaravelLocalization;

class LocaleController extends Controller
{
    public function setLang($locale)
    {
        // Get local from URL and set in the session
        session()->put('locale', $locale);

        // Set app locale
        App::setLocale($locale);
        LaravelLocalization::setLocale($locale);

        // Set in database
        if (! Auth::guest()) {
            Auth::user()->update([
            'language' => $locale,
            ]);
        }

        // Redirect where you came from
        return back();
    }
}
