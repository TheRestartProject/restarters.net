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
use App\Network;
use App\Notifications\AdminModerationGroup;
use App\Notifications\GroupConfirmed;
use App\Notifications\NewGroupWithinRadius;
use App\Party;
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
                                          'country' => $group->country,
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

    public static function getEventsForGroup(Request $request, Group $group)
    {
        // Used by old JS client.
        $group = $group->load('parties');

        $events = $group->parties->sortByDesc('event_start_utc');

        if ($request->has('format') && $request->input('format') == 'location') {
            $events = $events->map(function ($event) {
                return (object) [
                    'id' => $event->idevents,
                    'location' => $event->FriendlyLocation,
                ];
            });
        }

        return response()->json([
            'events' => $events->values()->toJson(),
        ]);
    }

    // TODO Add to OpenAPI.
    public static function listNamesv2(Request $request) {
        // We only return the group id and name, for speed.
        $groups = Group::select('idgroups', 'name')->get();
        $ret = [];

        foreach ($groups as $group) {
            $ret[] = [
                'id' => $group->idgroups,
                'name' => $group->name,
            ];
        }

        return [
            'data' => $ret
        ];
    }

    // TODO Add to OpenAPI.
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
     *      operationId="getGroup",
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
     *                description="An array of groups",
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

    // TODO Add to OpenAPI.
    public function listVolunteers(Request $request, $idgroups) {
        $group = Group::findOrFail($idgroups);

        // Get the user that the API has been authenticated as.
        $user = auth('api')->user();

        if (!$user || !Fixometer::userHasEditGroupPermission($idgroups, $user->id)) {
            // We require host permissions to view the list of volunteers.  At the moment this call is only used when
            // adding volunteers, and this check means we don't have to worry about exposing sensitive data.
            abort(403);
        }

        $volunteers = $group->allConfirmedVolunteers()->get();

        $ret = [];

        foreach ($volunteers as $v) {
            $volunteer = $v->volunteer;
            $ret[] = [
                'id' => $volunteer->id,
                'name' => $volunteer->name,
                'email'=> $volunteer->email
            ];
        }

        return response()->json($ret);
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

    // TODO Add to OpenAPI.
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
     *                 )
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
        // TODO Should we restrict group creation to non-Restarters?  The code in GroupController does.
        $user = $this->getUser();

        list($name, $area, $postcode, $location, $phone, $website, $description, $timezone, $latitude, $longitude, $country) = $this->validateGroupParams(
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
            'country' => $country,
            'free_text' => $description,
            'shareable_code' => Fixometer::generateUniqueShareableCode(\App\Group::class, 'shareable_code'),
            'timezone' => $timezone,
            'phone' => $phone,
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
                               'role' => 3,
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

    // TODO Add to OpenAPI
    public function updateGroupv2(Request $request, $idGroup) {
        $user = $this->getUser();

        list($name, $area, $postcode, $location, $phone, $website, $description, $timezone, $latitude, $longitude, $country) = $this->validateGroupParams(
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
            'country' => $country,
            'free_text' => $description,
            'timezone' => $timezone,
            'phone' => $phone,
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

        $latitude = null;
        $longitude = null;
        $country = null;

        if ($timezone && !in_array($timezone, \DateTimeZone::listIdentifiers())) {
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
            $country = $geocoded['country'];
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
            $country
        );
    }
}
