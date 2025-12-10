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
        if ($request->has('tag')) {
            $tagId = $request->get('tag');
            return response()->json($this->statsForTag($network, $tagId));
        }

        return response()->json($network->stats());
    }

    /**
     * Get stats for groups in a network that have a specific tag.
     */
    private function statsForTag(Network $network, int $tagId): array
    {
        $stats = \App\Group::getGroupStatsArrayKeys();

        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        // Get groups in this network that have the specified tag
        $groups = $network->groups()
            ->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
            ->where('grouptags_groups.group_tag', $tagId)
            ->get();

        foreach ($groups as $group) {
            $singleGroupStats = $group->getGroupStats($eEmissionRatio, $uEmissionratio);

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
     *          name="tag",
     *          description="Filter by tag ID. Only groups with this tag will be returned.",
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
        $query = Group::join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where('group_network.network_id', $id)
            ->where('groups.updated_at', '>=', $start)
            ->where('groups.updated_at', '<=', $end);

        if (!$request->has('includeArchived') || $request->get('includeArchived') == 'false') {
            $query = $query->whereNull('archived_at');
        }

        // Filter by tag if specified
        if ($request->has('tag')) {
            $tagId = $request->get('tag');
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
     *          name="tag",
     *          description="Filter by tag ID. Only events from groups with this tag will be returned.",
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
        $query = Party::join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where('group_network.network_id', $id)
            ->where('event_start_utc', '>=', $start)
            ->where('event_end_utc', '<=', $end)
            ->where('events.updated_at', '>=', $updated_start)
            ->where('events.updated_at', '<=', $updated_end)
            ->where('events.approved', true)
            ->where('groups.approved', true);

        // Filter by tag if specified
        if ($request->has('tag')) {
            $tagId = $request->get('tag');
            $query = $query->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
                ->where('grouptags_groups.group_tag', $tagId);
        }

        $events = $query->select('events.*')->get();

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
     *      @OA\Response(
     *          response=404,
     *          description="Network not found",
     *      ),
     *     )
     */
    public function getNetworkTagsv2(Request $request, $id)
    {
        $network = Network::findOrFail($id);

        // Try session auth first, then API token auth
        $user = Auth::user();
        if (!$user) {
            $user = auth('api')->user();
        }

        // Only admins can see global tags; everyone else sees only network-specific tags
        $isAdmin = $user && $user->hasRole('Administrator');
        $networkOnly = $request->get('network_only', false) === 'true' || $request->get('network_only', false) === true;

        if ($networkOnly || !$isAdmin) {
            // Network tags only (exclude global tags)
            $tags = GroupTags::forNetwork($id)->get();
        } else {
            // Admin: global + network tags
            $tags = GroupTags::availableForNetwork($id)->get();
        }

        return TagCollection::make($tags);
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
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden - User is not a coordinator for this network",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Network not found",
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error - tag name already exists in this network",
     *      ),
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
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden - User is not a coordinator for this network or tag is global",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Network or tag not found",
     *      ),
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
}
