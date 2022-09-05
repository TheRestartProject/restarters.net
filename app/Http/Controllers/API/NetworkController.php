<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Http\Controllers\Controller;
use App\Network;
use App\Party;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function getNetworkGroupsv2(Request $request, $id)
    {
        $network = Network::findOrFail($id);

        // Get date filters.  We default to far past and far future so that we don't need multiple code branches.  We
        // don't need to validate the date format - if they put junk in then they'll get junk matches back.
        $start = Carbon::parse($request->get('updated_start', '1970-01-01'))->setTimezone('UTC')->toIso8601String();
        $end = Carbon::parse($request->get('updated_end', '3000-01-01'))->setTimezone('UTC')->toIso8601String();

        // We use a query rather than $network->groups so that the filtering by date is done in the database rather
        // than getting all groups and filtering in PHP.  This is faster.
        $groups = Group::join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where('group_network.network_id', $id)
            ->where('groups.updated_at', '>=', $start)
            ->where('groups.updated_at', '<=', $end)->get();

        return \App\Http\Resources\GroupSummaryCollection::make($groups);
    }

    public function getNetworkEventsv2(Request $request, $id)
    {
        Network::findOrFail($id);

        // Get date filters.  We default to far past and far future so that we don't need multiple code branches.  We
        // don't need to validate the date format - if they put junk in then they'll get junk matches back.
        $start = Carbon::parse($request->get('updated_start', '1970-01-01'))->setTimezone('UTC')->format('Y-m-d H:i:s');
        $end = Carbon::parse($request->get('updated_end', '3000-01-01'))->setTimezone('UTC')->format('Y-m-d H:i:s');

        // We need to explicity select events.*, otherwise the updated_at values we get back are from the group_network
        // table, which is mightily confusing.  We only want to return approved events on approved groups.
        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where('group_network.network_id', $id)
            ->where('events.updated_at', '>=', $start)
            ->where('events.updated_at', '<=', $end)
            ->whereNotNull('events.wordpress_post_id')
            ->whereNotNull('groups.wordpress_post_id')
            ->select('events.*')
            ->get();

        return \App\Http\Resources\PartySummaryCollection::make($events);
    }
}
