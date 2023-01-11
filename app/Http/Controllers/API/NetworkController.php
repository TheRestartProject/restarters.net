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

    /**
     * @OA\Get(
     *      path="/api/v2/networks",
     *      operationId="getNetworks",
     *      tags={"Networks"},
     *      summary="Get Networks",
     *      description="Returns list of networks on the platform.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                title="data",
     *                description="An array of networks",
     *                type="array",
     *                @OA\Items(
     *                    ref="#/components/schemas/NetworkSummary"
     *                 )
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Network not found",
     *      ),
     *     )
     */

    public function getNetworksv2()
    {
        $networks = Network::all();
        return \App\Http\Resources\NetworkSummaryCollection::make($networks);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/networks/{id}",
     *      operationId="getNetwork",
     *      tags={"Networks"},
     *      summary="Get Network",
     *      description="Returns information about a network.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Network id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                title="data",
     *                ref="#/components/schemas/Network"
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Event not found",
     *      ),
     *     )
     */

    public function getNetworkv2($id)
    {
        $network = Network::findOrFail($id);
        return \App\Http\Resources\Network::make($network);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/networks/{id}/groups",
     *      operationId="getNetworkGroups",
     *      tags={"Networks"},
     *      summary="Get Network Groups",
     *      description="Returns list of groups for a network.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Network id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="includeNextEvent",
     *          description="Include the next event for the group.  This makes the call slower.  Default false.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="boolean"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="includeDetails",
     *          description="Include the details for each group.  This makes the call significantly slower.  Default false.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="boolean"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="data",
     *                  title="data",
     *                  description="An array of groups",
     *                  oneOf={
     *                      @OA\Schema(
     *                          type="array",
     *                          @OA\Items(
     *                            ref="#/components/schemas/GroupSummary"
     *                          )
     *                      ),
     *                      @OA\Schema(
     *                          type="array",
     *                          @OA\Items(
     *                            ref="#/components/schemas/Group"
     *                          )
     *                      ),
     *                  },
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Network not found",
     *      ),
     *     )
     */

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


        if ($request->get('includeDetails', false)) {
            return \App\Http\Resources\GroupCollection::make($groups);
        } else {
            return \App\Http\Resources\GroupSummaryCollection::make($groups);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v2/networks/{id}/events",
     *      operationId="getNetworkEvents",
     *      tags={"Networks"},
     *      summary="Get Network Events",
     *      description="Returns list of events for a network.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Network id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="start",
     *          description="The minimum start date for an event in ISO8601 format.  Inclusive.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="2022-09-18T11:30:00+00:00"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="end",
     *          description="The maximum end date for an event in ISO8601 format.  Inclusive.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="2022-09-18T12:30:00+00:00"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="updated_start",
     *          description="The minimum start date for when an event was updated in ISO8601 format.  Useful if you need to only process events that have had recent changes.  Inclusive.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="2022-09-18T11:30:00+00:00"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="updated_end",
     *          description="The maximum end date for when an event was updated in ISO8601 format.  Inclusive.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="2022-09-18T12:30:00+00:00"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="includeDetails",
     *          description="Include the details for each event.  This makes the call significantly slower.  Default false.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="boolean"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                title="data",
     *                description="An array of events",
     *                oneOf={
     *                      @OA\Schema(
     *                          type="array",
     *                          @OA\Items(
     *                             ref="#/components/schemas/EventSummary"
     *                          )
     *                      ),
     *                      @OA\Schema(
     *                          type="array",
     *                          @OA\Items(
     *                             ref="#/components/schemas/Event"
     *                          )
     *                      ),
     *                  },
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Network not found",
     *      ),
     *     )
     */

    public function getNetworkEventsv2(Request $request, $id)
    {
        Network::findOrFail($id);

        // Get date filters.  We default to far past and far future so that we don't need multiple code branches.  We
        // don't need to validate the date format - if they put junk in then they'll get junk matches back.
        $start = Carbon::parse($request->get('start', '1970-01-01'))->setTimezone('UTC')->toIso8601String();
        $end = Carbon::parse($request->get('end', '3000-01-01'))->setTimezone('UTC')->toIso8601String();
        $updated_start = Carbon::parse($request->get('updated_start', '1970-01-01'))->setTimezone('UTC')->format('Y-m-d H:i:s');
        $updated_end = Carbon::parse($request->get('updated_end', '3000-01-01'))->setTimezone('UTC')->format('Y-m-d H:i:s');

        // We need to explicity select events.*, otherwise the updated_at values we get back are from the group_network
        // table, which is mightily confusing.  We only want to return approved events on approved groups.
        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where('group_network.network_id', $id)
            ->where('event_start_utc', '>=', $start)
            ->where('event_end_utc', '<=', $end)
            ->where('events.updated_at', '>=', $updated_start)
            ->where('events.updated_at', '<=', $updated_end)
            ->whereNotNull('events.wordpress_post_id')
            ->whereNotNull('groups.wordpress_post_id')
            ->select('events.*')
            ->get();

        if ($request->get('includeDetails', false)) {
            return \App\Http\Resources\PartyCollection::make($events);
        } else {
            return \App\Http\Resources\PartySummaryCollection::make($events);
        }
    }
}
