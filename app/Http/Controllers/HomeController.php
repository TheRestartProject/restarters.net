<?php

namespace App\Http\Controllers;

use App\Providers\AppServiceProvider;
use App\Models\Device;
use App\Models\Group;
use App\Helpers\Fixometer;
use App\Models\Party;
use Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            // We're logged in.  Go to the dashboard.
            return redirect(AppServiceProvider::HOME);
        } else {
            // We're logged out. Render the landing page.
            $stats = Fixometer::loginRegisterStats();
            $deviceCount = array_key_exists(0, $stats['device_count_status']) ? $stats['device_count_status'][0]->counter : 0;

            return view('landing', [
                'co2Total' => $stats['waste_stats'][0]->powered_footprint + $stats['waste_stats'][0]->unpowered_footprint,
                'wasteTotal' => $stats['waste_stats'][0]->powered_waste + $stats['waste_stats'][0]->unpowered_waste,
                'partiesCount' => count($stats['allparties']),
                'deviceCount' => $deviceCount,
            ]);
        }
    }
}
