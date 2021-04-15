<?php

namespace App\Http\Controllers\API;

use App\Helpers\FootprintRatioCalculator;

use App\Http\Controllers\Controller;
use App\Network;

use Auth;

class NetworkController extends Controller
{
    public function stats(Network $network)
    {
        if ( ! Auth::user()->can('view', $network)) {
            abort(403, 'You do not have access to this network');
        }

        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();

        return response()->json($network->stats($emissionRatio));
    }
}
