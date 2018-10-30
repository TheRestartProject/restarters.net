<?php

namespace App\Http\Controllers;


use Jenssegers\Agent\Agent;
use Closure;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Redirect;
use Auth;
use Mcamara\LaravelLocalization;
use Illuminate\Foundation\Application;


class LocaleController extends Controller
{
    public function handle(Request $request)
    {
      if(!Session::has('locale'))
      {
          Session::put('locale', $request->input('language'));
      }

      else
      {
          Session::set('locale', $request->input('language'));
      }

        return response()->json('success');
    }
    // $agent = new Agent();
    // $language = $agent->language(); // Browser language
    //
    // $browser = $agent->browser(); // Get User Browser
    // $version = $agent->version($browser); // Get Browser Version
    //
    // $platform = $agent->platform(); // Get User OS
    // $version = $agent->version($platform); // Get User OS version
}
