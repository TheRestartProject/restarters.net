<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Device;
use App\Group;
use App\Party;
use App\User;
use Auth;
use DB;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="2.0.0",
 *      title="Restarters API",
 *      description="The Restarters API. The v2 surface (`/api/v2/*`) powers the Nuxt single-page app and is authenticated with a Sanctum bearer token (`Authorization: Bearer <token>`) unless an operation is explicitly marked public. A small number of legacy read-only endpoints outside `/api/v2` use the `?api_token=` query-string convention (see the ApiKeyAuth scheme).",
 *      @OA\Contact(
 *          email="tech@therestartproject.org"
 *      ),
 *      @OA\License(
 *          name="GPL v3",
 *          url="https://tldrlegal.com/license/gnu-general-public-license-v3-(gpl-3)"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST_LIVE,
 *      description="Live API Server"
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST_TEST,
 *      description="Test API Server"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="apiToken",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="Sanctum",
 *   description="Sanctum personal-access token issued by POST /api/v2/auth/login or /register. Send as `Authorization: Bearer <token>`. This is the scheme every authenticated /api/v2 operation requires.",
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="ApiKeyAuth",
 *   type="apiKey",
 *   in="query",
 *   name="api_token",
 *   description="Legacy query-string token (`?api_token=`) used only by the pre-v2 read-only endpoints outside /api/v2.",
 *  )
 */
class ApiController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/homepage_data",
     *      operationId="getHomepageDataLegacy",
     *      tags={"Legacy"},
     *      summary="Get sitewide headline stats for the public homepage widget",
     *      description="Legacy, unauthenticated endpoint. Used from DeviceController and embedded at https://therestartproject.org. Aggregates events-held/participants/hours-volunteered/items-fixed/waste/CO2 figures across all past, non-deleted events. The result is cached for 12 hours (`homepage_data` cache key); a stale or empty result may be served briefly while another worker rebuilds the cache.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="events_held", type="integer", example=1200),
     *              @OA\Property(property="participants", type="integer", example=45000),
     *              @OA\Property(property="hours_volunteered", type="integer", example=180000),
     *              @OA\Property(property="items_fixed", type="integer", example=28000),
     *              @OA\Property(property="waste_powered", type="number", example=12000),
     *              @OA\Property(property="waste_unpowered", type="number", example=8000),
     *              @OA\Property(property="waste_total", type="number", example=20000),
     *              @OA\Property(property="co2_powered", type="number", example=5000),
     *              @OA\Property(property="co2_unpowered", type="number", example=3000),
     *              @OA\Property(property="co2_total", type="number", example=8000),
     *              @OA\Property(property="fixed_powered", type="integer", example=18000),
     *              @OA\Property(property="fixed_unpowered", type="integer", example=10000),
     *              @OA\Property(property="total_powered", type="integer", example=25000),
     *              @OA\Property(property="total_unpowered", type="integer", example=15000),
     *              @OA\Property(property="weights", type="number", description="Alias of waste_total, kept for backward compatibility.", example=20000),
     *              @OA\Property(property="ewaste", type="number", description="Alias of waste_powered, kept for backward compatibility.", example=12000),
     *              @OA\Property(property="unpowered_waste", type="number", description="Alias of waste_unpowered, kept for backward compatibility.", example=8000),
     *              @OA\Property(property="emissions", type="number", description="Alias of co2_total, kept for backward compatibility.", example=8000)
     *          )
     *      )
     * )
     */
    public static function homepage_data(): JsonResponse
    {
        $result = [];

        $lock = \Cache::lock('homepage_data_lock', 60);

        if (\Cache::has('homepage_data')) {
            $result = \Cache::get('homepage_data');
        } elseif ($lock->get()) {
            try {
                $Device = new Device;

                // Aggregate participants and hours in SQL — avoids loading 18k+ event rows into PHP.
                // hoursVolunteered() formula: cancelled→3, volunteers>0→9+volunteers*ceil(minutes/60), else→21
                $eventStats = DB::table('events')
                    ->whereNull('deleted_at')
                    ->where('event_end_utc', '<', now())
                    ->selectRaw("
                        COUNT(*) as events_held,
                        SUM(pax) as participants,
                        SUM(CASE
                            WHEN cancelled = 1 THEN 3
                            WHEN volunteers > 0 THEN 9 + volunteers * CEIL(TIMESTAMPDIFF(MINUTE, event_start_utc, event_end_utc) / 60)
                            ELSE 21
                        END) as hours_volunteered
                    ")
                    ->first();

                // events_held mirrors legacy Fixometer::computeStats()'s
                // partiesCount (past, non-deleted events) - it is the "Events
                // held" figure in the logged-out header stats bar.
                $result['events_held'] = (int) ($eventStats->events_held ?? 0);
                $result['participants'] = (int) ($eventStats->participants ?? 0);
                $result['hours_volunteered'] = (int) ($eventStats->hours_volunteered ?? 0);

                $fixed = $Device->statusCount();
                $result['items_fixed'] = count($fixed) ? $fixed[0]->counter : 0;

                $stats = \App\Helpers\LcaStats::getWasteStats();
                $result['waste_powered'] = round($stats[0]->powered_waste);
                $result['waste_unpowered'] = round($stats[0]->unpowered_waste);
                $result['waste_total'] = round($stats[0]->powered_waste + $stats[0]->unpowered_waste);
                $result['co2_powered'] = round($stats[0]->powered_footprint);
                $result['co2_unpowered'] = round($stats[0]->unpowered_footprint);
                $result['co2_total'] = round($stats[0]->powered_footprint + $stats[0]->unpowered_footprint);

                $devices = new Device;
                $result['fixed_powered'] = $devices->fixedPoweredCount();
                $result['fixed_unpowered'] = $devices->fixedUnpoweredCount();
                $result['total_powered'] = $devices->poweredCount();
                $result['total_unpowered'] = $devices->unpoweredCount();

                // for backward compatibility (don't break therestartproject.org)
                $result['weights'] = round($result['waste_total']);
                $result['ewaste'] = round($result['waste_powered']);
                $result['unpowered_waste'] = round($result['waste_unpowered']);
                $result['emissions'] = round($result['co2_total']);

                \Cache::put('homepage_data', $result, 43200);
            } finally {
                $lock->release();
            }
        } else {
            // Another worker is rebuilding — return stale or empty rather than pile on
            $result = \Cache::get('homepage_data', []);
        }

        return response()
            ->json($result, 200);
    }

    /**
     * @OA\Get(
     *      path="/api/party/{id}/stats",
     *      operationId="getPartyStatsLegacy",
     *      tags={"Legacy","Events"},
     *      summary="Get impact stats for a single event",
     *      description="Legacy, unauthenticated endpoint used from TRP.org.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Event (party) id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="num_participants", type="integer", example=25),
     *              @OA\Property(property="num_volunteers", type="integer", example=6),
     *              @OA\Property(property="num_hours_volunteered", type="integer", example=42),
     *              @OA\Property(property="num_fixed_devices", type="integer", example=14),
     *              @OA\Property(property="num_repairable_devices", type="integer", example=3),
     *              @OA\Property(property="num_dead_devices", type="integer", example=2),
     *              @OA\Property(property="kg_powered_co2_diverted", type="integer", example=120),
     *              @OA\Property(property="kg_unpowered_co2_diverted", type="integer", example=40),
     *              @OA\Property(property="kg_powered_waste_diverted", type="integer", example=300),
     *              @OA\Property(property="kg_unpowered_waste_diverted", type="integer", example=90),
     *              @OA\Property(property="kg_co2_diverted", type="integer", example=160),
     *              @OA\Property(property="kg_waste_diverted", type="integer", example=390)
     *          )
     *      ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public static function partyStats($partyId): JsonResponse
    {
        $event = Party::where('idevents', $partyId)->first();

        if (! $event) {
            return response()->json([
                'message' => "Invalid party id $partyId",
            ], 404);
        }

        $stats = $event->getEventStats();

        $result = [
            'num_participants' => $stats['participants'],
            'num_volunteers' => $stats['volunteers'],
            'num_hours_volunteered' => $stats['hours_volunteered'],
            'num_fixed_devices' => $stats['fixed_devices'],
            'num_repairable_devices' => $stats['repairable_devices'],
            'num_dead_devices' => $stats['dead_devices'],
            'kg_powered_co2_diverted' => round($stats['co2_powered']),
            'kg_unpowered_co2_diverted' => round($stats['co2_unpowered']),
            'kg_powered_waste_diverted' => round($stats['waste_powered']),
            'kg_unpowered_waste_diverted' => round($stats['waste_unpowered']),
            'kg_co2_diverted' => round($stats['co2_total']),
            'kg_waste_diverted' => round($stats['waste_total']),
        ];

        return response()->json($result, 200);
    }

    /**
     * @OA\Get(
     *      path="/api/group/{id}/stats",
     *      operationId="getGroupStatsLegacy",
     *      tags={"Legacy","Groups"},
     *      summary="Get impact stats for a single group",
     *      description="Legacy, unauthenticated endpoint used from TRP.org.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Group id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="num_parties", type="integer", example=18),
     *              @OA\Property(property="num_participants", type="integer", example=450),
     *              @OA\Property(property="num_hours_volunteered", type="integer", example=760),
     *              @OA\Property(property="num_fixed_devices", type="integer", example=260),
     *              @OA\Property(property="num_repairable_devices", type="integer", example=40),
     *              @OA\Property(property="num_dead_devices", type="integer", example=20),
     *              @OA\Property(property="kg_powered_co2_diverted", type="integer", example=2100),
     *              @OA\Property(property="kg_unpowered_co2_diverted", type="integer", example=700),
     *              @OA\Property(property="kg_powered_waste_diverted", type="integer", example=5400),
     *              @OA\Property(property="kg_unpowered_waste_diverted", type="integer", example=1600),
     *              @OA\Property(property="kg_co2_diverted", type="integer", example=2800),
     *              @OA\Property(property="kg_waste_diverted", type="integer", example=7000)
     *          )
     *      ),
     *      @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public static function groupStats($groupId): JsonResponse
    {
        $group = Group::where('idgroups', $groupId)->first();

        if (!$group) {
            return response()->json([
                                        'message' => "Invalid group id $groupId",
                                    ], 404);
        }

        $stats = $group->getGroupStats();

        $result = [
                'num_parties' => $stats['parties'],
                'num_participants' => $stats['participants'],
                'num_hours_volunteered' => $stats['hours_volunteered'],
                'num_fixed_devices' => $stats['fixed_devices'],
                'num_repairable_devices' => $stats['repairable_devices'],
                'num_dead_devices' => $stats['dead_devices'],
                'kg_powered_co2_diverted' => round($stats['co2_powered']),
                'kg_unpowered_co2_diverted' => round($stats['co2_unpowered']),
                'kg_powered_waste_diverted' => round($stats['waste_powered']),
                'kg_unpowered_waste_diverted' => round($stats['waste_unpowered']),
                'kg_co2_diverted' => round($stats['co2_total']),
                'kg_waste_diverted' => round($stats['waste_total']),

            ];

        return response()->json($result, 200);
    }

    /**
     * @OA\Get(
     *      path="/api/users/me",
     *      operationId="getUserInfoLegacy",
     *      tags={"Legacy","Users"},
     *      summary="Get the authenticated user's profile",
     *      description="Legacy endpoint kept for backward compatibility; the Nuxt client uses GET /api/v2/session instead. Returns the raw user row (all columns) with credential/PII fields (api_token, calendar_hash, recovery, recovery_expires, mediawiki, latitude, longitude) stripped.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example=42),
     *              @OA\Property(property="name", type="string", example="Jane Doe"),
     *              @OA\Property(property="email", type="string", example="jane@example.com"),
     *              @OA\Property(property="username", type="string", nullable=true, example="janedoe"),
     *              @OA\Property(property="role", type="integer", example=3),
     *              @OA\Property(property="language", type="string", nullable=true, example="en"),
     *              @OA\Property(property="location", type="string", nullable=true, example="London"),
     *              @OA\Property(property="country_code", type="string", nullable=true, example="GB"),
     *              @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
     *              @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     */
    public static function getUserInfo(): JsonResponse
    {
        $user = Auth::user();

        // api_token and other credentials are in User::$hidden; makeHidden() is
        // a belt-and-suspenders guard for any future $hidden regression.
        $user->makeHidden(['api_token', 'calendar_hash', 'recovery', 'recovery_expires', 'mediawiki', 'latitude', 'longitude']);

        return response()->json($user->toArray());
    }

    /**
     * @OA\Get(
     *      path="/api/users",
     *      operationId="getUserListLegacy",
     *      tags={"Legacy","Users"},
     *      summary="List all users (Administrator only)",
     *      description="Legacy endpoint. Returns every non-deleted user as a raw user row (all columns except the model's hidden credential fields), newest-created first.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=42),
     *                  @OA\Property(property="name", type="string", example="Jane Doe"),
     *                  @OA\Property(property="email", type="string", example="jane@example.com"),
     *                  @OA\Property(property="role", type="integer", example=3),
     *                  @OA\Property(property="created_at", type="string", format="date-time", nullable=true)
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public static function getUserList()
    {
        $authenticatedUser = Auth::user();
        if (! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $users = User::whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * @OA\Get(
     *      path="/api/devices/{page}/{size}",
     *      operationId="getDevicesLegacy",
     *      tags={"Legacy","Devices"},
     *      summary="Search/paginate devices",
     *      description="Legacy, unauthenticated endpoint used by the Vue client. Joins events/groups/categories to support filtering; results are ordered by sortBy/sortDesc.",
     *      @OA\Parameter(name="page", description="1-based page number", required=true, in="path", @OA\Schema(type="integer", example=1)),
     *      @OA\Parameter(name="size", description="Number of items per page", required=true, in="path", @OA\Schema(type="integer", example=20)),
     *      @OA\Parameter(name="powered", description="Filter by whether the device's category is powered", required=false, in="query", @OA\Schema(type="string", enum={"true","false"})),
     *      @OA\Parameter(name="sortBy", description="Column to sort by", required=false, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="sortDesc", description="Sort direction, passed straight through to orderBy()", required=false, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="category", description="Filter by category id", required=false, in="query", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="brand", description="Filter by brand (partial match)", required=false, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="model", description="Filter by model (partial match)", required=false, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="item_type", description="Filter by item type (partial match)", required=false, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="status", description="Filter by repair status", required=false, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="comments", description="Filter by problem/comments text (partial match)", required=false, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="wiki", description="Only include devices flagged for the wiki", required=false, in="query", @OA\Schema(type="boolean")),
     *      @OA\Parameter(name="group", description="Filter by group name (partial match)", required=false, in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="from_date", description="Only include devices from events starting on/after this date", required=false, in="query", @OA\Schema(type="string", format="date")),
     *      @OA\Parameter(name="to_date", description="Only include devices from events ending on/before this date", required=false, in="query", @OA\Schema(type="string", format="date")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="count", type="integer", description="Total number of matching devices across all pages", example=532),
     *              @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/Device"))
     *          )
     *      )
     * )
     */
    public static function getDevices(Request $request, $page, $size): JsonResponse
    {
        $powered = $request->input('powered');
        $sortBy = $request->input('sortBy');
        $sortDesc = $request->input('sortDesc');
        $category = $request->input('category');
        $brand = $request->input('brand');
        $model = $request->input('model');
        $item_type = $request->input('item_type');
        $status = $request->input('status');
        $comments = $request->input('comments');
        $wiki = filter_var($request->input('wiki', false), FILTER_VALIDATE_BOOLEAN);
        $group = $request->input('group');
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');

        $wheres = [
            ['categories.powered', '=', $powered == 'true' ? 1 : 0],
        ];

        if ($category) {
            $wheres[] = ['idcategories', '=', $category];
        }

        if ($brand) {
            $wheres[] = ['devices.brand', 'LIKE', '%'.$brand.'%'];
        }

        if ($model) {
            $wheres[] = ['devices.model', 'LIKE', '%'.$model.'%'];
        }

        if ($item_type) {
            $wheres[] = ['devices.item_type', 'LIKE', '%'.$item_type.'%'];
        }

        if ($comments) {
            $wheres[] = ['devices.problem', 'LIKE', '%'.$comments.'%'];
        }

        if ($wiki) {
            $wheres[] = ['devices.wiki', '=', 1];
        }

        if ($status) {
            $wheres[] = ['repair_status', '=', $status];
        }

        if ($group) {
            $wheres[] = ['groups.name', 'LIKE', '%'.$group.'%'];
        }

        if ($from_date) {
            $wheres[] = ['events.event_start_utc', '>=', $from_date];
        }

        if ($to_date) {
            $wheres[] = ['events.event_end_utc', '<=', $to_date];
        }

        // Get the items we want for this page.
        $query = Device::with(['deviceEvent.theGroup', 'deviceCategory', 'barriers'])
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'events.group', '=', 'groups.idgroups')
            ->join('categories', 'devices.category', '=', 'categories.idcategories')
            ->where($wheres)
            ->orderBy($sortBy, $sortDesc);

        // Get total info across all pages.
        $count = $query->count();

        $items = $query->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        // Batch-load device images to avoid N+1 per device.
        $device_ids = $items->pluck('iddevices')->toArray();
        $allImages = (new \FixometerFile)->findImagesForMany(env('TBL_DEVICES'), $device_ids);
        foreach ($items as $item) {
            $item->preloadedImages = $allImages[$item->iddevices] ?? [];
        }

        $item_data = [];

        foreach ($items as $item) {
            $item_data[] = (new \App\Http\Resources\Device($item))->resolve();
        }

        return response()->json([
            'count' => $count,
            'items' => $item_data,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/timezones",
     *      operationId="getTimezonesLegacy",
     *      tags={"Legacy"},
     *      summary="List all known IANA timezone identifiers",
     *      description="Legacy, unauthenticated endpoint backed by PHP's DateTimeZone::listIdentifiers(ALL_WITH_BC).",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="name", type="string", example="Europe/London")
     *              )
     *          )
     *      )
     * )
     */
    public function timezones(): JsonResponse {
        $zones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC);
        $ret = [];

        foreach ($zones as $zone) {
            $ret[] = [
                'name' => $zone
            ];
        }

        return response()->json($ret);
    }
}
