<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
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
    /**
     * @OA\Get(
     *      path="/api/events/network/{date_from}/{date_to}",
     *      operationId="getEventsByUsersNetworksLegacy",
     *      tags={"Legacy", "Events"},
     *      summary="Events across the authenticated user's networks (legacy, used by Repair Together)",
     *      description="Legacy endpoint outside the /api/v2 surface, used by the Repair Together integration. Returns approved events for every group in every network the caller coordinates, each with per-event impact stats and widget URLs. Authenticated via the ?api_token= query string.",
     *      security={{"ApiKeyAuth":{}}},
     *      @OA\Parameter(name="date_from", in="path", required=false, description="Optional ISO-8601 lower bound on event start.", @OA\Schema(type="string", format="date-time")),
     *      @OA\Parameter(name="date_to", in="path", required=false, description="Optional ISO-8601 upper bound on event end.", @OA\Schema(type="string", format="date-time")),
     *      @OA\Response(response=200, description="A list of events with per-event impact stats and widget URLs.", @OA\JsonContent(type="array", @OA\Items(type="object"))),
     *      @OA\Response(response=404, description="No events found for the caller's networks.")
     * )
     */
    public function getEventsByUsersNetworks(Request $request, $date_from = null, $date_to = null, $timezone = 'UTC')
    {
        $authenticatedUser = Auth::user();
        set_time_limit(240);

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
            $start = Carbon::parse($date_from, $timezone);
            $start->setTimezone('UTC');
            $end = Carbon::parse($date_to, $timezone);
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

    /**
     * @OA\Put(
     *      path="/api/events/{id}/volunteers",
     *      operationId="addVolunteerLegacy",
     *      tags={"Legacy", "Events"},
     *      summary="Add a volunteer to an event (legacy)",
     *      description="Legacy endpoint outside the /api/v2 surface. Adds a registered or unregistered volunteer to an event; requires edit-party permission. When volunteer_email_address is supplied for a non-existent user, an invitation email is sent.",
     *      security={{"ApiKeyAuth":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, description="Event id.", @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          @OA\MediaType(mediaType="application/json", @OA\Schema(
     *              @OA\Property(property="user", description="Existing user id, or 'not-registered'.", type="string", nullable=true),
     *              @OA\Property(property="volunteer_email_address", type="string", format="email", nullable=true),
     *              @OA\Property(property="full_name", type="string", nullable=true)
     *          ))
     *      ),
     *      @OA\Response(response=200, description="Volunteer added.", @OA\JsonContent(@OA\Property(property="success", type="string", example="success"))),
     *      @OA\Response(response=403, description="Caller lacks edit-party permission."),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function addVolunteer(Request $request, $idevents): JsonResponse
    {
        $request->validate([
            'volunteer_email_address' => ['nullable', 'email'],
        ]);

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

                // A host of the group who is added to an event becomes a host of the event.
                $eventRole = $u && Fixometer::userIsHostOfGroup($party->group, $user) ? Role::HOST : Role::RESTARTER;
            }
        } else {
            $user = null;
        }

        // Check if user was invited but not RSVPed.
        $invitedUserQuery = EventsUsers::where('event', $idevents)
            ->where('user', $user)
            ->where('status', '<>', '1')
            ->whereNotNull('status')
            ->where('role', $eventRole);
        $userWasInvited = $invitedUserQuery->count() == 1;

        if ($userWasInvited) {
            $invitedUser = $invitedUserQuery->first();
            $invitedUser->status = '1';
            $invitedUser->save();
        } else {
            // Let's add the volunteer.
            EventsUsers::create([
                'event' => $idevents,
                'user' => $user,
                'status' => '1',
                'role' => $eventRole,
                'full_name' => $full_name,
            ]);
        }

        if (!is_null($volunteer_email_address)) {
            // Send email.
            $from = User::find(Auth::user()->id);

            $hash = Fixometer::generateHash();
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


    /**
     * @OA\Get(
     *      path="/api/events/{id}/volunteers",
     *      operationId="listVolunteersLegacy",
     *      tags={"Legacy", "Events"},
     *      summary="List an event's confirmed volunteers (legacy)",
     *      description="Legacy endpoint outside the /api/v2 surface (the v2 replacement is GET /api/v2/events/{id}/attendees). Returns the event's confirmed volunteers.",
     *      security={{"ApiKeyAuth":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, description="Event id.", @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="The event's confirmed volunteers.", @OA\JsonContent(type="array", @OA\Items(type="object"))),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function listVolunteers(Request $request, $idevents): JsonResponse
    {
        $party = Party::findOrFail($idevents);

        // Get the user that the API has been authenticated as (whichever guard
        // the auth:sanctum,api middleware resolved).
        $user = $request->user();

        // Only show emails to users who have edit permission on this event.
        $showEmails = $user && Fixometer::userHasEditPartyPermission($idevents, $user->id);
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
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     )
     */

    public function getEventv2(Request $request, $idevents)
    {
        $party = Party::findOrFail($idevents);

        // Events on unapproved (unmoderated) groups are only visible to the
        // relevant hosts/coordinators/admins - restores the legacy
        // PartyController::view() gate the API-only cutover dropped. Events on
        // approved groups stay fully public.
        if (! Fixometer::userHasViewPartyPermission($idevents, $this->optionalUser()?->id, $party)) {
            abort(404);
        }

        return \App\Http\Resources\Party::make($party);
    }


    /**
     * Resolve the acting user across all guards (web session, SPA sanctum
     * bearer, api_token) without throwing - for optional-auth endpoints that
     * are public but behave differently for a recognised user. Plain
     * $request->user() only checks the default 'web' guard, so it misses the
     * client's bearer token on routes that carry no auth middleware.
     */
    private function optionalUser()
    {
        return Auth::user() ?? auth('sanctum')->user() ?? auth('api')->user();
    }

    /**
     * @OA\Get(
     *      path="/api/v2/events/{id}/attendees",
     *      operationId="getEventAttendeesv2",
     *      tags={"Events","Volunteers"},
     *      summary="Get event attendees",
     *      description="Confirmed attendees (participants/volunteers/hosts) and pending invitees for an event. Replaces the Blade-only attended/invited/hosts computation in PartyController::view() and extends v1 GET /api/events/{id}/volunteers (confirmed-only). Unlike the Blade view, lists are NOT truncated.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Event id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="confirmed", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer", description="events_users.idevents_users"),
     *                  @OA\Property(property="user", type="integer", nullable=true, description="Null for a manually-added, unregistered volunteer"),
     *                  @OA\Property(property="fullName", type="string"),
     *                  @OA\Property(property="role", type="integer", description="Role: HOST=3, RESTARTER=4, GUEST=5 (guest = plain attendee/participant)"),
     *                  @OA\Property(property="confirmed", type="boolean"),
     *                  @OA\Property(property="profilePath", type="string"),
     *                  @OA\Property(property="volunteer", type="object", nullable=true, description="Present only when user is set",
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string", nullable=true, description="Only when the caller has edit-party permission"),
     *                      @OA\Property(property="user_skills", type="array", @OA\Items(type="object"))
     *                  )
     *              )),
     *              @OA\Property(property="invited", type="array", description="Same shape as confirmed, with confirmed:false; the raw status hash is not surfaced", @OA\Items(
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="user", type="integer", nullable=true),
     *                  @OA\Property(property="fullName", type="string"),
     *                  @OA\Property(property="role", type="integer"),
     *                  @OA\Property(property="confirmed", type="boolean"),
     *                  @OA\Property(property="profilePath", type="string"),
     *                  @OA\Property(property="volunteer", type="object", nullable=true,
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string", nullable=true),
     *                      @OA\Property(property="user_skills", type="array", @OA\Items(type="object"))
     *                  )
     *              ))
     *          ))
     *      ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function attendeesv2(Request $request, $idevents): JsonResponse
    {
        $party = Party::findOrFail($idevents);

        // Optional auth: showEmails mirrors listVolunteers' gate, everything else is public.
        // Resolve across guards so the SPA's bearer token is recognised on this
        // auth-middleware-free route (default 'web' guard alone would miss it).
        $user = $this->optionalUser();

        // Events on unapproved groups are hidden from the public (legacy
        // PartyController::view() gate) - approved-group events stay public.
        if (! Fixometer::userHasViewPartyPermission($idevents, $user?->id, $party)) {
            abort(404);
        }

        $showEmails = $user && Fixometer::userHasEditPartyPermission($idevents, $user->id);

        $confirmed = Party::expandVolunteers($party->allConfirmedVolunteers()->get(), $showEmails);
        $invited = Party::expandVolunteers($party->allInvited()->get(), $showEmails);

        return response()->json([
            'data' => [
                'confirmed' => array_map(fn ($row) => self::shapeAttendee($row, true), $confirmed),
                'invited' => array_map(fn ($row) => self::shapeAttendee($row, false), $invited),
            ],
        ]);
    }

    /**
     * Shape an EventsUsers row (as expanded by Party::expandVolunteers()) into the
     * confirmed/invited attendee JSON documented in api-contracts-phase-c.md#C1b.
     */
    private static function shapeAttendee($row, bool $confirmed): array
    {
        $shaped = [
            'id' => $row->idevents_users,
            'user' => $row->user,
            'fullName' => $row['fullName'],
            'role' => (int) $row->role,
            'confirmed' => $confirmed,
            'profilePath' => $row['profilePath'],
        ];

        if ($row->user) {
            $shaped['volunteer'] = $row['volunteer'];
        }

        return $shaped;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/events/{id}/devices",
     *      operationId="getEventDevicesv2",
     *      tags={"Events","Devices"},
     *      summary="Get the devices logged at an event",
     *      description="Replaces the Blade view() controller's inline device-resolve loop - not exposed as a callable endpoint today (Blade passes it as an initial prop). The Nuxt client needs it as a real call since there's no server render.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Event id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Device")))
     *      ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function devicesv2(Request $request, $idevents): JsonResponse
    {
        $party = Party::findOrFail($idevents);

        // Events on unapproved groups are hidden from the public (legacy
        // PartyController::view() gate) - approved-group events stay public.
        if (! Fixometer::userHasViewPartyPermission($idevents, $this->optionalUser()?->id, $party)) {
            abort(404);
        }

        return response()->json([
            'data' => \App\Http\Resources\Device::collection($party->devices()->get()),
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/moderate/events",
     *      operationId="getEventsModeratev2",
     *      tags={"Events"},
     *      summary="Get Events for Moderation",
     *      description="Events requiring moderation across the networks the caller can moderate: Administrators see every network, Network Coordinators see their own networks. Returns an empty list for an authenticated user who is neither.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *             type="array",
     *             description="An array of events",
     *             @OA\Items(
     *                 ref="#/components/schemas/Event"
     *             )
     *          )
     *       ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *     )
     */
    /**
     * @OA\Get(
     *      path="/api/v2/events/{id}/audits",
     *      operationId="getEventAuditsv2",
     *      tags={"Events"},
     *      summary="Audit trail for an event",
     *      description="Backs the edit page's Event log tab. Administrator only, matching the legacy edit view's `$audits && hasRole(Administrator)` gate. Strings are rendered server-side from the event-audits lang files so the placeholder substitution stays in one place; each entry's `heading` and `changes` are HTML.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Audit entries, newest first",
     *          @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="event", type="string", example="updated"),
     *              @OA\Property(property="heading", type="string"),
     *              @OA\Property(property="changes", type="array", @OA\Items(type="string"))
     *          )))
     *      ),
     *      @OA\Response(response=403, description="Not an administrator"),
     *      @OA\Response(response=404, description="No such event")
     * )
     */
    public function auditsv2($id): JsonResponse
    {
        if ($resp = $this->requireAdministrator()) {
            return $resp;
        }

        $event = Party::find($id);

        if (! $event) {
            return response()->json(['error' => 'No such event'], 404);
        }

        // Rendered server-side, as the legacy view does
        // (partials/log-accordion.blade.php's
        // `@lang($type.'.'.$audit->event.'.metadata', $audit->getMetadata())`).
        // Resolving them client-side would mean reimplementing the placeholder
        // substitution and keeping two copies of the key layout in step.
        $audits = $event->audits()->with('user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $audits->map(function ($audit) {
                $changes = [];

                foreach ($audit->getModified() as $attribute => $modified) {
                    $key = 'event-audits.'.$audit->event.'.modified.'.$attribute;
                    $line = __($key, $modified);

                    // An attribute with no lang entry returns the key itself -
                    // skip it rather than showing a raw key to the user, which
                    // is exactly the failure the /party moderation header had.
                    if ($line !== $key) {
                        $changes[] = $line;
                    }
                }

                $headingKey = 'event-audits.'.$audit->event.'.metadata';
                $heading = __($headingKey, $audit->getMetadata());

                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'heading' => $heading === $headingKey ? null : $heading,
                    'changes' => $changes,
                ];
            })->values()->all(),
        ]);
    }

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

        $events = [];

        foreach ($networks as $network) {
            $events = array_merge($events, $network->eventsRequiringModeration());
        }

        // Dedupe by id: an event whose group belongs to more than one network
        // is returned once per network by the loop above, so it appeared
        // twice in the moderation queue. Observed against the parity fixtures,
        // where the queue rendered the same pending event twice while develop
        // showed it once.
        $events = collect($events)->unique('idevents')->values()->all();

        usort($events, function ($a, $b) {
            return strtotime($a->event_start_utc) - strtotime($b->event_start_utc);
        });

        // One query for every event's invited count, rather than one per event
        // (see the Party resource's `invited` field).
        // Eloquent's collection, not the base one - loadCount only exists on
        // the former, and collect() returns the latter.
        $collection = new \Illuminate\Database\Eloquent\Collection($events);
        $collection->loadCount('allInvited');

        $ret = \App\Http\Resources\Party::collection($collection);

        return response()->json($ret);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/events",
     *      operationId="createEvent",
     *      tags={"Events"},
     *      summary="Create Event",
     *      description="Creates an event. `location` is required unless `online` is true.",
     *      security={{"apiToken":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                required={"groupid","start","end","title","description"},
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
     *              property="id",
     *              type="integer",
     *              description="Unique identifier of the newly-created event",
     *              example=1
     *            )
     *        ),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     *  )
     */
    public function createEventv2(Request $request): JsonResponse
    {
        $user = $this->getUser();

        list($groupid, $start, $end, $title, $description, $location, $timezone, $latitude, $longitude, $online, $link, $network_data) = $this->validateEventParams(
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
            'online' => $online,
            'network_data' => $network_data,
        ];

        $party = Party::create($data);
        $idParty = $party->idevents;

        EventsUsers::create([
            'event' => $idParty,
            'user' => $user->id,
            'status' => '1',
            'role' => Role::HOST,
        ]);

        // Notify relevant users.
        $usersToNotify = Fixometer::usersWhoHavePreference('admin-moderate-event');

        foreach ($party->associatedNetworkCoordinators() as $coordinator) {
            // If the user is an admin, we allow them to turn off the notification.
            // Network coordinators must always receive them, for fear of events languishing unapproved.
            // So only add network coordinators who aren't admins, and rely on usersWhoHavePreference to have
            // added relevant admins.
            if (!$coordinator->hasRole('Administrator')) {
                $usersToNotify->push($coordinator);
            }
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
     *      description="Edits an event.  The event of a group cannot be changed after creation. `location` is required unless `online` is true.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", description="Event id", required=true, in="path", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                required={"start","end","title","description"},
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
     *                @OA\Property(
     *                   description="Network-defined JSON data",
     *                   property="network_data",
     *                   @OA\Schema()
     *                ),
     *                @OA\Property(
     *                   property="participants",
     *                   description="New value for the participants headcount counter (replaces POST /party/update-quantity). Host/NC/admin gated, same as the rest of this endpoint.",
     *                   type="integer",
     *                   minimum=0,
     *                ),
     *                @OA\Property(
     *                   property="volunteers",
     *                   description="New value for the volunteers headcount counter (replaces POST /party/update-volunteerquantity). Host/NC/admin gated, same as the rest of this endpoint.",
     *                   type="integer",
     *                   minimum=0,
     *                ),
     *             )
     *         )
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Successful operation",
     *        @OA\JsonContent(
     *            @OA\Property(
     *              property="id",
     *              type="string",
     *              description="The event's id",
     *              example=1
     *            )
     *        ),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     *  )
     */
    public function updateEventv2(Request $request, $idEvents): JsonResponse
    {
        $user = $this->getUser();

        list($groupid, $start, $end, $title, $description, $location, $timezone, $latitude, $longitude, $online, $link, $network_data) = $this->validateEventParams(
            $request,
            false
        );

        if (!Fixometer::userHasEditPartyPermission($idEvents, $user->id)) {
            abort(403);
        }

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
            'timezone' => $timezone,
            'network_data' => $network_data,
        ];

        // Headcount counters (the +/- control next to "Participants"/"Volunteers"): folded in here
        // instead of the legacy POST /party/update-quantity + update-volunteerquantity routes
        // (judgment call #3 in api-contracts-phase-c.md). Those two routes had their own inline
        // role check ((Host||NetworkCoordinator)&&userHasEditPartyPermission || Administrator) which
        // is a SUBSET of the userHasEditPartyPermission check already enforced above (that helper
        // already returns true for Administrator/NetworkCoordinator-for-network/host-of-group), so
        // reusing it here only tightens, never loosens, who can bump the counters.
        $request->validate([
            'participants' => 'nullable|integer|min:0',
            'volunteers' => 'nullable|integer|min:0',
        ]);

        if ($request->filled('participants')) {
            $update['pax'] = $request->input('participants');
        }

        if ($request->filled('volunteers')) {
            $update['volunteers'] = $request->input('volunteers');
        }

        $party = Party::findOrFail($idEvents);
        $party->update($update);

        if ($request->has('moderate') && $request->input('moderate') == 'approve' && Fixometer::userCanApproveEvent($idEvents, $user->id)) {
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
        $network_data = $request->input('network_data');

        $latitude = null;
        $longitude = null;

        if ($timezone && !in_array($timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC))) {
            throw ValidationException::withMessages(['location ' => __('partials.validate_timezone')]);
        }

        if (!empty($location)) {
            $geocoder = app(\App\Helpers\Geocoder::class);
            $geocoded = $geocoder->geocode($location);

            if (empty($geocoded)) {
                throw ValidationException::withMessages(['location ' => __('events.geocode_failed')]);
            }

            $latitude = $geocoded['latitude'];
            $longitude = $geocoded['longitude'];
        }

        return [
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
            $network_data
        ];
    }
}
