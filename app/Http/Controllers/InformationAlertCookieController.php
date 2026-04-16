<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use Cookie;
use Illuminate\Http\Request;

#[Feature('Platform', description: 'Platform-wide statistics and public impact data')]
class InformationAlertCookieController extends Controller
{
    protected $minute;

    protected $minutes;

    public function __construct()
    {
        $this->minute = 1440; // 1 day

        // $this->minutes = $this->minute * 7; // 7 days

        $this->minutes = $this->minute * 30; // 30 days

      // $this->minutes = $this->minute * 365; // 1 year
    }

    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return View
     */
    #[UserStory('As a Guest, I can dismiss an information alert banner', persona: 'Guest')]
    public function __invoke(Request $request)
    {
        if (! $request->has('dismissable_id')) {
            return response()->json(false);
        }

        if (Cookie::get('information-alert-dismissed-'.$request->input('dismissable_id'))) {
            Cookie::queue(
            Cookie::forget('information-alert-dismissed-'.$request->input('dismissable_id'))
        );
        }

        Cookie::queue('information-alert-dismissed-'.$request->input('dismissable_id'), true, $this->minutes);

        return response()->json(true);
    }
}
