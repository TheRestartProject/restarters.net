<?php

namespace App\Http\Controllers\API;

use App\Events\EditEvent;
use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Invite;
use App\Network;
use App\Notifications\AdminModerationEvent;
use App\Role;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Notification;
use App\Notifications\JoinGroup;
use App\Party;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function getEventsByUsersNetworks(Request $request, $date_from = null, $date_to = null, $timezone = 'UTC')
    {
        $authenticatedUser = Auth::user();

        $groups = [];
        foreach ($authenticatedUser->networks as $network) {
            foreach ($network->groups as $group) {
                $groups[] = $group;
            }
        }
        $parties = Party::join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->join('networks', 'networks.id', '=', 'group_network.network_id')
            ->join('user_network', 'networks.id', '=', 'user_network.network_id')
            ->join('users', 'users.id', '=', 'user_network.user_id')
            ->orderBy('event_start_utc', 'ASC');

        if (!empty($date_from) && !empty($date_to)) {
            $start = Carbon\Carbon::parse($date_from, $timezone);
            $start->setTimezone('UTC');
            $end = Carbon\Carbon::parse($date_to, $timezone);
            $end->setTimezone('UTC');
            $parties = $parties->where('events.event_start_utc', '>=', $start->toIso8601String())
                ->where('events.event_end_utc', '<=', $end->toIso8601String());
        }

        $parties = $parties->where([
            ['users.api_token', $authenticatedUser->api_token],
        ])
            ->select('events.*')
            ->get();

        // If no parties are found, through 404 error
        if (empty($parties)) {
            return abort(404, 'No Events found.');
        }

        $groups_array = collect([]);
        foreach ($groups as $group) {
            $groupStats = $group->getGroupStats();
            $groups_array->push([
                'id' => $group->idgroups,
                'name' => $group->name,
                'area' => $group->area,
                'timezone' => $group->timezone,
                'postcode' => $group->postcode,
                'description' => $group->free_text,
                'image_url' => $group->groupImagePath(),
                'volunteers' => $group->volunteers,
                'participants' => $groupStats['participants'],
                'hours_volunteered' => $groupStats['hours_volunteered'],
                'parties_thrown' => $groupStats['parties'],
                'waste_prevented' => $groupStats['waste_total'],
                'co2_emissions_prevented' => $groupStats['co2_total'],
            ]);
        }

        $collection = collect([]);

        // Send these to getEventStats() to speed things up a bit.
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        foreach ($parties as $key => $party) {
            $group = $groups_array->filter(function ($group) use ($party) {
                return $group['id'] == $party->group;
            })->first();

            $eventStats = $party->getEventStats($eEmissionRatio, $uEmissionratio);
            // Push Party to Collection
            $collection->push([
                'id' => $party->idevents,
                'group' => [$group],
                'area' => $group['area'],
                'postcode' => $group['postcode'],
                'timezone' => $party->timezone,
                'event_date' => $party->event_date_local,
                'start_time' => $party->start_local,
                'end_time' => $party->end_local,
                'name' => $party->venue,
                'link' => $party->link,
                'online' => $party->online,
                'location' => [
                    'value' => $party->location,
                    'latitude' => $party->latitude,
                    'longitude' => $party->longitude,
                    'area' => $group['area'],
                    'postcode' => $group['postcode'],
                ],
                'description' => $party->free_text,
                'user' => $party_user = collect(),
                'impact' => [
                    'participants' => $party->pax,
                    'volunteers' => $eventStats['volunteers'],
                    'waste_prevented' => $eventStats['waste_powered'],
                    'co2_emissions_prevented' => $eventStats['co2_powered'],
                    'devices_fixed' => $eventStats['fixed_devices'],
                    'devices_repairable' => $eventStats['repairable_devices'],
                    'devices_dead' => $eventStats['dead_devices'],
                ],
                'widgets' => [
                    'headline_stats' => url("/party/stats/{$party->idevents}/wide"),
                    'co2_equivalence_visualisation' => url(
                        "/outbound/info/party/{$party->idevents}/manufacture"
                    ),
                ],
                'hours_volunteered' => $party->hoursVolunteered(),
                'created_at' => new \Carbon\Carbon($party->created_at),
             'updated_at' => (new \Carbon\Carbon($party->max_updated_at_devices_updated_at)),
            ]);

            if (!empty($party->owner)) {
                $party_user->put('id', $party->owner->id);
                $party_user->put('name', $party->owner->name);
            }
        }

        return $collection;
    }

    public function addVolunteer(Request $request, $idevents)
    {
        $party = Party::findOrFail($idevents);

        if (!Fixometer::userHasEditPartyPermission($idevents)) {
            abort(403);
        }

        $volunteer_email_address = $request->input('volunteer_email_address');

        // Retrieve name if one exists.  If no name exists and user is null as well then this volunteer is anonymous.
        if ($request->has('full_name')) {
            $full_name = $request->input('full_name');
        } else {
            $full_name = null;
        }

        $eventRole = Role::RESTARTER;

        if ($request->has('user') && $request->input('user') !== 'not-registered') {
            // User is null, this volunteer is either anonymous or no user exists.
            $user = $request->input('user');

            if ($user) {
                $u = User::find($user);

                // A host of a group who is added to an event becomes a host of the event.
                $eventRole = $u && $u->role == Role::RESTARTER ? Role::RESTARTER : Role::HOST;
            }
        } else {
            $user = null;
        }

        // Check if user was invited but not RSVPed.
        $invitedUserQuery = EventsUsers::where('event', $idevents)
            ->where('user', $user)
            ->where('status', '<>', 1)
            ->whereNotNull('status')
            ->where('role', $eventRole);
        $userWasInvited = $invitedUserQuery->count() == 1;

        if ($userWasInvited) {
            $invitedUser = $invitedUserQuery->first();
            $invitedUser->status = 1;
            $invitedUser->save();
        } else {
            // Let's add the volunteer.
            EventsUsers::create([
                'event' => $idevents,
                'user' => $user,
                'status' => 1,
                'role' => $eventRole,
                'full_name' => $full_name,
            ]);
        }

        if (!is_null($volunteer_email_address)) {
            // Send email.
            $from = User::find(Auth::user()->id);

            $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
            $url = url('/user/register/'.$hash);

            $invite = Invite::create([
                'record_id' => $party->theGroup->idgroups,
                'email' => $volunteer_email_address,
                'hash' => $hash,
                'type' => 'group',
            ]);

            Notification::send(
                $invite,
                new JoinGroup([
                    'name' => $from->name,
                    'group' => $party->theGroup->name,
                    'url' => $url,
                    'message' => null,
                ])
            );
        }

        return response()->json([
            'success' => 'success'
        ]);
    }


    public function listVolunteers(Request $request, $idevents)
    {
        $party = Party::findOrFail($idevents);

        // Get the user that the API has been authenticated as.
        $user = auth('api')->user();

        // Emails are sensitive.
        $showEmails = $user && !Fixometer::userHasEditPartyPermission($idevents, $user->id);
        $volunteers = $party->expandVolunteers($party->allConfirmedVolunteers()->get(), $showEmails);

        return response()->json([
            'success' => 'success',
            'volunteers' => $volunteers
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/events/{id}",
     *      operationId="getEvent",
     *      tags={"Events"},
     *      summary="Get Event",
     *      description="Returns information about an event.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Event id",
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
     *                ref="#/components/schemas/Event"
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Event not found",
     *      ),
     *     )
     */

    public function getEventv2(Request $request, $idevents)
    {
        $party = Party::findOrFail($idevents);

        return \App\Http\Resources\Party::make($party);
    }

    private function getUser()
    {
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
     *      path="/api/v2/moderate/events",
     *      operationId="getEventsModeratev2",
     *      tags={"Events"},
     *      summary="Get Events for Moderation",
     *      description="Only available for Administrators and Network Coordinators.",
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
     *             type="array",
     *             description="An array of groups",
     *             @OA\Items(
     *                 ref="#/components/schemas/Event"
     *             )
     *          )
     *       ),
     *     )
     */
    public function moderateEventsv2(Request $request)
    {
        // Get the user that the API has been authenticated as.
        $user = $this->getUser();
        $ret = [];
        $networks = [];

        if ($user->hasRole('Administrator')) {
            $networks = Network::all();
        } else {
            if ($user->hasRole('NetworkCoordinator')) {
                $networks = $user->networks;
            }
        }

        foreach ($networks as $network) {
            foreach ($network->eventsRequiringModeration() as $event) {
                $ret[] = \App\Http\Resources\Party::make($event);
            }
        }

        // Sort $ret by ->sortBy('event_start_utc') ascending order of time
        usort($ret, function ($a, $b) {
            return strtotime($a->resource->event_start_utc) - strtotime($b->resource->event_start_utc);
        });

        return response()->json($ret);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/events",
     *      operationId="createEvent",
     *      tags={"Events"},
     *      summary="Create Event",
     *      description="Creates an event.",
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
     *                required={"start","end","title","description","location","lat","lng"},
     *                @OA\Property(
     *                   property="groupid",
     *                   title="id",
     *                   description="Unique identifier of the group to which the event belongs",
     *                   format="int64",
     *                   example=1
     *                ),
     *                @OA\Property(
     *                   property="start",
     *                   ref="#/components/schemas/Event/properties/start",
     *                ),
     *                @OA\Property(
     *                   property="end",
     *                   ref="#/components/schemas/Event/properties/start",
     *                ),
     *                @OA\Property(
     *                   property="timezone",
     *                   ref="#/components/schemas/Event/properties/timezone",
     *                ),
     *                @OA\Property(
     *                   property="title",
     *                   ref="#/components/schemas/Event/properties/title",
     *                ),
     *                @OA\Property(
     *                   property="description",
     *                   ref="#/components/schemas/Event/properties/description",
     *                ),
     *                @OA\Property(
     *                   property="location",
     *                   ref="#/components/schemas/Event/properties/location",
     *                ),
     *                @OA\Property(
     *                   property="online",
     *                   ref="#/components/schemas/Event/properties/online",
     *                ),
     *                @OA\Property(
     *                   property="link",
     *                   ref="#/components/schemas/Event/properties/link",
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
     *              ref="#/components/schemas/Event"
     *            )
     *        ),
     *     )
     *  )
     */
    public function createEventv2(Request $request)
    {
        $user = $this->getUser();

        list($groupid, $start, $end, $title, $description, $location, $timezone, $latitude, $longitude, $online, $link) = $this->validateEventParams(
            $request,
            true
        );

        $group = Group::findOrFail($groupid);

        // Check whether the event should be auto-approved, if all of the networks it belongs to
        // allow it.
        $autoapprove = $group->auto_approve;

        if (!Fixometer::userCanCreateEvents($user)) {
            // TODO: This doesn't check that they are a host of this particular group.
            abort(403);
        }

        // Convert the timezone to UTC, because the timezone is not itself stored in the DB.
        $event_start_utc = Carbon::parse($start)->setTimezone('UTC')->toIso8601String();
        $event_end_utc = Carbon::parse($end)->setTimezone('UTC')->toIso8601String();
        $hours = Carbon::parse($event_start_utc)->diffInHours(Carbon::parse($event_end_utc));

        // timezone needs to be the first attribute set, because it is used in mutators for later attributes.
        $data = [
            'timezone' => $timezone,
            'event_start_utc' => $event_start_utc,
            'event_end_utc' => $event_end_utc,
            'free_text' => $description,
            'link' => $link,
            'venue' => $title,
            'location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'group' => $groupid,
            'hours' => $hours,
            'user_id' => $user->id,
            'created_at' => date('Y-m-d H:i:s'),
            'shareable_code' => Fixometer::generateUniqueShareableCode(\App\Party::class, 'shareable_code'),
            'online' => $online
        ];

        $party = Party::create($data);
        $idParty = $party->idevents;

        EventsUsers::create([
            'event' => $idParty,
            'user' => $user->id,
            'status' => 1,
            'role' => Role::HOST,
        ]);

        // Notify relevant users.
        $usersToNotify = Fixometer::usersWhoHavePreference('admin-moderate-event');
        foreach ($party->associatedNetworkCoordinators() as $coordinator) {
            $usersToNotify->push($coordinator);
        }

        Notification::send(
            $usersToNotify->unique(),
            new AdminModerationEvent([
                'event_venue' => $title,
                'event_url' => url('/party/edit/'.$idParty),
            ])
        );

        if (isset($_FILES) && !empty($_FILES) && is_array($_FILES['file']['name'])) {
            $File = new FixometerFile;
            $files = Fixometer::rearrange($_FILES['file']);
            foreach ($files as $upload) {
                $File->upload($upload, 'image', $idParty, env('TBL_EVENTS'));
            }
        }

        if ($autoapprove) {
            Log::info("Auto-approve event $idParty");
            Party::find($idParty)->approve();
        }

        if (isset($_FILES) && !empty($_FILES)) {
            $file = new \FixometerFile();
            $file->upload('image', 'image', $idParty, env('TBL_EVENTS'), false, true, true);
        }

        return response()->json([
            'id' => $idParty,
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/events/{id}",
     *      operationId="editEvent",
     *      tags={"Events"},
     *      summary="Edit Event",
     *      description="Edits an event.",
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
     *                required={"start","end","title","description","location","lat","lng"},
     *                @OA\Property(
     *                   property="start",
     *                   ref="#/components/schemas/Event/properties/start",
     *                ),
     *                @OA\Property(
     *                   property="end",
     *                   ref="#/components/schemas/Event/properties/start",
     *                ),
     *                @OA\Property(
     *                   property="timezone",
     *                   ref="#/components/schemas/Event/properties/timezone",
     *                ),
     *                @OA\Property(
     *                   property="title",
     *                   ref="#/components/schemas/Event/properties/title",
     *                ),
     *                @OA\Property(
     *                   property="description",
     *                   ref="#/components/schemas/Event/properties/description",
     *                ),
     *                @OA\Property(
     *                   property="location",
     *                   ref="#/components/schemas/Event/properties/location",
     *                ),
     *                @OA\Property(
     *                   property="online",
     *                   ref="#/components/schemas/Event/properties/online",
     *                ),
     *                @OA\Property(
     *                   property="link",
     *                   ref="#/components/schemas/Event/properties/link",
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
     *              ref="#/components/schemas/Event"
     *            )
     *        ),
     *     )
     *  )
     */
    public function updateEventv2(Request $request, $idEvents)
    {
        $user = $this->getUser();

        list($groupid, $start, $end, $title, $description, $location, $timezone, $latitude, $longitude, $online, $link) = $this->validateEventParams(
            $request,
            false
        );

        // Convert the timezone to UTC, because the timezone is not itself stored in the DB.
        $event_start_utc = Carbon::parse($start)->setTimezone('UTC')->toIso8601String();
        $event_end_utc = Carbon::parse($end)->setTimezone('UTC')->toIso8601String();
        $hours = Carbon::parse($event_start_utc)->diffInHours(Carbon::parse($event_end_utc));

        // We don't let the user change the group that an event is on.
        $update = [
            'event_start_utc' => $event_start_utc,
            'event_end_utc' => $event_end_utc,
            'hours' => $hours,
            'free_text' => $description,
            'online' => $online,
            'venue' => $title,
            'link' => $link,
            'location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => $timezone
        ];

        $party = Party::findOrFail($idEvents);
        $party->update($update);

        // REVIEW: In the old code we wouldn't generate EditEvent in the approval case.  But I think that was a bug.
        if ($request->has('moderate') && $request->input('moderate') == 'approve') {
            $party->approve();
        }

        event(new EditEvent($party, $update));

        return response()->json([
            'id' => $idEvents,
        ]);
    }

    private function validateEventParams(Request $request, $create): array
    {
        // We don't validate max lengths of other strings, to avoid duplicating the length information both here
        // and in the migrations.  If we wanted to do that we should extract the length dynamically from the
        // schema, which is possible but not trivial.
        if ($create) {
            $request->validate([
                'groupid' => 'required|integer',
                                   'start' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
                                   'end' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
                'title' => ['required', 'max:255'],
                'description' => ['required'],
                'location' => [
                    function ($attribute, $value, $fail) use ($request) {
                        if (!$request->filled('online') && !$value) {
                            $fail(__('events.validate_location'));
                        }
                    },
                ],
                'online' => ['boolean'],
            ]);
        } else {
            $request->validate([
                                   'start' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
                                   'end' => ['required', 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z'],
                'title' => ['required', 'max:255'],
                'description' => ['required'],
                'location' => [
                    function ($attribute, $value, $fail) use ($request) {
                        if (!$request->filled('online') && !$value) {
                            $fail(__('events.validate_location'));
                        }
                    },
                ],
                'online' => ['boolean'],
            ]);
        }

        $groupid = $request->input('groupid');
        $start = $request->input('start');
        $end = $request->input('end');
        $title = $request->input('title');
        $description = $request->input('description');
        $location = $request->input('location');
        $timezone = $request->input('timezone');
        $online = $request->input('online', false);
        $link = $request->input('link', null);

        $latitude = null;
        $longitude = null;

        if ($timezone && !in_array($timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC))) {
            throw ValidationException::withMessages(['location ' => __('partials.validate_timezone')]);
        }

        if (!empty($location)) {
            $geocoder = new \App\Helpers\Geocoder();
            $geocoded = $geocoder->geocode($location);

            if (empty($geocoded)) {
                throw ValidationException::withMessages(['location ' => __('groups.geocode_failed')]);
            }

            $latitude = $geocoded['latitude'];
            $longitude = $geocoded['longitude'];
        }

        return array(
            $groupid,
            $start,
            $end,
            $title,
            $description,
            $location,
            $timezone,
            $latitude,
            $longitude,
            $online,
            $link,
        );
    }
}
