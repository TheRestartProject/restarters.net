<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Cookie;
use Illuminate\Http\Request;

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
    public function __invoke(Request $request): JsonResponse
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
