<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;

class InformationAlertCookieController extends Controller
{
  private $minutes;

  public function __construct()
  {
    $this->minutes = 10080; // 7 days
  }
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return View
     */
    public function __invoke(Request $request)
    {
      if ( ! $request->has('dismissable_id') ) {
        return response()->json(false);
      }

      if ( Cookie::get('information-alert-dismissed-'.$request->input('dismissable_id')) ) {
        $cookie = Cookie::queue(
            Cookie::forget('information-alert-dismissed-'.$request->input('dismissable_id'))
        );
      }

      $cookie = Cookie::queue('information-alert-dismissed-'.$request->input('dismissable_id'), true, $this->minutes);

      return response()->json(true);
    }
}
