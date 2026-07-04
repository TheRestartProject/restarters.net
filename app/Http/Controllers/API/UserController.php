<?php

namespace App\Http\Controllers\API;

use App\Events\PasswordChanged;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\Geocoder;
use App\Http\Controllers\Controller;
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
        $user = Auth::user();

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'country_code' => $user->country_code,
                'location' => $user->location,
                'age' => $user->age,
                'gender' => $user->gender,
                'biography' => $user->biography,
                'countries' => $this->profileCountryOptions(),
                'ages' => $this->profileAgeOptions(),
            ],
        ]);
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'age' => 'required',
            'country' => 'required',
        ]);

        $user = Auth::user();

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

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'country_code' => $user->country_code,
                'location' => $user->location,
                'age' => $user->age,
                'gender' => $user->gender,
                'biography' => $user->biography,
            ],
        ]);
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
        $user = Auth::user();

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

        return response()->json([
            'data' => [
                'categories' => $categories,
                'selected' => $selected,
            ],
        ]);
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
        $validated = $request->validate([
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
        ]);

        $user = Auth::user();
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

        return response()->json([
            'data' => [
                'tags' => $currentSkillIds,
            ],
        ]);
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
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string',
            'new_password_confirmation' => 'required|string',
        ]);

        // This endpoint always operates on the authenticated user - there is no id
        // parameter, so there is no possibility of targeting another user's password.
        $user = Auth::user();

        if ($validated['new_password'] !== $validated['new_password_confirmation']) {
            throw ValidationException::withMessages([
                'new_password_confirmation' => [__('profile.password_new_mismatch')],
            ]);
        }

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('profile.password_old_mismatch')],
            ]);
        }

        $oldPassword = $user->password;
        $user->setPassword(Hash::make($validated['new_password']));
        $user->save();

        $user->update([
            'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
            'recovery_expires' => strftime('%Y-%m-%d %X', time() + (24 * 60 * 60)),
        ]);

        event(new PasswordChanged($user, $oldPassword));

        return response()->json([
            'data' => [
                'success' => true,
            ],
        ]);
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
     *      summary="Upload the authenticated user's profile photo",
     *      security={{"apiToken":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(property="profilePhoto", type="string", format="binary")
     *              )
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

        $request->validate([
            'profilePhoto' => 'required|image|mimes:jpeg,png,gif|max:2048',
        ]);

        $file = new \FixometerFile();
        $filename = $file->upload('profilePhoto', 'image', $user->id, env('TBL_USERS'), false, true);

        if (! $filename) {
            throw ValidationException::withMessages([
                'profilePhoto' => [__('profile.picture_error')],
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
}
