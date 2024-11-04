<?php

namespace App\Http\Controllers\API;

use App\Events\ApproveGroup;
use App\Events\EditGroup;
use App\Group;
use App\GroupTags;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Http\Resources\PartySummaryCollection;
use App\Http\Resources\TagCollection;
use App\Http\Resources\VolunteerCollection;
use App\Network;
use App\Notifications\AdminModerationGroup;
use App\Notifications\GroupConfirmed;
use App\Notifications\NewGroupWithinRadius;
use App\Party;
use App\Role;
use App\Rules\Timezone;
use App\User;
use App\UserGroups;
use Auth;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Notification;
use Illuminate\Validation\ValidationException;

class GroupController extends Controller
{
    /**
     * List changes made to groups.
     * Makes use of the audits produced by Laravel audits.
     *
     * Created specifically for use as a Zapier trigger.
     *
     * Only Administrators can access this API call.
     */
    public static function getGroupChanges(Request $request)
    {
        $authenticatedUser = Auth::user();
        if (! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $dateFrom = $request->input('date_from', null);

        $groupAudits = self::getGroupAudits($dateFrom);

        $groupChanges = [];
        foreach ($groupAudits as $groupAudit) {
            $group = Group::find($groupAudit->auditable_id);
            if (! is_null($group) && $group->changesShouldPushToZapier()) {
                $groupChanges[] = self::mapDetailsAndAuditToChange($group, $groupAudit);
            }
        }

        return response()->json($groupChanges);
    }

    public static function getGroupsByUsersNetworks(Request $request)
    {
        $authenticatedUser = Auth::user();

        $bbox = $minLat = $minLng = $maxLat = $maxLng = null;

        if ($request->has('bbox')) {
            $bbox = $request->get('bbox');
            if (preg_match('/(.*?),(.*?),(.*?),(.*)/', $bbox, $matches)) {
                $minLat = floatval($matches[1]);
                $minLng = floatval($matches[2]);
                $maxLat = floatval($matches[3]);
                $maxLng = floatval($matches[4]);
            }
        }

        $groups = [];

        foreach ($authenticatedUser->networks as $network) {
            foreach ($network->groups as $group) {
                $groups[] = $group;
            }
        }

        // New Collection Instance
        $collection = collect([]);

        foreach ($groups as $group) {
            // If we have a bounding box, check that the group is within it.
            if (! $bbox || (
                $group->latitude !== null && $group->longitude !== null &&
                $group->latitude >= $minLat && $group->latitude <= $maxLat &&
                $group->longitude >= $minLng && $group->longitude <= $maxLng
                )) {
                $groupStats = $group->getGroupStats();
                $collection->push([
                                      'id' => $group->idgroups,
                                      'name' => $group->name,
                                      'timezone' => $group->timezone,
                                      'location' => [
                                          'value' => $group->location,
                                          'country' => Fixometer::getCountryFromCountryCode($group->country_code),
                                          'country_code' => $group->country_code,
                                          'latitude' => $group->latitude,
                                          'longitude' => $group->longitude,
                                          'area' => $group->area,
                                          'postcode' => $group->postcode,
                                      ],
                                      'website' => $group->website,
                                      'facebook' => $group->facebook,
                                      'description' => $group->free_text,
                                      'image_url' => $group->groupImagePath(),
                                      'upcoming_parties' => $upcoming_parties_collection = collect([]),
                                      'past_parties' => $past_parties_collection = collect([]),
                                      'impact' => [
                                        'volunteers' => $groupStats['participants'],
                                        'hours_volunteered' => $groupStats['hours_volunteered'],
                                          'parties_thrown' => $groupStats['parties'],
                                          'waste_prevented' => round($groupStats['waste_total']),
                                          'co2_emissions_prevented' => round($groupStats['co2_total']),
                                      ],
                                      'widgets' => [
                                          'headline_stats' => url("/group/stats/{$group->idgroups}"),
                                          'co2_equivalence_visualisation' => url("/outbound/info/group/{$group->idgroups}/manufacture"),
                                      ],
                                      'created_at' => new \Carbon\Carbon($group->created_at),
                                      'updated_at' => new \Carbon\Carbon($group->max_updated_at_devices_updated_at),
                                      'network_data' => $group->network_data
                                  ]);

                foreach ($group->upcomingParties() as $event) {
                    $upcoming_parties_collection->push([
                                                           'event_id' => $event->idevents,
                                                           'event_date' => $event->event_date_local,
                                                           'start_time' => $event->start_local,
                                                           'end_time' => $event->end_local,
                                                           'timezone' => $event->timezone,
                                                           'name' => $event->venue,
                                                           'link' => $event->link,
                                                           'online' => $event->online,
                                                           'description' => $event->free_text,
                                                           'location' => [
                                                               'value' => $event->location,
                                                               'latitude' => $event->latitude,
                                                               'longitude' => $event->longitude,
                                                           ],
                                                           'created_at' => $event->created_at,
                                                           'updated_at' => $event->updated_at,
                                                       ]);
                }

                foreach ($group->pastParties() as $key => $event) {
                    $past_parties_collection->push([
                                                       'event_id' => $event->idevents,
                                                       'event_date' => $event->event_date_local,
                                                       'start_time' => $event->start_local,
                                                       'end_time' => $event->end_local,
                                                       'timezone' => $event->timezone,
                                                       'name' => $event->venue,
                                                       'link' => $event->link,
                                                       'online' => $event->online,
                                                       'description' => $event->free_text,
                                                       'location' => [
                                                           'value' => $event->location,
                                                           'latitude' => $event->latitude,
                                                           'longitude' => $event->longitude,
                                                       ],
                                                       'created_at' => $event->created_at,
                                                       'updated_at' => $event->updated_at,
                                                   ]);
                }
            }
        }

        return response()->json($collection);
    }

    /**
     * Get all of the audits related to groups from the audits table.
     */
    public static function getGroupAudits($dateFrom = null)
    {
        $query = \OwenIt\Auditing\Models\Audit::where('auditable_type', \App\Group::class);

        if (! is_null($dateFrom)) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $query->groupBy('created_at')
              ->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * Map from the group and audit information as recorded by the audits library,
     * into the format needed for Zapier.
     */
    public static function mapDetailsAndAuditToChange($group, $groupAudit)
    {
        $group->makeHidden(['updated_at', 'wordpress_post_id', 'ShareableLink', 'shareable_code']);
        $groupChange = $group->toArray();

        // Zapier makes use of this unique hash as an id for the change for deduplication.
        $auditCreatedAtAsString = $groupAudit->created_at->toDateTimeString();
        $groupChange['id'] = md5($group->idgroups.$auditCreatedAtAsString);
        $groupChange['group_id'] = $group->idgroups;
        $groupChange['change_occurred_at'] = $auditCreatedAtAsString;
        $groupChange['change_type'] = $groupAudit->event;

        return $groupChange;
    }

    public static function getGroupList()
    {
        $groups = Group::orderBy('created_at', 'desc');

        $groups = $groups->get();
        foreach ($groups as $group) {
            mb_convert_encoding($group, 'UTF-8', 'UTF-8');
        }

        return response()->json($groups);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/groups/names",
     *      operationId="getGroupListv2",
     *      tags={"Groups"},
     *      summary="Get list of group names",
     *      @OA\Parameter(
     *          name="archived",
     *          description="Include archived groups",
     *          required=false,
     *          in="path",
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
     *                description="An array of basic group info",
     *                type="array",
     *                @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer", example=1),
     *                   @OA\Property(property="name", type="string", example="Group Name"),
     *                   @OA\Property(
     *                     property="lat",
     *                     title="lat",
     *                     description="Latitude of the group.",
     *                     format="float",
     *                     example="50.8113243"
     *                  ),
     *                  @OA\Property(
     *                     property="lng",
     *                     title="lng",
     *                     description="Longitude of the group.",
     *                     format="float",
     *                     example="-1.0788839"
     *                  ),
     *                )
     *             )
     *          )
     *       ),
     *     )
     */

    public static function listNamesv2(Request $request) {
        $request->validate([
            'archived' => ['string', 'in:true,false'],
        ]);

        // We only return a small number of attributes, for speed.
        $query = Group::select('idgroups', 'name', 'latitude', 'longitude', 'archived_at');

        if (!$request->has('archived') || $request->get('archived') == 'false') {
            $query = $query->whereNull('archived_at');
        }

        $groups = $query->get();
        $ret = [];

        foreach ($groups as $group) {
            $ret[] = [
                'id' => $group->idgroups,
                'name' => $group->name,
                'lat' => $group->latitude,
                'lng' => $group->longitude,
                'archived_at' => $group->archived_at ? Carbon::parse($group->archived_at)->toIso8601String() : null
            ];
        }

        return [
            'data' => $ret
        ];
    }

    /**
     * @OA\Get(
     *      path="/api/v2/groups/summary",
     *      operationId="getGroupSummariesv2",
     *      tags={"Groups"},
     *      summary="Get list of groups with summary information",
     *      @OA\Parameter(
     *          name="archived",
     *          description="Include archived groups",
     *          required=false,
     *          in="path",
     *          @OA\Schema(
     *              type="boolean"
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
     *          name="includeCounts",
     *          description="Include the counts of hosts and restarters.  This makes the call slower.  Default false.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="boolean"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="includeCounts",
     *          description="Include impact stats.  This makes the call slower.  Default true.",
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
     *                type="array",
     *                @OA\Items(
     *                    @OA\Schema(
     *                       ref="#/components/schemas/GroupSummary"
     *                    ),
     *                 )
     *              )
     *          )
     *       ),
     *     )
     */

    public static function listSummaryv2(Request $request) {
        $request->validate([
            'archived' => ['string', 'in:true,false'],
        ]);

        $query = Group::all();

        if (!$request->has('archived') || $request->get('archived') == 'false') {
            $query = $query->whereNull('archived_at');
        }

        $groups = $query->all();

        return [
            'data' => \App\Http\Resources\GroupSummaryCollection::make($groups)
        ];
    }

    /**
     * @OA\Get(
     *      path="/api/v2/groups/tags",
     *      operationId="getGroupTagsv2",
     *      tags={"Groups"},
     *      summary="Get list of group tags",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                property="data",
     *                title="data",
     *                description="An array of group tags",
     *                type="array",
     *                @OA\Items(
     *                   ref="#/components/schemas/Tag"
     *                )
     *             )
     *          )
     *       ),
     *     )
     */
    public static function listTagsv2(Request $request) {
        return [
            'data' => TagCollection::make(GroupTags::all())
        ];
    }

    /**
     * @OA\Get(
     *      path="/api/v2/groups/{id}",
     *      operationId="getGroup",
     *      tags={"Groups"},
     *      summary="Get Group",
     *      description="Returns information about a group.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Group id",
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
     *                ref="#/components/schemas/Group"
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Group not found",
     *      ),
     *     )
     */
    public static function getGroupv2(Request $request, $idgroups) {
        $group = Group::findOrFail($idgroups);
        return \App\Http\Resources\Group::make($group);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/groups/{id}/events",
     *      operationId="getGroupv2",
     *      tags={"Groups"},
     *      summary="Get Group",
     *      description="Returns the list of events for a group.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Group id",
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
     *                    ref="#/components/schemas/EventSummary"
     *                )
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Group not found",
     *      ),
     *     )
     */

    public static function getEventsForGroupv2(Request $request, $idgroups) {
        $group = Group::findOrFail($idgroups);

        $parties = collect([]);

        // Only show events on approved groups.
        if ($group->approved) {
            // Get date filters.  We default to far past and far future so that we don't need multiple code branches.  We
            // don't need to validate the date format - if they put junk in then they'll get junk matches back.
            $start = Carbon::parse($request->get('start', '1970-01-01'))->setTimezone('UTC')->toIso8601String();
            $end = Carbon::parse($request->get('end', '3000-01-01'))->setTimezone('UTC')->toIso8601String();

            $parties = Party::undeleted()->forGroup($idgroups)
                ->where('event_start_utc', '>=', $start)
                ->where('event_end_utc', '<=', $end)
                ->get();
        }

        return PartySummaryCollection::make($parties);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/groups/{id}/volunteers",
     *      operationId="getVolunteersForGroupv2",
     *      tags={"Groups","Volunteers"},
     *      summary="Get Group Volunteers",
     *      description="Returns the list of confirmed volunters for a group.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Group id",
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
     *                description="An array of volunteers",
     *                type="array",
     *                @OA\Items(
     *                    ref="#/components/schemas/Volunteer"
     *                )
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Group not found",
     *      ),
     *     )
     */

    public static function getVolunteersForGroupv2($idgroups) {
        $group = Group::findOrFail($idgroups);
        $volunteers = $group->allConfirmedVolunteers()->get();
        return VolunteerCollection::make($volunteers);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/groups/{id}/volunteers/{iduser}",
     *      operationId="deleteVolunteerForGroupv2",
     *      tags={"Groups","Volunteers"},
     *      summary="Delete Group Volunteer",
     *      description="Removes a volunteer from a group",
     *      @OA\Parameter(
     *          name="id",
     *          description="Group id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="iduser",
     *          description="User id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Group not found",
     *      ),
     *     )
     */

    public function deleteVolunteerForGroupv2(Request $request, $id, $iduser)
    {
        $user = $this->getUser();

        $group = Group::findOrFail($id);
        $is_host_of_group = Fixometer::userHasEditGroupPermission($id, $user->id);
        $isCoordinatorForGroup = $user->isCoordinatorForGroup($group);

        if (!Fixometer::hasRole($user, 'Administrator') && !$is_host_of_group && !$isCoordinatorForGroup) {
            throw new AuthenticationException();
        }

        $userGroupAssociation = UserGroups::where('group', $id)->where('user', $iduser)->first();

        if (!is_null($userGroupAssociation)) {
            $userGroupAssociation->delete();
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/groups/{id}/volunteers/{iduser}",
     *      operationId="patchVolunteerForGroupv2",
     *      tags={"Groups","Volunteers"},
     *      summary="Modify Group Volunteer",
     *      description="Modify a volunteer's status on a group",
     *      @OA\Parameter(
     *          name="id",
     *          description="Group id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="host",
     *          description="Host",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="boolean"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Group not found",
     *      ),
     *     )
     */

    public function patchVolunteerForGroupv2(Request $request, $id, $iduser)
    {
        $user = $this->getUser();
        $host = $request->get('host', false);

        $group = Group::findOrFail($id);
        $is_host_of_group = Fixometer::userHasEditGroupPermission($id, $user->id);
        $isCoordinatorForGroup = $user->isCoordinatorForGroup($group);

        if (!Fixometer::hasRole($user, 'Administrator') && !$is_host_of_group && !$isCoordinatorForGroup) {
            throw new AuthenticationException();
        }

        $userGroupAssociation = UserGroups::where('group', $id)->where('user', $iduser)->first();

        if (!is_null($userGroupAssociation)) {
            $userGroupAssociation->role = $host ? Role::HOST : Role::RESTARTER;
            $userGroupAssociation->save();
        }
    }

    private function getUser() {
        // We want to allow this call to work if a) we are logged in as a user, or b) we have a valid API token.
        //
        // This is a slightly odd thing to do, but it is necessary to get both the PHPUnit tests and the
        // real client use of the API to work.
        $user = Auth::user();

        if (!$user) {
            $user = auth('api')->user();
        }

        if (!$user) {
            throw new AuthenticationException();
        }

        return $user;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/moderate/groups",
     *      operationId="getGroupsModeratev2",
     *      tags={"Groups"},
     *      summary="Get Groups for Moderation",
     *      description="Only available for Administrators and Network Coordinators. ",
     *      @OA\Parameter(
     *          name="api_token",
     *          description="A valid user API token",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="1234"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             description="An array of groups",
     *             type="array",
     *             @OA\Items(
     *                 ref="#/components/schemas/Group"
     *             )
     *          )
     *       ),
     *     )
     */
    public function moderateGroupsv2(Request $request) {
        $user = $this->getUser();
        $ret = \App\Http\Resources\GroupCollection::make(Group::unapprovedVisibleTo($user->id));
        return response()->json($ret);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/groups",
     *      operationId="createGroup",
     *      tags={"Groups"},
     *      summary="Create Group",
     *      description="Creates a group.",
     *      @OA\Parameter(
     *          name="api_token",
     *          description="A valid user API token",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="1234"
     *          )
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                required={"name","location","description"},
     *                @OA\Property(
     *                   property="name",
     *                   ref="#/components/schemas/Group/properties/name",
     *                ),
     *                @OA\Property(
     *                   property="location",
     *                   ref="#/components/schemas/Group/properties/location",
     *                ),
     *                @OA\Property(
     *                   property="phone",
     *                   ref="#/components/schemas/Group/properties/phone"
     *                ),
     *                @OA\Property(
     *                   property="website",
     *                   ref="#/components/schemas/Group/properties/website"
     *                ),
     *                @OA\Property(
     *                   property="email",
     *                   ref="#/components/schemas/Group/properties/email"
     *                ),
     *                @OA\Property(
     *                   property="description",
     *                   ref="#/components/schemas/Group/properties/description",
     *                ),
     *                @OA\Property(
     *                   property="timezone",
     *                   ref="#/components/schemas/Group/properties/timezone"
     *                ),
     *                @OA\Property(
     *                   description="Image for the group",
     *                   property="image",
     *                   type="string", format="binary"
     *                ),
     *                @OA\Property(
     *                   description="Network-defined JSON data",
     *                   property="network_data",
     *                   @OA\Schema()
     *                ),
     *             )
     *         )
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Successful operation",
     *        @OA\JsonContent(
     *            @OA\Property(
     *              property="data",
     *              title="data",
     *              ref="#/components/schemas/Group"
     *            )
     *        ),
     *     )
     *  )
     */
    public function createGroupv2(Request $request) {
        $user = $this->getUser();
        $user->convertToHost();

        list($name, $area, $postcode, $location, $phone, $website, $description, $timezone, $latitude, $longitude, $country, $network_data, $email) = $this->validateGroupParams(
            $request,
            true
        );

        $data = [
            'name' => $name,
            'website' => $website,
            'location' => $location,
            'area' => $area,
            'postcode' => $postcode,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country_code' => $country,
            'free_text' => $description,
            'shareable_code' => Fixometer::generateUniqueShareableCode(\App\Group::class, 'shareable_code'),
            'timezone' => $timezone,
            'phone' => $phone,
            'network_data' => $network_data,
            'email' => $email,
        ];

        $group = Group::create($data);
        $idGroup = $group->idgroups;

        // Add the group to the same network as this logged in user.  Note that the CheckForRepairNetwork middleware
        // which checks the host name is only used for the web interface, not the API.
        //
        // The networks can be amended in the update call.
        if ($user->repair_network) {
            $network = Network::find($user->repair_network);

            if ($network) {
                $network->addGroup($group);
            }
        }

        //Associate currently logged-in user as a host.
        UserGroups::create([
                               'user' => $user->id,
                               'group' => $idGroup,
                               'status' => 1,
                               'role' => Role::HOST,
                           ]);

        if (isset($_FILES) && !empty($_FILES)) {
            $file = new \FixometerFile();
            $file->upload('image', 'image', $idGroup, env('TBL_GROUPS'), false, true, true);
        }

        // Notify relevant admins.
        $notify_admins = Fixometer::usersWhoHavePreference('admin-moderate-group');
        Notification::send($notify_admins, new AdminModerationGroup([
                                                                        'group_name' => $name,
                                                                        'group_url' => url('/group/edit/'.$idGroup),
                                                                    ]));

        return response()->json([
            'id' => $idGroup,
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/groups/{id}",
     *      operationId="editGroup",
     *      tags={"Groups"},
     *      summary="Edit Group",
     *      description="Edit a group.",
     *      @OA\Parameter(
     *          name="api_token",
     *          description="A valid user API token",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="1234"
     *          )
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                required={"name","location","description"},
     *                @OA\Property(
     *                   property="name",
     *                   ref="#/components/schemas/Group/properties/name",
     *                ),
     *                @OA\Property(
     *                   property="location",
     *                   ref="#/components/schemas/Group/properties/location",
     *                ),
     *                @OA\Property(
     *                   property="phone",
     *                   ref="#/components/schemas/Group/properties/phone"
     *                ),
     *                @OA\Property(
     *                   property="website",
     *                   ref="#/components/schemas/Group/properties/website"
     *                ),
     *                @OA\Property(
     *                   property="email",
     *                   ref="#/components/schemas/Group/properties/email"
     *                ),
     *                @OA\Property(
     *                   property="description",
     *                   ref="#/components/schemas/Group/properties/description",
     *                ),
     *                @OA\Property(
     *                   property="timezone",
     *                   ref="#/components/schemas/Group/properties/timezone"
     *                ),
     *                @OA\Property(
     *                   description="Image for the group",
     *                   property="image",
     *                   type="string", format="binary"
     *                 ),
     *                @OA\Property(
     *                   description="Network-defined JSON data",
     *                   property="network_data",
     *                   @OA\Schema()
     *                ),
     *             )
     *         )
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Successful operation",
     *        @OA\JsonContent(
     *            @OA\Property(
     *              property="data",
     *              title="data",
     *              ref="#/components/schemas/Group"
     *            )
     *        ),
     *     )
     *  )
     */
    public function updateGroupv2(Request $request, $idGroup) {
        $user = $this->getUser();

        list($name, $area, $postcode, $location, $phone, $website, $description, $timezone, $latitude, $longitude, $country, $network_data, $email) = $this->validateGroupParams(
            $request,
            false
        );

        $group = Group::findOrFail($idGroup);
        $is_host_of_group = Fixometer::userHasEditGroupPermission($idGroup, $user->id);
        $isCoordinatorForGroup = $user->isCoordinatorForGroup($group);

        if (! Fixometer::hasRole($user, 'Administrator') && ! $is_host_of_group && ! $isCoordinatorForGroup) {
            abort(403);
        }

        $old_zone = $group->timezone;

        $data = [
            'name' => $name,
            'website' => $website,
            'location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country_code' => $country,
            'free_text' => $description,
            'timezone' => $timezone,
            'phone' => $phone,
            'network_data' => $network_data,
            'email' => $email,
        ];

        if ($user->hasRole('Administrator') || $user->hasRole('NetworkCoordinator')) {
            // Not got permission to update these.
            $data['area'] = $request->area;
            $data['postcode'] = $request->postcode;
        }

        if (isset($_FILES) && !empty($_FILES)) {
            // Update the group image.
            $file = new \FixometerFile();
            $group_avatar = $file->upload('image', 'image', $idGroup, env('TBL_GROUPS'), false, true, true);
            $group_avatar = env('UPLOADS_URL').'mid_'.$group_avatar;
        } else {
            // Get the existing image to pass in data to the notification in case it needs it.
            $existing_image = Fixometer::hasImage($idGroup, 'groups', true);
            if (! empty($existing_image)) {
                $group_avatar = env('UPLOADS_URL').'mid_'.$existing_image[0]->path;
            } else {
                $group_avatar = 'null';
            }
        }

        $data['group_avatar'] = $group_avatar;

        $group->update($data);

        if (Fixometer::hasRole($user, 'Administrator')) {
            // We can update the networks.  The parameter is an array of ids.
            $networks = $request->networks;

            if ($networks) {
                $networks = json_decode($networks);
                $group->networks()->sync($networks);
            }

            // We can update the tags.  The parameter is an array of ids.
            // TODO The old code restricts updating tags to admins.  But I wonder if it should include
            // networks coordinators too.
            $tags = $request->tags;

            if ($tags) {
                $tags = json_decode($tags);
                $group->group_tags()->sync($tags);
            }
        }

        if ($timezone != $old_zone) {
            // The timezone of the group has changed.  Update the zone of any future events.  This happens
            // sometimes when a group is created and events are created before the group is approved (and therefore
            // before the admin has a chance to set the zone on the group.
            foreach ($group->upcomingParties() as $party) {
                $party->update([
                                   'timezone' => $timezone
                               ]);
            }
        }

        $moderate = $request->input('moderate');

        if ($moderate == 'approve' && (Fixometer::hasRole($user, 'Administrator') || $isCoordinatorForGroup)) {
            // We've been asked to approve this group.
            event(new ApproveGroup($group, $data));

            // Notify the creator, as long as it's not the current user.
            $creator = User::find(UserGroups::where('group', $idGroup)->first()->user);

            if ($creator->id != $user->id) {
                Notification::send($creator, new GroupConfirmed($group));
            }

            // Notify nearest users.
            if (! is_null($latitude) && ! is_null($longitude)) {
                $restarters_nearby = User::nearbyRestarters($latitude, $longitude, 25)
                    ->orderBy('name', 'ASC')
                    ->get();

                Notification::send($restarters_nearby, new NewGroupWithinRadius([
                                                                                    'group_name' => $group->name,
                                                                                    'group_url' => url('/group/view/'.$idGroup),
                                                                                ]));
            }
        } elseif (!empty($group->wordpress_post_id)) {
            event(new EditGroup($group, $data));
        }

        return response()->json([
                                    'id' => $idGroup,
                                ]);
    }

    private function validateGroupParams(Request $request, $create): array {
        // We don't validate max lengths of other strings, to avoid duplicating the length information both here
        // and in the migrations.  If we wanted to do that we should extract the length dynamically from the
        // schema, which is possible but not trivial.
        if ($create) {
            $request->validate([
                                   'name' => ['required', 'unique:groups', 'max:255'],
                                   'location' => ['required', 'max:255'],
                                   'description' => ['required'],
                               ]);
        } else {
            $request->validate([
                                   'name' => ['max:255'],
                                   'location' => ['max:255'],
                               ]);
        }

        $name = $request->input('name');
        $area = $request->input('area');
        $postcode = $request->input('postcode', '');
        $location = $request->input('location');
        $phone = $request->input('phone');
        $website = $request->input('website');
        $description = $request->input('description');
        $timezone = $request->input('timezone');
        $network_data = $request->input('network_data');
        $email = $request->input('email');

        $latitude = null;
        $longitude = null;
        $country_code = null;

        if ($timezone && !in_array($timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC))) {
            throw ValidationException::withMessages(['location ' => __('partials.validate_timezone')]);
        }

        if (!empty($location)) {
            $geocoder = new \App\Helpers\Geocoder();
            $geocoded = $geocoder->geocode($location);

            if (empty($geocoded))
            {
                throw ValidationException::withMessages(['location ' => __('groups.geocode_failed')]);
            }

            $latitude = $geocoded['latitude'];
            $longitude = $geocoded['longitude'];

            // Note that the country returned by the geocoder is already in English, which is what we need for the
            // value in the database.
            $country_code = $geocoded['country_code'];
        }

        return array(
            $name,
            $area,
            $postcode,
            $location,
            $phone,
            $website,
            $description,
            $timezone,
            $latitude,
            $longitude,
            $country_code,
            $network_data,
            $email,
        );
    }
}
