<?php

namespace App\Http\Controllers;

use App\Device;
use App\Group;
use App\Helpers\Fixometer;
use App\Party;
use App\Providers\RouteServiceProvider;
use Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            // We're logged in.  Go to the dashboard.
            return redirect(RouteServiceProvider::HOME);
        } else {
            // We're logged out. Render the landing page.
            $stats = Fixometer::loginRegisterStats();

            return view('landing', [
                'co2Total' => $stats['co2Total'],
                'wasteTotal' => $stats['wasteTotal'],
                'partiesCount' => $stats['partiesCount'],
                'deviceCount' => $stats['deviceCount'],
            ]);
        }
    }
}
