<?php

namespace App\Http\Controllers;

use App;
use App\Attributes\Feature;
use App\Attributes\UserStory;
use Auth;
use LaravelLocalization;

#[Feature('Platform', description: 'Platform-wide statistics and public impact data')]
class LocaleController extends Controller
{
    #[UserStory('As a Guest, I can switch the application language', persona: 'Guest', theme: 'Language preferences')]
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
