<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Auth;
use Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $user = Auth::user();

        return response()->json([
            'data' => [
                'invites' => (bool) $user->invites,
            ],
        ]);
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
        $validated = $request->validate([
            'invites' => 'required|boolean',
        ]);

        $user = Auth::user();
        $user->invites = $validated['invites'] ? 1 : 0;
        $user->save();

        return response()->json([
            'data' => [
                'invites' => (bool) $user->invites,
            ],
        ]);
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
}
