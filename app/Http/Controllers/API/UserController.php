<?php

namespace App\Http\Controllers\API;

use App\Events\PasswordChanged;
use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\Geocoder;
use App\Helpers\LcaStats;
use App\Helpers\Tus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserAdmin;
use App\Party;
use App\Permissions;
use App\Preferences;
use App\Role;
use App\User;
use App\UserGroups;
use App\UsersSkills;
use Auth;
use Cache;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
    /**
     * List recent changes made to users.
     * Makes use of the audits produced by Laravel audits.
     *
     * Created specifically for use as a Zapier trigger.
     *
     * Only Administrators can access this API call.
     */
    public static function changes(Request $request)
    {
        $authenticatedUser = Auth::user();
        if (! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $dateFrom = $request->input('date_from', null);

        $userAudits = self::getUserAudits($dateFrom);

        $userChanges = [];
        foreach ($userAudits as $userAudit) {
            $user = User::withTrashed()->find($userAudit->auditable_id);
            if (! is_null($user) && $user->changesShouldPushToZapier()) {
                $userChanges[] = self::mapUserAndAuditToUserChange($user, $userAudit);
            }
        }

        return response()->json($userChanges);
    }

    protected static function getUserAudits($dateFrom = null)
    {
        $query = \OwenIt\Auditing\Models\Audit::where('auditable_type', \App\User::class);

        if (! is_null($dateFrom)) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $query->groupBy('event', 'created_at', 'auditable_id')
              ->orderBy('created_at', 'desc');

        return $query->get();
    }

    protected static function mapUserAndAuditToUserChange($user, $audit)
    {
        // Hide fields not relevant for Zapier.
        $user->makeHidden([
            'updated_at',
            'deleted_at',
            'api_token',
            'drip_subscriber_id',
            'recovery',
            'recovery_expires',
            'calendar_hash',
            'number_of_logins',
            'consent_past_data',
            'consent_gdpr',
            'consent_future_data',
            'access_group_tag_id',
            'mediawiki',
            'wiki_sync_status',
            'latitude',
            'longitude',
        ]);

        $userChange = $user->toArray();
        $userChange['talk_profile_url'] = $user->getTalkProfileUrl();

        $auditCreatedAtAsString = $audit->created_at->toDateTimeString();

        $userChange['user_id'] = $user->id;
        $userChange['id'] = md5($user->id.$auditCreatedAtAsString);
        $userChange['role'] = $user->role()->first()->role;

        $userChange['change_occurred_at'] = $auditCreatedAtAsString;
        $userChange['change_type'] = $audit->event;

        return $userChange;
    }

    /**
     * Get notification counts for a user.
     */
    public function notifications(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $restartersNotifications = $user->unreadNotifications()->count();
        $discourseNotifications = 0;

        if (config('restarters.features.discourse_integration')) {
            if (Cache::has('talk_notification_' . $user->username)) {
                $discourseNotifications = Cache::get('talk_notification_' . $user->username);
            } else {
                try {
                    if (config('restarters.features.discourse_integration')) {
                        $client = app('discourse-client');
                        $response = $client->request('GET', '/notifications.json?username=' . $user->username);
                        $talk_notifications = json_decode($response->getBody()->getContents(), true);

                        if (!empty($talk_notifications) && array_key_exists('notifications', $talk_notifications)) {
                            foreach ($talk_notifications['notifications'] as $notification) {
                                if ($notification['read'] !== true) {
                                    $discourseNotifications++;
                                }
                            }

                            Cache::put('talk_notification_' . $user->username, $discourseNotifications, 60);
                        }
                    }
                } catch (\Exception $e) {
                    // Discourse unavailable - fail gracefully with 0 notifications
                    \Log::warning('Discourse notifications unavailable: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
                                    'success' => 'success',
                                    'restarters' => $restartersNotifications,
                                    'discourse' => $discourseNotifications
                                ], 200);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/me/preferences",
     *      operationId="getMyEmailPreferencesv2",
     *      tags={"Users"},
     *      summary="Get the authenticated user's email preferences",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="invites", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyEmailPreferencesv2(): JsonResponse
    {
        return response()->json([
            'data' => $this->emailPreferencesData(Auth::user()),
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/{id}/preferences",
     *      operationId="getUserPreferencesv2",
     *      tags={"Users"},
     *      summary="Administrator (or self): get a user's email preferences",
     *      description="Id-scoped mirror of GET /users/me/preferences, so an admin edit form can be pre-filled with the target's current value before PATCHing it. Authorised via UserPolicy::update (self-or-Administrator).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="invites", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function getUserPreferencesv2(int $id): JsonResponse
    {
        $target = User::findOrFail($id);
        $this->authorize('update', $target);

        return response()->json([
            'data' => $this->emailPreferencesData($target),
        ]);
    }

    /**
     * Shared body of getMyEmailPreferencesv2/getUserPreferencesv2.
     */
    private function emailPreferencesData(User $user): array
    {
        return [
            'invites' => (bool) $user->invites,
        ];
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/me/preferences",
     *      operationId="updateMyEmailPreferencesv2",
     *      tags={"Users"},
     *      summary="Update the authenticated user's email preferences",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="invites", type="boolean")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="invites", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateMyEmailPreferencesv2(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->applyEmailPreferencesUpdate($request, Auth::user()),
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/{id}/preferences",
     *      operationId="updateUserPreferencesv2",
     *      tags={"Users"},
     *      summary="Administrator (or self): update a user's email preferences",
     *      description="Id-scoped mirror of PATCH /users/me/preferences. Authorised via UserPolicy::update (self-or-Administrator).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="invites", type="boolean")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="invites", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function updateUserPreferencesv2(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);
        $this->authorize('update', $target);

        return response()->json([
            'data' => $this->applyEmailPreferencesUpdate($request, $target),
        ]);
    }

    /**
     * Shared body of updateMyEmailPreferencesv2/updateUserPreferencesv2.
     */
    private function applyEmailPreferencesUpdate(Request $request, User $user): array
    {
        $validated = $request->validate([
            'invites' => 'required|boolean',
        ]);

        $user->invites = $validated['invites'] ? 1 : 0;
        $user->save();

        return $this->emailPreferencesData($user);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/users/me/onboarding-complete",
     *      operationId="onboardingCompletev2",
     *      tags={"Users"},
     *      summary="Mark the post-registration onboarding modal as seen",
     *      description="Port of the legacy GET /user/onboarding-complete (UserController::getOnboardingComplete): bumps number_of_logins to at least 2 so GET /api/v2/session's flags.onboarding is false from the next call onwards.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Onboarding dismissed",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="onboarding", type="boolean")
     *          ))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Data consent required")
     * )
     */
    public function onboardingCompletev2(): JsonResponse
    {
        $user = Auth::user();

        if ($user->number_of_logins < 2) {
            $user->number_of_logins += 1;
            $user->save();
        }

        return response()->json(['data' => ['onboarding' => false]]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/me/groups",
     *      operationId="getMyGroupsv2",
     *      tags={"Users"},
     *      summary="All of the authenticated user's group memberships",
     *      description="Unlike GET /api/v2/dashboard's your_groups (capped at 5), this returns every group the user has a users_groups pivot row for.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="role", type="integer"),
     *              @OA\Property(property="archived", type="boolean"),
     *              @OA\Property(property="image_url", type="string", nullable=true)
     *          )))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyGroupsv2(): JsonResponse
    {
        $user = Auth::user();

        $groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups', 'groups.name', 'users_groups.role', 'groups.archived_at')
            ->select(['groups.idgroups', 'groups.name', 'users_groups.role', 'groups.archived_at'])
            ->with('groupImage.image')
            ->get();

        return response()->json([
            'data' => $groups->map(fn ($group) => [
                'id' => $group->idgroups,
                'name' => $group->name,
                'role' => (int) $group->role,
                'archived' => ! is_null($group->archived_at),
                'image_url' => $group->realImageUrl(),
            ])->values()->all(),
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/me/events",
     *      operationId="getMyEventsv2",
     *      tags={"Users","Events"},
     *      summary="Events relevant to the authenticated user",
     *      description="Replacement basis for the dead /party (mine) route (PartyController::index()'s no-group_id branch, which has no API endpoint today - only a server-rendered prop). Returns the union of: events the user hosts/attends/belongs to the group of, nearby upcoming events if the user has a location, and other approved upcoming events - each tagged nearby/all as the Blade view does.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="title", type="string", nullable=true),
     *              @OA\Property(property="start", type="string", format="date-time"),
     *              @OA\Property(property="end", type="string", format="date-time"),
     *              @OA\Property(property="timezone", type="string", nullable=true),
     *              @OA\Property(property="online", type="boolean"),
     *              @OA\Property(property="location", type="string", nullable=true),
     *              @OA\Property(property="approved", type="boolean"),
     *              @OA\Property(property="attending", type="boolean"),
     *              @OA\Property(property="nearby", type="boolean", description="True for a nearby-upcoming event not already in the user's own list"),
     *              @OA\Property(property="all", type="boolean", description="True for an event included only because it's nearby or generally upcoming, not because the user hosts/attends/belongs to its group"),
     *              @OA\Property(property="group", type="object", nullable=true,
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="country", type="string", description="The host group's free-form country name (Fixometer::getCountryFromCountryCode of its country_code), empty string if not set.")
     *              ),
     *              @OA\Property(property="stats", type="object", description="Party::getEventStats() - the same shape as the stats block on GET /api/v2/events/{id}. participants/volunteers/invited/hours_volunteered are always populated; the device/waste/co2 counters are only non-zero once the event has started or finished.",
     *                  @OA\Property(property="co2_powered", type="number"),
     *                  @OA\Property(property="co2_unpowered", type="number"),
     *                  @OA\Property(property="co2_total", type="number"),
     *                  @OA\Property(property="waste_powered", type="number"),
     *                  @OA\Property(property="waste_unpowered", type="number"),
     *                  @OA\Property(property="waste_total", type="number"),
     *                  @OA\Property(property="fixed_devices", type="number"),
     *                  @OA\Property(property="fixed_powered", type="number"),
     *                  @OA\Property(property="fixed_unpowered", type="number"),
     *                  @OA\Property(property="repairable_devices", type="number"),
     *                  @OA\Property(property="dead_devices", type="number"),
     *                  @OA\Property(property="unknown_repair_status", type="number"),
     *                  @OA\Property(property="devices_powered", type="number"),
     *                  @OA\Property(property="devices_unpowered", type="number"),
     *                  @OA\Property(property="no_weight_powered", type="number"),
     *                  @OA\Property(property="no_weight_unpowered", type="number"),
     *                  @OA\Property(property="participants", type="number"),
     *                  @OA\Property(property="volunteers", type="number"),
     *                  @OA\Property(property="hours_volunteered", type="number"),
     *                  @OA\Property(property="invited", type="number")
     *              )
     *          )))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyEventsv2(Request $request): JsonResponse
    {
        $user = $request->user();

        // Explicit $userids everywhere below - these scopes fall back to Auth::user() (the
        // default 'web' guard) when passed null, which is not necessarily who $request->user()
        // resolved via the sanctum/api guards.
        $attending = EventsUsers::where('user', $user->id)->where('status', '1')->pluck('event')->toArray();

        // Computed once and passed down to every getEventStats() call below (mirrors
        // Group::bulkGroupStats()/EventController::getEventsByUsersNetworks() "to speed things
        // up a bit") rather than re-reading the same env vars per event.
        $eEmissionRatio = LcaStats::getEmissionRatioPowered();
        $uEmissionratio = LcaStats::getEmissionRatioUnpowered();

        $events = [];
        $seenIds = [];

        // allDevices is eager-loaded because this list mixes past/in-progress/future events -
        // getEventStats() touches it for any event that has started, so without this each such
        // event would trigger its own device query (N+1). withCount('allInvited') avoids the
        // equivalent N+1 for the invited tally (see Party::getEventStats()'s all_invited_count
        // comment).
        foreach (
            Party::forUser([$user->id])
                ->with(['theGroup.groupImage.image', 'allDevices'])
                ->withCount('allInvited')
                ->reorder()->orderBy('event_start_utc', 'DESC')->get() as $event
        ) {
            $events[] = self::shapeMyEvent($event, $attending, $eEmissionRatio, $uEmissionratio);
            $seenIds[] = $event->idevents;
        }

        if (! is_null($user->latitude) && ! is_null($user->longitude)) {
            // Strictly upcoming (event_start_utc >= now), so allDevices is never touched by
            // getEventStats() here - no need to eager-load it, only the invited count.
            $nearbyEvents = Party::upcomingEventsInUserArea($user)
                ->with(['theGroup.groupImage.image'])
                ->withCount('allInvited')
                ->whereNotIn('idevents', $seenIds)
                ->get();

            foreach ($nearbyEvents as $event) {
                if (Fixometer::userHasViewPartyPermission($event->idevents, $user->id)) {
                    $row = self::shapeMyEvent($event, $attending, $eEmissionRatio, $uEmissionratio);
                    $row['nearby'] = true;
                    $row['all'] = true;
                    $events[] = $row;
                    $seenIds[] = $event->idevents;
                }
            }
        }

        // Strictly future too (event_start_utc > now) - same reasoning, no allDevices needed.
        $otherUpcoming = Party::with(['theGroup.networks', 'theGroup.groupImage.image'])->future()
            ->withCount('allInvited')
            ->whereNotIn('idevents', $seenIds)
            ->get();

        foreach ($otherUpcoming as $event) {
            if (Fixometer::userHasViewPartyPermission($event->idevents, $user->id, $event)) {
                $row = self::shapeMyEvent($event, $attending, $eEmissionRatio, $uEmissionratio);
                $row['all'] = true;
                $events[] = $row;
            }
        }

        return response()->json(['data' => $events]);
    }

    private static function shapeMyEvent(Party $event, array $attending, $eEmissionRatio = null, $uEmissionratio = null): array
    {
        $group = $event->theGroup;

        return [
            'id' => $event->idevents,
            'title' => $event->venue ?? $event->location,
            'start' => $event->event_start_utc,
            'end' => $event->event_end_utc,
            'timezone' => $event->timezone,
            'online' => (bool) $event->online,
            'location' => $event->location,
            'approved' => (bool) $event->approved,
            'attending' => in_array($event->idevents, $attending),
            'nearby' => false,
            'all' => false,
            'group' => $group ? [
                'id' => $group->idgroups,
                'name' => $group->name,
                'country' => Fixometer::getCountryFromCountryCode($group->country_code),
            ] : null,
            // Same shape as the "stats" block on GET /api/v2/events/{id} (App\Http\Resources\Party) -
            // party/view/[id].vue already reads event.stats.{participants,volunteers,waste_total,
            // co2_total,fixed_devices,repairable_devices,dead_devices,...} unconditionally, gating
            // what it shows on finished/upcoming client-side. Do the same here rather than shipping
            // two different per-event shapes.
            'stats' => $event->getEventStats($eEmissionRatio, $uEmissionratio),
        ];
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/me/calendars",
     *      operationId="getMyCalendarsv2",
     *      tags={"Users"},
     *      summary="Get the authenticated user's calendar subscription URLs",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="user_url", type="string"),
     *                  @OA\Property(property="groups", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="url", type="string")
     *                  )),
     *                  @OA\Property(property="is_admin", type="boolean"),
     *                  @OA\Property(property="admin_all_events_url", type="string", nullable=true),
     *                  @OA\Property(property="group_areas", type="array", @OA\Items(type="string"))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyCalendarsv2(): JsonResponse
    {
        $user = Auth::user();

        $groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->join('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->select('groups.idgroups', 'groups.name')
            ->groupBy('groups.idgroups', 'groups.name')
            ->orderBy('groups.idgroups', 'ASC')
            ->get();

        $groupRows = $groups->map(function ($g) {
            return [
                'id' => (int) $g->idgroups,
                'name' => $g->name,
                'url' => url('/calendar/group/' . $g->idgroups),
            ];
        })->all();

        $isAdmin = Fixometer::hasRole($user, 'Administrator');
        $calendarHash = config('restarters.calendar_hash');
        $adminAllEventsUrl = $isAdmin && $calendarHash
            ? url('/calendar/all-events/' . $calendarHash . '/')
            : null;

        $groupAreas = Group::whereNotNull('area')
            ->groupBy('area')
            ->pluck('area')
            ->toArray();

        return response()->json([
            'data' => [
                'user_url' => url('/calendar/user/' . $user->calendar_hash),
                'groups' => $groupRows,
                'is_admin' => $isAdmin,
                'admin_all_events_url' => $adminAllEventsUrl,
                'group_areas' => $groupAreas,
            ],
        ]);
    }

    private function repairDirRoleNames(): array
    {
        return [
            Role::REPAIR_DIRECTORY_NONE => 'profile.repair_dir_none',
            Role::REPAIR_DIRECTORY_EDITOR => 'profile.repair_dir_editor',
            Role::REPAIR_DIRECTORY_REGIONAL_ADMIN => 'profile.repair_dir_regional_admin',
            Role::REPAIR_DIRECTORY_SUPERADMIN => 'profile.repair_dir_superadmin',
        ];
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/{id}/repair-directory-options",
     *      operationId="getRepairDirOptionsv2",
     *      tags={"Users"},
     *      summary="List Repair Directory role options available for the target user",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="current", type="integer"),
     *                  @OA\Property(property="options", type="array", @OA\Items(
     *                      @OA\Property(property="value", type="integer"),
     *                      @OA\Property(property="key", type="string"),
     *                      @OA\Property(property="selected", type="boolean"),
     *                      @OA\Property(property="disabled", type="boolean")
     *                  ))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="User not found")
     * )
     */
    public function getRepairDirOptionsv2(int $id): JsonResponse
    {
        $perp = Auth::user();
        $victim = User::find($id);
        if (!$victim) {
            throw new NotFoundHttpException();
        }

        $options = [];
        foreach ($this->repairDirRoleNames() as $value => $key) {
            $options[] = [
                'value' => $value,
                'key' => $key,
                'selected' => $victim->repairdir_role() === $value,
                'disabled' => !$perp->can('changeRepairDirRole', [$victim, $value]),
            ];
        }

        return response()->json([
            'data' => [
                'current' => $victim->repairdir_role(),
                'options' => $options,
            ],
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/{id}/repair-directory-role",
     *      operationId="updateRepairDirRolev2",
     *      tags={"Users"},
     *      summary="Update a user's Repair Directory role (policy-gated)",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(required=true,
     *          @OA\JsonContent(@OA\Property(property="role", type="integer"))
     *      ),
     *      @OA\Response(response=200, description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="role", type="integer")
     *          ))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="User not found"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateRepairDirRolev2(Request $request, int $id): JsonResponse
    {
        $perp = Auth::user();
        $victim = User::find($id);
        if (!$victim) {
            throw new NotFoundHttpException();
        }

        $validated = $request->validate([
            'role' => 'required|integer|in:' . implode(',', array_keys($this->repairDirRoleNames())),
        ]);

        if (!$perp->can('changeRepairDirRole', [$victim, $validated['role']])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $victim->repairdir_role = $validated['role'];
        $victim->save();

        return response()->json([
            'data' => [
                'role' => $victim->repairdir_role(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/me/language",
     *      operationId="getMyLanguagev2",
     *      tags={"Users"},
     *      summary="Get the authenticated user's preferred language",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="language", type="string", nullable=true),
     *                  @OA\Property(property="supported", type="array", @OA\Items(
     *                      @OA\Property(property="code", type="string"),
     *                      @OA\Property(property="native", type="string")
     *                  ))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyLanguagev2(): JsonResponse
    {
        $supported = [];
        foreach (\LaravelLocalization::getSupportedLocales() as $code => $props) {
            $supported[] = [
                'code' => $code,
                'native' => $props['native'] ?? $code,
            ];
        }

        return response()->json([
            'data' => [
                'language' => Auth::user()->language,
                'supported' => $supported,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/me/notifications",
     *      operationId="getMyNotifications",
     *      tags={"Users"},
     *      summary="List the authenticated user's in-app notifications",
     *      description="Paginated list of the user's Restarters (in-app) notifications, replacing the old /profile/notifications page.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Notifications"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     * )
     */
    public function getMyNotificationsv2(Request $request): JsonResponse
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'data' => collect($notifications->items())->map(function ($n) {
                $data = is_array($n->data) ? $n->data : (array) $n->data;

                return [
                    'id' => $n->id,
                    'type' => class_basename($n->type),
                    'title' => $data['title'] ?? null,
                    'name' => $data['name'] ?? null,
                    'url' => $data['url'] ?? null,
                    'read' => $n->read_at !== null,
                    'created_at' => optional($n->created_at)->toIso8601String(),
                ];
            })->all(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'unread' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/users/me/notifications/read",
     *      operationId="markMyNotificationsRead",
     *      tags={"Users"},
     *      summary="Mark the user's notifications as read",
     *      description="Marks a single notification (by id) or all of them as read.",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(required=false, @OA\JsonContent(
     *          @OA\Property(property="id", type="string", description="Notification id; omit to mark all as read"),
     *      )),
     *      @OA\Response(response=200, description="Marked read"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     * )
     */
    public function markMyNotificationsReadv2(Request $request): JsonResponse
    {
        $user = Auth::user();
        $id = $request->input('id');

        if ($id) {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['data' => ['unread' => $user->unreadNotifications()->count()]]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/me/language",
     *      operationId="updateMyLanguagev2",
     *      tags={"Users"},
     *      summary="Update the authenticated user's preferred language",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          @OA\Property(property="language", type="string")
     *      )),
     *      @OA\Response(response=200, description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", type="object",
     *              @OA\Property(property="language", type="string")
     *          ))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden (e.g. data consent outstanding)"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateMyLanguagev2(Request $request): JsonResponse
    {
        $supportedCodes = array_keys(\LaravelLocalization::getSupportedLocales());
        $validated = $request->validate([
            'language' => 'required|string|in:' . implode(',', $supportedCodes),
        ]);

        $user = Auth::user();
        $user->language = $validated['language'];
        $user->save();

        session()->put('locale', $validated['language']);
        \LaravelLocalization::setLocale($validated['language']);
        \App::setLocale($validated['language']);
        event(new \App\Events\UserLanguageUpdated($user));

        return response()->json([
            'data' => [
                'language' => $user->language,
            ],
        ]);
    }

    private function profileCountryOptions(): array
    {
        $options = [];
        foreach (Fixometer::getAllCountries() as $code => $name) {
            $options[] = [
                'code' => $code,
                'name' => $name,
            ];
        }

        return $options;
    }

    private function profileAgeOptions(): array
    {
        // Includes a leading '' entry (matching the legacy blade select), so the
        // dropdown can show no selection until the user picks a year of birth.
        // Cast to strings for a consistent JSON type (option values are strings
        // in the HTML select either way).
        return array_map('strval', array_values(Fixometer::allAges()));
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/me/profile",
     *      operationId="getMyProfilev2",
     *      tags={"Users"},
     *      summary="Get the authenticated user's profile info",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="email", type="string"),
     *                  @OA\Property(property="country_code", type="string", nullable=true),
     *                  @OA\Property(property="location", type="string", nullable=true),
     *                  @OA\Property(property="age", type="string", nullable=true),
     *                  @OA\Property(property="gender", type="string", nullable=true),
     *                  @OA\Property(property="biography", type="string", nullable=true),
     *                  @OA\Property(property="countries", type="array", @OA\Items(
     *                      @OA\Property(property="code", type="string"),
     *                      @OA\Property(property="name", type="string")
     *                  )),
     *                  @OA\Property(property="ages", type="array", @OA\Items(type="string"))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyProfilev2(): JsonResponse
    {
        return response()->json([
            'data' => $this->profileData(Auth::user()),
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/{id}/profile",
     *      operationId="getUserProfilev2",
     *      tags={"Users"},
     *      summary="Administrator (or self): get a user's profile info",
     *      description="Id-scoped mirror of GET /users/me/profile, so an admin edit form can be pre-filled with the target's current values before PATCHing them. Authorised via UserPolicy::update (self-or-Administrator).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="email", type="string"),
     *                  @OA\Property(property="country_code", type="string", nullable=true),
     *                  @OA\Property(property="location", type="string", nullable=true),
     *                  @OA\Property(property="age", type="string", nullable=true),
     *                  @OA\Property(property="gender", type="string", nullable=true),
     *                  @OA\Property(property="biography", type="string", nullable=true),
     *                  @OA\Property(property="countries", type="array", @OA\Items(
     *                      @OA\Property(property="code", type="string"),
     *                      @OA\Property(property="name", type="string")
     *                  )),
     *                  @OA\Property(property="ages", type="array", @OA\Items(type="string"))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function getUserProfilev2(int $id): JsonResponse
    {
        $target = User::findOrFail($id);
        $this->authorize('update', $target);

        return response()->json([
            'data' => $this->profileData($target),
        ]);
    }

    /**
     * Shared body of getMyProfilev2/getUserProfilev2. Deliberately NOT shared with
     * applyProfileUpdate()'s return value - the PATCH response (see updateMyProfilev2's
     * @OA doc) omits the countries/ages option lists that only a GET-for-editing needs.
     */
    private function profileData(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'country_code' => $user->country_code,
            'location' => $user->location,
            'age' => $user->age,
            'gender' => $user->gender,
            'biography' => $user->biography,
            'countries' => $this->profileCountryOptions(),
            'ages' => $this->profileAgeOptions(),
        ];
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/me/profile",
     *      operationId="updateMyProfilev2",
     *      tags={"Users"},
     *      summary="Update the authenticated user's profile info",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name", "email", "age", "country"},
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string"),
     *              @OA\Property(property="age", type="string"),
     *              @OA\Property(property="country", type="string"),
     *              @OA\Property(property="townCity", type="string", nullable=true),
     *              @OA\Property(property="gender", type="string", nullable=true),
     *              @OA\Property(property="biography", type="string", nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="email", type="string"),
     *                  @OA\Property(property="country_code", type="string", nullable=true),
     *                  @OA\Property(property="location", type="string", nullable=true),
     *                  @OA\Property(property="age", type="string", nullable=true),
     *                  @OA\Property(property="gender", type="string", nullable=true),
     *                  @OA\Property(property="biography", type="string", nullable=true)
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateMyProfilev2(Request $request, Geocoder $geocoder): JsonResponse
    {
        return response()->json([
            'data' => $this->applyProfileUpdate($request, Auth::user(), $geocoder),
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/{id}/profile",
     *      operationId="updateUserProfilev2",
     *      tags={"Users"},
     *      summary="Administrator (or self): update a user's profile info",
     *      description="Id-scoped mirror of PATCH /users/me/profile - identical validation and persistence, targeting the resolved user instead of Auth::user(). Authorised via UserPolicy::update (self-or-Administrator).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name", "email", "age", "country"},
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string"),
     *              @OA\Property(property="age", type="string"),
     *              @OA\Property(property="country", type="string"),
     *              @OA\Property(property="townCity", type="string", nullable=true),
     *              @OA\Property(property="gender", type="string", nullable=true),
     *              @OA\Property(property="biography", type="string", nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="email", type="string"),
     *                  @OA\Property(property="country_code", type="string", nullable=true),
     *                  @OA\Property(property="location", type="string", nullable=true),
     *                  @OA\Property(property="age", type="string", nullable=true),
     *                  @OA\Property(property="gender", type="string", nullable=true),
     *                  @OA\Property(property="biography", type="string", nullable=true)
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function updateUserProfilev2(Request $request, Geocoder $geocoder, int $id): JsonResponse
    {
        $target = User::findOrFail($id);
        $this->authorize('update', $target);

        return response()->json([
            'data' => $this->applyProfileUpdate($request, $target, $geocoder),
        ]);
    }

    /**
     * Shared body of updateMyProfilev2/updateUserProfilev2 so the self and admin paths can
     * never drift. $user is Auth::user() for the self route, or the resolved+authorised
     * target for the id-scoped admin route.
     */
    private function applyProfileUpdate(Request $request, User $user, Geocoder $geocoder): array
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'age' => 'required',
            'country' => 'required',
        ]);

        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'country_code' => $request->input('country'),
            'location' => $request->input('townCity'),
            'age' => $request->input('age'),
            'gender' => $request->input('gender'),
            'biography' => $request->input('biography'),
        ]);

        $user = $user->fresh();

        if (! empty($user->location)) {
            $geocoded = $geocoder->geocode("{$user->location}, " . Fixometer::getCountryFromCountryCode($user->country_code));
            if (! empty($geocoded)) {
                $user->latitude = $geocoded['latitude'];
                $user->longitude = $geocoded['longitude'];
            } else {
                $user->latitude = null;
                $user->longitude = null;
            }
        } else {
            $user->latitude = null;
            $user->longitude = null;
        }

        $user->save();

        return [
            'name' => $user->name,
            'email' => $user->email,
            'country_code' => $user->country_code,
            'location' => $user->location,
            'age' => $user->age,
            'gender' => $user->gender,
            'biography' => $user->biography,
        ];
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/me/skills",
     *      operationId="getMySkillsv2",
     *      tags={"Users"},
     *      summary="Get the repair-skills catalogue and the authenticated user's current selection",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="categories", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="label", type="string"),
     *                      @OA\Property(property="skills", type="array", @OA\Items(
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string")
     *                      ))
     *                  )),
     *                  @OA\Property(property="selected", type="array", @OA\Items(type="integer"))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMySkillsv2(): JsonResponse
    {
        return response()->json([
            'data' => $this->skillsData(Auth::user()),
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/{id}/skills",
     *      operationId="getUserSkillsv2",
     *      tags={"Users"},
     *      summary="Administrator (or self): get the repair-skills catalogue and a user's current selection",
     *      description="Id-scoped mirror of GET /users/me/skills, so an admin edit form can be pre-filled with the target's current selection before PATCHing it. Authorised via UserPolicy::update (self-or-Administrator).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="categories", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="label", type="string"),
     *                      @OA\Property(property="skills", type="array", @OA\Items(
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string")
     *                      ))
     *                  )),
     *                  @OA\Property(property="selected", type="array", @OA\Items(type="integer"))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function getUserSkillsv2(int $id): JsonResponse
    {
        $target = User::findOrFail($id);
        $this->authorize('update', $target);

        return response()->json([
            'data' => $this->skillsData($target),
        ]);
    }

    /**
     * Shared body of getMySkillsv2/getUserSkillsv2.
     */
    private function skillsData(User $user): array
    {
        $selected = UsersSkills::where('user', $user->id)
            ->pluck('skill')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $allSkills = Fixometer::allSkills();

        $categories = [];
        foreach (Fixometer::skillCategories() as $key => $label) {
            $skillsForCategory = [];
            if (isset($allSkills[$key])) {
                foreach ($allSkills[$key] as $skill) {
                    $skillsForCategory[] = [
                        'id' => (int) $skill->id,
                        'name' => $skill->skill_name,
                    ];
                }
            }
            $categories[] = [
                'id' => (int) $key,
                'label' => $label,
                'skills' => $skillsForCategory,
            ];
        }

        return [
            'categories' => $categories,
            'selected' => $selected,
        ];
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/me/skills",
     *      operationId="updateMySkillsv2",
     *      tags={"Users"},
     *      summary="Replace the authenticated user's repair skills",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="tags", type="array", @OA\Items(type="integer"))
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="tags", type="array", @OA\Items(type="integer"))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateMySkillsv2(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->applySkillsUpdate($request, Auth::user()),
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/{id}/skills",
     *      operationId="updateUserSkillsv2",
     *      tags={"Users"},
     *      summary="Administrator (or self): replace a user's repair skills",
     *      description="Id-scoped mirror of PATCH /users/me/skills. Authorised via UserPolicy::update (self-or-Administrator).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="tags", type="array", @OA\Items(type="integer"))
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="tags", type="array", @OA\Items(type="integer"))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function updateUserSkillsv2(Request $request, int $id): JsonResponse
    {
        $target = User::findOrFail($id);
        $this->authorize('update', $target);

        return response()->json([
            'data' => $this->applySkillsUpdate($request, $target),
        ]);
    }

    /**
     * Shared body of updateMySkillsv2/updateUserSkillsv2.
     */
    private function applySkillsUpdate(Request $request, User $user): array
    {
        $validated = $request->validate([
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
        ]);

        $skills = $validated['tags'] ?? [];

        $user->skillsold()->sync($skills);
        $user->refresh();

        $roleBasedOnSkills = Fixometer::skillsDetermineRole($skills);

        if ($roleBasedOnSkills == Role::HOST) {
            $user->convertToHost();
        }

        $currentSkillIds = UsersSkills::where('user', $user->id)
            ->pluck('skill')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'tags' => $currentSkillIds,
        ];
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/me/password",
     *      operationId="updateMyPasswordv2",
     *      tags={"Users"},
     *      summary="Change the authenticated user's password",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"current_password", "new_password", "new_password_confirmation"},
     *              @OA\Property(property="current_password", type="string"),
     *              @OA\Property(property="new_password", type="string"),
     *              @OA\Property(property="new_password_confirmation", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="success", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateMyPasswordv2(Request $request): JsonResponse
    {
        // This endpoint always operates on the authenticated user - there is no id
        // parameter, so there is no possibility of targeting another user's password.
        $user = Auth::user();
        $this->applyPasswordUpdate($request, $user, $user);

        return response()->json([
            'data' => [
                'success' => true,
            ],
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/{id}/password",
     *      operationId="updateUserPasswordv2",
     *      tags={"Users"},
     *      summary="Administrator (or self): change a user's password",
     *      description="Id-scoped mirror of PATCH /users/me/password. Authorised via UserPolicy::update (self-or-Administrator). When an Administrator resets ANOTHER user's password, current_password is not required or checked (matching the legacy admin edit-user form, which lets admins set a new password directly); when acting on your own id, current_password is still required, exactly as the self route.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"new_password", "new_password_confirmation"},
     *              @OA\Property(property="current_password", type="string", description="Required only when the target id is the acting user's own id"),
     *              @OA\Property(property="new_password", type="string"),
     *              @OA\Property(property="new_password_confirmation", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="success", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function updateUserPasswordv2(Request $request, int $id): JsonResponse
    {
        $actor = Auth::user();
        $target = User::findOrFail($id);
        $this->authorize('update', $target);

        $this->applyPasswordUpdate($request, $actor, $target);

        return response()->json([
            'data' => [
                'success' => true,
            ],
        ]);
    }

    /**
     * Shared body of updateMyPasswordv2/updateUserPasswordv2. $actor is who is making the
     * request, $target is whose password is being changed - they are the same User instance
     * on the self route. current_password is only required/checked when $actor === $target;
     * an Administrator resetting another user's password doesn't know (and shouldn't need)
     * the target's current password.
     */
    private function applyPasswordUpdate(Request $request, User $actor, User $target): void
    {
        $isSelf = $actor->id == $target->id;

        $rules = [
            'new_password' => 'required|string',
            'new_password_confirmation' => 'required|string',
        ];
        if ($isSelf) {
            $rules['current_password'] = 'required|string';
        }

        $validated = $request->validate($rules);

        if ($validated['new_password'] !== $validated['new_password_confirmation']) {
            throw ValidationException::withMessages([
                'new_password_confirmation' => [__('profile.password_new_mismatch')],
            ]);
        }

        if ($isSelf && ! Hash::check($validated['current_password'], $target->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('profile.password_old_mismatch')],
            ]);
        }

        $oldPassword = $target->password;
        $target->setPassword(Hash::make($validated['new_password']));
        $target->save();

        $target->update([
            'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
            'recovery_expires' => strftime('%Y-%m-%d %X', time() + (24 * 60 * 60)),
        ]);

        event(new PasswordChanged($target, $oldPassword));
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/{id}/admin-settings",
     *      operationId="getAdminSettingsv2",
     *      tags={"Users"},
     *      summary="Administrator-only: get a user's role, groups, preferences, permissions and the available options",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="role", type="integer", nullable=true),
     *                  @OA\Property(property="assigned_groups", type="array", @OA\Items(type="integer")),
     *                  @OA\Property(property="preferences", type="array", @OA\Items(type="integer")),
     *                  @OA\Property(property="permissions", type="array", @OA\Items(type="integer")),
     *                  @OA\Property(property="roles", type="array", @OA\Items(
     *                      @OA\Property(property="value", type="integer"),
     *                      @OA\Property(property="label", type="string")
     *                  )),
     *                  @OA\Property(property="groups", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string")
     *                  )),
     *                  @OA\Property(property="preferences_options", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="purpose", type="string", nullable=true)
     *                  )),
     *                  @OA\Property(property="permissions_options", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="purpose", type="string", nullable=true)
     *                  ))
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="User not found")
     * )
     */
    public function getAdminSettingsv2(int $id): JsonResponse
    {
        if (! Auth::user()->hasRole('Administrator')) {
            abort(403);
        }

        $user = User::find($id);
        if (! $user) {
            throw new NotFoundHttpException();
        }

        $roles = Role::all()->map(fn ($r) => [
            'value' => (int) $r->idroles,
            'label' => $r->role,
        ])->values()->all();

        $groups = Group::orderBy('name')->get()->map(fn ($g) => [
            'id' => (int) $g->idgroups,
            'name' => $g->name,
        ])->values()->all();

        $preferencesOptions = Preferences::all()->map(fn ($p) => [
            'id' => (int) $p->id,
            'name' => $p->name,
            'purpose' => $p->purpose,
        ])->values()->all();

        $permissionsOptions = Permissions::all()->map(fn ($p) => [
            'id' => (int) $p->idpermissions,
            'name' => $p->permission,
            'purpose' => $p->purpose,
        ])->values()->all();

        return response()->json([
            'data' => [
                'role' => $user->role,
                'assigned_groups' => $user->groups()->pluck('idgroups')->map(fn ($v) => (int) $v)->values()->all(),
                'preferences' => DB::table('users_preferences')->where('user_id', $user->id)->pluck('preference_id')->map(fn ($v) => (int) $v)->values()->all(),
                'permissions' => DB::table('users_permissions')->where('user_id', $user->id)->pluck('permission_id')->map(fn ($v) => (int) $v)->values()->all(),
                'roles' => $roles,
                'groups' => $groups,
                'preferences_options' => $preferencesOptions,
                'permissions_options' => $permissionsOptions,
            ],
        ]);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/users/{id}/admin-settings",
     *      operationId="updateAdminSettingsv2",
     *      tags={"Users"},
     *      summary="Administrator-only: update a user's role, groups, preferences and permissions",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"user_role"},
     *              @OA\Property(property="user_role", type="integer"),
     *              @OA\Property(property="assigned_groups", type="array", @OA\Items(type="integer")),
     *              @OA\Property(property="preferences", type="array", @OA\Items(type="integer")),
     *              @OA\Property(property="permissions", type="array", @OA\Items(type="integer"))
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="role", type="integer")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="User not found"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateAdminSettingsv2(Request $request, int $id): JsonResponse
    {
        // Administrator-only. Matches the web handler's exact check (postAdminEdit) -
        // this is a critical privilege-escalation guard, so it is deliberately kept as
        // simple and explicit as the code it replaces.
        if (! Auth::user()->hasRole('Administrator')) {
            abort(403);
        }

        $user = User::find($id);
        if (! $user) {
            throw new NotFoundHttpException();
        }

        $validated = $request->validate([
            'user_role' => 'required|integer',
            'assigned_groups' => 'nullable|array',
            'assigned_groups.*' => 'integer',
            'preferences' => 'nullable|array',
            'preferences.*' => 'integer',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer',
        ]);

        $groups = $validated['assigned_groups'] ?? [];
        $preferences = $validated['preferences'] ?? [];
        $permissions = $validated['permissions'] ?? [];

        $oldRole = $user->role;

        // Set role directly - role is not mass-assignable (security: see UserController::postAdminEdit).
        $user->role = $validated['user_role'];
        $user->save();

        // If we are demoting from NetworkCoordinator, remove them from the list of coordinators for
        // any networks they are currently coordinating.
        if ($oldRole == Role::NETWORK_COORDINATOR && ($user->role == Role::HOST || $user->role == Role::RESTARTER)) {
            $user->networks()->detach();
        }

        // The user may have previously been removed from a group, which will mean they have an entry in
        // users_groups with deleted_at set. Restore it so that sync() then works - sync() doesn't handle
        // soft deletes itself.
        foreach ($groups as $idgroups) {
            $inGroup = UserGroups::where('user', $id)->where('group', $idgroups)->withTrashed()->first();

            if ($inGroup && $inGroup->trashed()) {
                $inGroup->restore();
            }
        }

        $user->groups()->sync($groups);
        $user->preferences()->sync($preferences);
        $user->permissions()->sync($permissions);

        return response()->json([
            'data' => [
                'role' => $user->fresh()->role,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/users/me/photo",
     *      operationId="updateMyPhotov2",
     *      tags={"Users"},
     *      summary="Attach a completed tus upload as the authenticated user's profile photo",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"upload_key"},
     *              @OA\Property(property="upload_key", type="string", description="The tus upload key/id returned by the tus server once the upload completed")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="path", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateMyPhotov2(Request $request): JsonResponse
    {
        // This endpoint always operates on Auth::user() - there is no id parameter, so
        // there is no possibility of uploading a photo on behalf of another user.
        $user = Auth::user();

        $validated = $request->validate([
            'upload_key' => 'required|string',
        ]);

        $cache = Tus::buildCache();
        $meta = $cache->get($validated['upload_key']);

        $filePath = $meta['file_path'] ?? null;

        if (! $meta || ! $filePath || ! is_file($filePath)) {
            throw ValidationException::withMessages([
                'upload_key' => [__('profile.picture_error')],
            ]);
        }

        // Confirm the tus upload actually finished (offset === size), not just started.
        if (($meta['offset'] ?? null) !== ($meta['size'] ?? null)) {
            throw ValidationException::withMessages([
                'upload_key' => [__('profile.picture_error')],
            ]);
        }

        // Max 2MB, matching the previous multipart contract.
        if (filesize($filePath) > 2 * 1024 * 1024) {
            $cache->delete($validated['upload_key']);
            @unlink($filePath);

            throw ValidationException::withMessages([
                'upload_key' => [__('profile.picture_error')],
            ]);
        }

        // Validate it's really an image FixometerFile knows how to handle (jpeg/png/gif -
        // matches the whitelist in FixometerFile::filename()), before we touch any DB state.
        $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            $cache->delete($validated['upload_key']);
            @unlink($filePath);

            throw ValidationException::withMessages([
                'upload_key' => [__('profile.picture_error')],
            ]);
        }

        $file = new \FixometerFile();
        $filename = $file->uploadLocalFile($filePath, 'image', $user->id, env('TBL_USERS'), true);

        // Whether it succeeded or not, the tus temp file has served its purpose - remove it
        // and its cache entry so it isn't left behind (and can't be replayed against /photo).
        $cache->delete($validated['upload_key']);
        @unlink($filePath);

        if (! $filename) {
            throw ValidationException::withMessages([
                'upload_key' => [__('profile.picture_error')],
            ]);
        }

        return response()->json([
            'data' => [
                'path' => $filename,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/users/me",
     *      operationId="deleteMyAccountv2",
     *      tags={"Users"},
     *      summary="Soft-delete (and anonymise) the authenticated user's account",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="success", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deleteMyAccountv2(Request $request): JsonResponse
    {
        // This endpoint always operates on Auth::user() - there is no id parameter, so a
        // user can only ever delete their own account via this route.
        $user = Auth::user();

        $this->authorize('delete', $user);

        $user->delete(); // Will be anonymised automatically by event handlers (see postSoftDeleteUser).

        return response()->json([
            'data' => [
                'success' => true,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/users/{id}",
     *      operationId="deleteUserv2",
     *      tags={"Users"},
     *      summary="Administrator (or self): soft-delete a user's account",
     *      description="Id-scoped mirror of DELETE /users/me. Authorised via UserPolicy::delete (self-or-Administrator).",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="success", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function deleteUserv2(int $id): JsonResponse
    {
        $target = User::findOrFail($id);

        $this->authorize('delete', $target);

        $target->delete(); // Will be anonymised automatically by event handlers (see postSoftDeleteUser).

        return response()->json([
            'data' => [
                'success' => true,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/users",
     *      operationId="createUserv2",
     *      tags={"Users"},
     *      summary="Administrator-only: create a new user account",
     *      description="Port of the legacy 'Create new user' admin form (UserController::create / includes/modals/create-user.blade.php), adapted so the admin sets the initial password directly rather than a randomly-generated one that was never emailed to anyone. No consent is recorded and no admin-new-user notification is sent - both are specific to self-registration (AuthController::registerv2).",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name", "email", "role", "password"},
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string"),
     *              @OA\Property(property="role", type="integer", description="A roles.idroles value"),
     *              @OA\Property(property="password", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Created",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/UserAdmin"))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *      @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function createUserv2(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            // min:6 matches AuthController::registerv2's password rule - this is the same
            // account, just created by an Administrator instead of via self-registration.
            'password' => 'required|string|min:6',
            'role' => 'required|integer|exists:roles,idroles',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
            'recovery_expires' => date('Y-m-d H:i:s', time() + (24 * 60 * 60)),
            'calendar_hash' => Str::random(15),
            // username is NOT NULL with no default - filled below via generateAndSetUsername(),
            // exactly like AuthController::registerv2.
            'username' => '',
        ]);

        // role excluded from $fillable (security: privilege escalation via mass assignment)
        // - must be set via direct assignment, matching registerv2/updateAdminSettingsv2.
        $user->role = $validated['role'];
        $user->generateAndSetUsername();
        $user->save();

        return response()->json([
            'data' => (new UserAdmin($user))->toArray($request),
        ], 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users",
     *      operationId="listUsersv2",
     *      tags={"Users"},
     *      summary="List users with optional filtering and sorting",
     *      description="Administrator only. Paginated.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="name", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="email", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="location", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="country", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="role", in="query", required=false, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="permissions[]", in="query", required=false, description="Only return users whose role holds ALL of these permission ids", @OA\Schema(type="array", @OA\Items(type="integer"))),
     *      @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"name","email","role","location","country","created_at","updated_at"})),
     *      @OA\Parameter(name="sortdir", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
     *      @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserAdmin")),
     *              @OA\Property(property="meta", type="object")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function listUsersv2(Request $request): JsonResponse
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = User::query()
            ->leftJoin('roles', 'roles.idroles', '=', 'users.role')
            ->select('users.*', 'roles.role as role_name')
            ->withCount('groups');

        if ($name = $request->input('name')) {
            $query->where('users.name', 'like', '%' . $name . '%');
        }
        if ($email = $request->input('email')) {
            $query->where('users.email', 'like', '%' . $email . '%');
        }
        if ($location = $request->input('location')) {
            $query->where('users.location', 'like', '%' . $location . '%');
        }
        if ($country = $request->input('country')) {
            $query->where('users.country_code', '=', $country);
        }
        if (($role = $request->input('role')) !== null && $role !== '') {
            $query->where('users.role', '=', (int) $role);
        }

        $validated = $request->validate([
            'permissions' => 'sometimes|array',
            'permissions.*' => 'integer',
        ]);
        if (! empty($validated['permissions'])) {
            // Permissions are held by roles (roles_permissions), not directly by users -
            // mirrors the legacy UserController::search, which filtered on
            // array_column($User->getRolePermissions($user->role), 'idpermissions').
            // Restrict to roles that hold EVERY selected permission.
            $permissionIds = $validated['permissions'];
            $query->whereIn('users.role', function ($subquery) use ($permissionIds) {
                $subquery->select('role')
                    ->from('roles_permissions')
                    ->whereIn('permission', $permissionIds)
                    ->groupBy('role')
                    ->havingRaw('COUNT(DISTINCT permission) = ?', [count($permissionIds)]);
            });
        }

        $sortMap = [
            'name' => 'users.name',
            'email' => 'users.email',
            'role' => 'users.role',
            'location' => 'users.location',
            'country' => 'users.country_code',
            'created_at' => 'users.created_at',
            'updated_at' => 'users.updated_at',
        ];
        $sort = $request->input('sort');
        if ($sort && isset($sortMap[$sort])) {
            $dir = strtolower($request->input('sortdir', 'asc'));
            if (!in_array($dir, ['asc', 'desc'], true)) {
                $dir = 'asc';
            }
            $query->orderBy($sortMap[$sort], $dir);
        } else {
            $query->orderBy('users.id', 'asc');
        }

        $perPage = (int) (env('PAGINATE') ?: 30);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => UserAdmin::collection($paginator->getCollection())->toArray($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/users/{id}",
     *      operationId="getPublicProfilev2",
     *      tags={"Users"},
     *      summary="Get a user's public (PII-safe) profile",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string"),
     *                  @OA\Property(property="avatar_url", type="string", nullable=true),
     *                  @OA\Property(property="role_name", type="string", nullable=true),
     *                  @OA\Property(property="location", type="string", nullable=true),
     *                  @OA\Property(property="groups", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string")
     *                  )),
     *                  @OA\Property(property="skills", type="array", @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string")
     *                  )),
     *                  @OA\Property(property="biography", type="string", nullable=true),
     *                  @OA\Property(property="talk_profile_url", type="string", nullable=true),
     *                  @OA\Property(property="on_talk", type="boolean")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="User not found")
     * )
     *
     * PII-safe public profile behind pages/profile/[id].vue and /profile
     * (resources/views/user/profile-new.blade.php via the legacy
     * UserController::index($id) is the functional spec). No permission check
     * beyond the route's own auth middleware: any logged-in user may view any
     * profile, exactly as the legacy Blade page did. Fields limited to what
     * that page rendered publicly - name, avatar, role name, location, the
     * user's groups and skills, and their biography.
     */
    public function getPublicProfilev2($id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Same xref/thumbnail derivation SessionController::userPayload() and
        // the legacy profile view (User::getProfile) use, so the avatar here
        // matches what the navbar already shows for the same person.
        $profile = User::getProfile($user->id);
        $avatarUrl = ($profile && $profile->path) ? url('/uploads/thumbnail_'.$profile->path) : null;

        // Mirrors the legacy profile-new.blade.php's
        // $user->existsOnDiscourse() / getTalkProfileUrl() pairing: null/false
        // when Discourse integration is off or the user has no Discourse
        // account, so the client only ever renders a link that actually works.
        $onTalk = $user->existsOnDiscourse();

        return response()->json([
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'avatar_url' => $avatarUrl,
                'role_name' => optional($user->role()->first())->role,
                'location' => $user->location,
                'groups' => $user->groups->map(fn ($group) => [
                    'id' => (int) $group->idgroups,
                    'name' => $group->name,
                ])->values(),
                'skills' => $user->skills->map(fn ($skill) => [
                    'id' => (int) $skill->id,
                    'name' => $skill->skill_name,
                ])->values(),
                'biography' => $user->biography,
                'talk_profile_url' => $onTalk ? $user->getTalkProfileUrl() : null,
                'on_talk' => $onTalk,
            ],
        ]);
    }
}
