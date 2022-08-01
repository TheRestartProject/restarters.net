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

    public function getNetworksv2()
    {
        $networks = Network::all();
        return \App\Http\Resources\NetworkSummaryCollection::make($networks);
    }

    public function getNetworkv2($id)
    {
        $network = Network::findOrFail($id);
        return \App\Http\Resources\Network::make($network);
    }

    public function getNetworkGroupsv2($id)
    {
        $network = Network::findOrFail($id);
        return \App\Http\Resources\GroupSummaryCollection::make($network->groups);
    }
}
