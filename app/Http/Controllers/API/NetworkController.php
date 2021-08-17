<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Network;
use Auth;

class NetworkController extends Controller
{
    public function stats(Network $network)
    {
        if (! Auth::user()->can('view', $network)) {
            abort(403, 'You do not have access to this network');
        }

        return response()->json($network->stats());
    }
}
