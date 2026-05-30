<?php

namespace App\Http\Controllers\API;

use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserAdmin;
use App\Role;
use App\User;
use Auth;
use Cache;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
