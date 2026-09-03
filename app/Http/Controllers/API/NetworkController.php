<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use App\Group;
use App\GroupTags;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagCollection;
use App\Http\Resources\Tag as TagResource;
use App\Network;
use App\Party;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NetworkController extends Controller
{
    public function stats(Request $request, Network $network): JsonResponse
    {
        if (! Auth::user()->can('view', $network)) {
            abort(403, 'You do not have access to this network');
        }

        // If tag filter specified, compute stats only for groups with that tag
        // Accept both 'tag' (legacy) and 'group_tag' parameter names
        if ($request->has('group_tag') || $request->has('tag')) {
            $tagId = $request->get('group_tag', $request->get('tag'));
            return response()->json($this->statsForTag($network, $tagId));
        }

        return response()->json($network->stats());
    }

    /**
     * Get stats for groups in a network that have a specific tag.
     */
    private function statsForTag(Network $network, int $tagId): array
    {
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        // Get groups in this network that have the specified tag
        $groups = $network->groups()
            ->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
            ->where('grouptags_groups.group_tag', $tagId)
            ->get();

        $allStats = \App\Group::bulkGroupStats($groups, $eEmissionRatio, $uEmissionratio);

        $stats = \App\Group::getGroupStatsArrayKeys();

        foreach ($allStats as $singleGroupStats) {
            foreach ($singleGroupStats as $key => $value) {
                $stats[$key] = $stats[$key] + $value;
            }
        }

        return $stats;
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
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
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
     *      @OA\Parameter(
     *          name="includeStats",
     *          description="Include the stats for each group.  This makes the call significantly slower.  Default false.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="boolean"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="includeArchived",
     *          description="Include archived groups",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="boolean"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="updated_start",
     *          description="The minimum start date for when a group was updated in ISO8601 format.  Useful if you need to only process groups that have had recent changes.  Inclusive.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="2022-09-18T11:30:00+00:00"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="updated_end",
     *          description="The maximum end date for when a group was updated in ISO8601 format.  Inclusive.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="2022-09-18T12:30:00+00:00"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="group_tag",
     *          description="Filter by group tag ID. Only groups with this tag will be returned.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                 property="data",
     *                 title="data",
     *                 description="An array of groups",
     *                 type="array",
     *                 @OA\Items(
     *                    oneOf={
     *                        @OA\Schema(
     *                          ref="#/components/schemas/GroupSummary"
     *                        ),
     *                        @OA\Schema(
     *                          ref="#/components/schemas/Group"
     *                        ),
     *                    },
     *                 )
     *              )
     *          )
     *       ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
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
        $query = Group::join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where('group_network.network_id', $id)
            ->where('groups.updated_at', '>=', $start)
            ->where('groups.updated_at', '<=', $end);

        if (!$request->has('includeArchived') || $request->get('includeArchived') == 'false') {
            $query = $query->whereNull('archived_at');
        }

        // Filter by group tag if specified
        if ($request->has('group_tag')) {
            $tagId = $request->get('group_tag');
            $query = $query->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
                ->where('grouptags_groups.group_tag', $tagId);
        }

        $groups = $query->select('groups.*')->get();

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
     *      @OA\Parameter(
     *          name="group_tag",
     *          description="Filter by group tag ID. Only events from groups with this tag will be returned.",
     *          required=false,
     *          in="query",
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
     *                description="An array of events",
     *                type="array",
     *                @OA\Items(
     *                    oneOf={
     *                        @OA\Schema(
     *                          ref="#/components/schemas/EventSummary"
     *                        ),
     *                        @OA\Schema(
     *                          ref="#/components/schemas/Event"
     *                        ),
     *                    },
     *                 )
     *              )
     *          )
     *       ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
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
        //
        // Both PartySummary and Party (full) call getEventStats() per event, which needs allDevices
        // and allInvited loaded or it's an N+1 - same eager-load as GroupController::getEventsForGroupv2
        // and Group::bulkGroupStats(). This endpoint has no upper bound on the date range, so an
        // unfiltered network can return a lot of events.
        //
        // theGroup is also rendered per event (via GroupSummary), and unlike
        // getEventsForGroupv2 every event here can belong to a DIFFERENT group - so without
        // eager-loading it (and the relations GroupSummary itself touches unconditionally:
        // groupImage.image, networks - same set listSummaryv2 eager-loads) this scales with
        // event count too, just less obviously than the stats N+1.
        $query = Party::join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->with('allDevices', 'theGroup.networks', 'theGroup.groupImage.image')
            ->withCount('allInvited')
            ->where('group_network.network_id', $id)
            ->where('event_start_utc', '>=', $start)
            ->where('event_end_utc', '<=', $end)
            ->where('events.updated_at', '>=', $updated_start)
            ->where('events.updated_at', '<=', $updated_end)
            ->where('events.approved', true)
            ->where('groups.approved', true);

        // Filter by group tag if specified
        if ($request->has('group_tag')) {
            $tagId = $request->get('group_tag');
            $query = $query->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
                ->where('grouptags_groups.group_tag', $tagId);
        }

        // addSelect(), not select() - select() replaces the whole column list, which would
        // silently discard the all_invited_count column withCount('allInvited') staged above via
        // its own addSelect() (found by diffing the actual SQL between the 3-event and 6-event
        // runs of testEventsQueryCountDoesNotScaleWithEventCount: it grew by exactly a
        // lazy-loaded events_users query per new event - withCount() was running but its result
        // was being thrown away before the query even executed).
        $events = $query->addSelect('events.*')->get();

        if ($request->get('includeDetails', false)) {
            return \App\Http\Resources\PartyCollection::make($events);
        } else {
            return \App\Http\Resources\PartySummaryCollection::make($events);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v2/networks/{id}/tags",
     *      operationId="getNetworkTags",
     *      tags={"Networks"},
     *      summary="Get Network Tags",
     *      description="Returns list of tags available for a network. Administrators see global + network tags; others see only network-specific tags.",
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
     *          name="network_only",
     *          description="Only return tags specific to this network (exclude global tags)",
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
     *                description="An array of tags",
     *                type="array",
     *                @OA\Items(
     *                    ref="#/components/schemas/Tag"
     *                 )
     *              )
     *          )
     *       ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     )
     */
    public function getNetworkTagsv2(Request $request, $id)
    {
        $network = Network::findOrFail($id);

        // Return tags belonging to this network (public - tags are visible on group pages)
        $tags = GroupTags::forNetwork($id)->get();

        return TagCollection::make($tags);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/networks/{id}/stats",
     *      operationId="getNetworkStats",
     *      tags={"Networks"},
     *      summary="Get Network Stats",
     *      description="Returns impact statistics for a network, optionally filtered by group tag.",
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
     *          name="group_tag",
     *          description="Filter stats to only include groups with this tag ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="co2_total", type="number", example=17631.6),
     *              @OA\Property(property="co2_powered", type="number", example=17475.97),
     *              @OA\Property(property="co2_unpowered", type="number", example=155.64),
     *              @OA\Property(property="waste_total", type="number", example=2021.72),
     *              @OA\Property(property="waste_powered", type="number", example=1952.1),
     *              @OA\Property(property="waste_unpowered", type="number", example=69.62),
     *              @OA\Property(property="fixed_devices", type="integer", example=680),
     *              @OA\Property(property="fixed_powered", type="integer", example=667),
     *              @OA\Property(property="fixed_unpowered", type="integer", example=13),
     *              @OA\Property(property="repairable_devices", type="integer", example=520),
     *              @OA\Property(property="dead_devices", type="integer", example=178),
     *              @OA\Property(property="unknown_repair_status", type="integer", example=22),
     *              @OA\Property(property="devices_powered", type="integer", example=610),
     *              @OA\Property(property="devices_unpowered", type="integer", example=90),
     *              @OA\Property(property="no_weight_powered", type="integer", example=4),
     *              @OA\Property(property="no_weight_unpowered", type="integer", example=1),
     *              @OA\Property(property="participants", type="integer", example=880),
     *              @OA\Property(property="volunteers", type="integer", example=556),
     *              @OA\Property(property="hours_volunteered", type="integer", example=3152),
     *              @OA\Property(property="invited", type="integer", example=940),
     *              @OA\Property(property="parties", type="integer", example=161)
     *          )
     *       ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     )
     */
    public function getNetworkStatsv2(Request $request, $id): JsonResponse
    {
        $network = Network::findOrFail($id);

        if ($request->has('group_tag')) {
            $tagId = $request->get('group_tag');
            return response()->json($this->statsForTag($network, $tagId));
        }

        return response()->json($network->stats());
    }

    /**
     * @OA\Post(
     *      path="/api/v2/networks/{id}/tags",
     *      operationId="createNetworkTag",
     *      tags={"Networks"},
     *      summary="Create Network Tag",
     *      description="Create a new tag for a network. Requires authentication as a Network Coordinator for this network or an Administrator.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Network id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  description="Name of the tag",
     *                  example="Municipality A"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  description="Description of the tag",
     *                  example="Groups managed by Municipality A"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Tag created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                ref="#/components/schemas/Tag"
     *              )
     *          )
     *       ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     )
     */
    public function createNetworkTagv2(Request $request, $id)
    {
        $network = Network::findOrFail($id);
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user is admin or network coordinator for this network
        $isAdmin = $user->hasRole('Administrator');
        $isCoordinator = $user->isCoordinatorOf($network);

        if (!$isAdmin && !$isCoordinator) {
            return response()->json(['message' => 'You do not have permission to create tags for this network'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Check if tag name already exists in this network
        $existingTag = GroupTags::where('tag_name', $request->name)
            ->where('network_id', $id)
            ->first();

        if ($existingTag) {
            return response()->json(['message' => 'A tag with this name already exists in this network'], 422);
        }

        $tag = GroupTags::create([
            'tag_name' => $request->name,
            'description' => $request->description,
            'network_id' => $id,
        ]);

        return TagResource::make($tag)->response()->setStatusCode(201);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/networks/{id}/tags/{tagId}",
     *      operationId="updateNetworkTag",
     *      tags={"Networks"},
     *      summary="Update Network Tag",
     *      description="Update a tag for a network. Requires authentication as a Network Coordinator for this network or an Administrator.",
     *      security={{"apiToken":{}}},
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
     *          name="tagId",
     *          description="Tag id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", example="My Tag"),
     *              @OA\Property(property="description", type="string", example="Description of the tag", nullable=true),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Tag updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                ref="#/components/schemas/Tag"
     *              )
     *          )
     *       ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     )
     */
    public function updateNetworkTagv2(Request $request, $id, $tagId)
    {
        $network = Network::findOrFail($id);
        $tag = GroupTags::findOrFail($tagId);
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check tag belongs to this network
        if ($tag->network_id != $id) {
            return response()->json(['message' => 'Tag does not belong to this network'], 403);
        }

        // Check if user is admin or network coordinator for this network
        $isAdmin = $user->hasRole('Administrator');
        $isCoordinator = $user->isCoordinatorOf($network);

        if (!$isAdmin && !$isCoordinator) {
            return response()->json(['message' => 'You do not have permission to edit tags for this network'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Check if tag name already exists in this network (excluding current tag)
        $existingTag = GroupTags::where('tag_name', $request->name)
            ->where('network_id', $id)
            ->where('id', '!=', $tagId)
            ->first();

        if ($existingTag) {
            return response()->json(['message' => 'A tag with this name already exists in this network'], 422);
        }

        $tag->update([
            'tag_name' => $request->name,
            'description' => $request->description,
        ]);

        return TagResource::make($tag->fresh());
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/networks/{id}/tags/{tagId}",
     *      operationId="deleteNetworkTag",
     *      tags={"Networks"},
     *      summary="Delete Network Tag",
     *      description="Delete a tag from a network. Requires authentication as a Network Coordinator for this network or an Administrator. This will remove the tag from all groups.",
     *      security={{"apiToken":{}}},
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
     *          name="tagId",
     *          description="Tag id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Tag deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Tag deleted successfully")
     *          )
     *       ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     )
     */
    public function deleteNetworkTagv2(Request $request, $id, $tagId)
    {
        $network = Network::findOrFail($id);
        $tag = GroupTags::findOrFail($tagId);
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check tag belongs to this network
        if ($tag->network_id != $id) {
            return response()->json(['message' => 'Tag does not belong to this network'], 403);
        }

        // Check if user is admin or network coordinator for this network
        $isAdmin = $user->hasRole('Administrator');
        $isCoordinator = $user->isCoordinatorOf($network);

        if (!$isAdmin && !$isCoordinator) {
            return response()->json(['message' => 'You do not have permission to delete tags for this network'], 403);
        }

        // Delete will cascade to remove group associations due to foreign key
        $tag->delete();

        return response()->json(['message' => 'Tag deleted successfully']);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/networks/{id}/groups",
     *      operationId="associateNetworkGroups",
     *      tags={"Networks"},
     *      summary="Associate groups with a network",
     *      description="Add one or more groups to a network. Requires authentication as a Network Coordinator for this network or an Administrator.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", description="Network id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"groups"},
     *              @OA\Property(property="groups", type="array", minItems=1, @OA\Items(type="integer"), description="Group ids to add to the network. Unknown ids are silently skipped."),
     *          )
     *      ),
     *      @OA\Response(response=200, description="Groups associated",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="associated", type="integer", description="Number of groups actually found and associated")))),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     * )
     *
     * Port of NetworkController::associateGroup (the old session+CSRF web form).
     */
    public function associateGroupsv2(Request $request, $id): JsonResponse
    {
        $network = Network::findOrFail($id);
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $user->hasRole('Administrator') && ! $user->isCoordinatorOf($network)) {
            return response()->json(['message' => 'You do not have permission to add groups to this network'], 403);
        }

        $validated = $request->validate([
            'groups' => 'required|array|min:1',
            'groups.*' => 'integer',
        ]);

        $associated = 0;
        foreach ($validated['groups'] as $groupId) {
            $group = Group::find($groupId);
            if ($group) {
                $network->addGroup($group);
                $associated++;
            }
        }

        return response()->json(['data' => ['associated' => $associated]]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/networks/{id}/logo",
     *      operationId="uploadNetworkLogo",
     *      tags={"Networks"},
     *      summary="Upload a network logo",
     *      description="Set the network's logo from a completed tus upload. Requires authentication as a Network Coordinator for this network or an Administrator.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", description="Network id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"upload_key"},
     *              @OA\Property(property="upload_key", type="string", description="Key of the completed tus upload"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Logo stored",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="logo", type="string")))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     * )
     *
     * Port of NetworkController::update's network_logo handling: stores the
     * image under network_logos/ (with a -_x100 sized copy) and sets
     * network->logo. The SPA uploads the file via tus first, then calls this
     * with the resulting upload_key.
     */
    public function uploadLogov2(Request $request, $id): JsonResponse
    {
        $network = Network::findOrFail($id);
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! $user->hasRole('Administrator') && ! $user->isCoordinatorOf($network)) {
            return response()->json(['message' => 'You do not have permission to edit this network'], 403);
        }

        $validated = $request->validate([
            'upload_key' => 'required|string',
        ]);

        if (! config('restarters.features.image_upload')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'upload_key' => [__('events.image_upload_error')],
            ]);
        }

        // Resolve (and validate: complete, <=2MB, image mime) the tus upload.
        $tusPath = EventAttendanceController::validatedTusFilePath($validated['upload_key'], 'events');

        $extByMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];
        $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tusPath);
        $ext = $extByMime[$mime] ?? 'jpg';

        // Same disk selection as the old web controller (s3 on Fly, else public).
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public_uploads';
        $storage = \Illuminate\Support\Facades\Storage::disk($disk);

        $path = 'network_logos/'.\Illuminate\Support\Str::random(40).'.'.$ext;
        if (! $storage->put($path, file_get_contents($tusPath))) {
            abort(500, 'Failed to save logo');
        }

        // Generate the _x100 sized version by copying the file (matches the
        // old controller; the sized image is served at that derived path).
        $sizedPath = preg_replace('/\.([^.\s]{3,4})$/', '-_x100.$1', $path);
        $storage->copy($path, $sizedPath);

        $network->logo = $path;
        $network->save();

        // Clean up the consumed tus upload.
        $cache = \App\Helpers\Tus::buildCache();
        $cache->delete($validated['upload_key']);
        @unlink($tusPath);

        return response()->json(['data' => ['logo' => $network->logo]]);
    }
}
