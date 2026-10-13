<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use App\Device;
use App\Group;
use App\Helpers\Fixometer;
use App\Party;
use App\Providers\RouteServiceProvider;
use Auth;
use Illuminate\Http\Request;

#[Feature('Platform', description: 'Platform-wide statistics and public impact data')]
class HomeController extends Controller
{
    #[UserStory('As a Guest, I can view the landing page with platform impact statistics', persona: 'Guest', theme: 'Landing page')]
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
