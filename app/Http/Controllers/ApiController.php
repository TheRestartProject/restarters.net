<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Device;
use App\Group;
use App\Party;
use App\User;
use Carbon\Carbon;
use Auth;
use DB;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="2.0.0",
 *      title="Restarters API",
 *      description="An API for accessing Restarters data.  No API authorisation is necessary - all data is read-only and public.",
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
 *   securityScheme="ApiKeyAuth",
 *   type="apiKey",
 *   in="query",
 *   name="api_token",
 *  )
 */
class ApiController extends Controller
{
    /**
     * Embedded at https://therestartproject.org
     */
    public static function homepage_data(): JsonResponse
    {
        $result = [];

        $lock = \Cache::lock('homepage_data_v3_lock', 60);

        if (\Cache::has('homepage_data_v3')) {
            $result = \Cache::get('homepage_data_v3');
        } elseif ($lock->get()) {
            try {
                $Device = new Device;

                // Aggregate participants and hours in SQL — avoids loading 18k+ event rows into PHP.
                // hoursVolunteered() formula: cancelled→3, volunteers>0→9+volunteers*ceil(minutes/60), else→21
                // Events held leaves out cancelled events and those of groups that aren't approved.
                $eventStats = DB::table('events')
                    ->leftJoin('groups', 'groups.idgroups', '=', 'events.group')
                    ->whereNull('events.deleted_at')
                    ->where('events.event_end_utc', '<', now())
                    ->selectRaw("
                        SUM(CASE WHEN events.cancelled = 0 AND groups.approved = 1 THEN 1 ELSE 0 END) as events,
                        SUM(events.pax) as participants,
                        SUM(CASE
                            WHEN events.cancelled = 1 THEN 3
                            WHEN events.volunteers > 0 THEN 9 + events.volunteers * CEIL(TIMESTAMPDIFF(MINUTE, events.event_start_utc, events.event_end_utc) / 60)
                            ELSE 21
                        END) as hours_volunteered
                    ")
                    ->first();

                $result['participants'] = (int) ($eventStats->participants ?? 0);
                $result['hours_volunteered'] = (int) ($eventStats->hours_volunteered ?? 0);
                $result['events'] = (int) ($eventStats->events ?? 0);

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
                $result['total_items'] = $result['total_powered'] + $result['total_unpowered'];

                // for backward compatibility (don't break therestartproject.org)
                $result['weights'] = round($result['waste_total']);
                $result['ewaste'] = round($result['waste_powered']);
                $result['unpowered_waste'] = round($result['waste_unpowered']);
                $result['emissions'] = round($result['co2_total']);

                \Cache::put('homepage_data_v3', $result, 43200);
            } finally {
                $lock->release();
            }
        } else {
            // Another worker is rebuilding — return stale or empty rather than pile on
            $result = \Cache::get('homepage_data_v3', []);
        }

        return response()
            ->json($result, 200);
    }

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

    public static function getUserInfo(): JsonResponse
    {
        $user = Auth::user();

        // api_token and other credentials are in User::$hidden; makeHidden() is
        // a belt-and-suspenders guard for any future $hidden regression.
        $user->makeHidden(['api_token', 'calendar_hash', 'recovery', 'recovery_expires', 'mediawiki', 'latitude', 'longitude']);

        return response()->json($user->toArray());
    }

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
     * The where clauses for the device list's search filters.
     */
    private static function deviceFilters(Request $request): array
    {
        $wheres = [];

        // No powered filter means both powered and unpowered items.
        $powered = $request->input('powered');

        if ($powered === 'true' || $powered === 'false') {
            $wheres[] = ['categories.powered', '=', $powered === 'true' ? 1 : 0];
        }

        if ($request->input('category')) {
            $wheres[] = ['idcategories', '=', $request->input('category')];
        }

        // Free text searches.
        $likes = [
            'brand' => 'devices.brand',
            'model' => 'devices.model',
            'item_type' => 'devices.item_type',
            'comments' => 'devices.problem',
            'group' => 'groups.name',
        ];

        foreach ($likes as $param => $column) {
            if ($request->input($param)) {
                $wheres[] = [$column, 'LIKE', '%'.$request->input($param).'%'];
            }
        }

        if (filter_var($request->input('wiki', false), FILTER_VALIDATE_BOOLEAN)) {
            $wheres[] = ['devices.wiki', '=', 1];
        }

        $status = $request->input('status');

        if ($status) {
            // The client uses the status strings from the rest of the API; accept the underlying numbers too.
            $statuses = [
                Device::REPAIR_STATUS_FIXED_STR => Device::REPAIR_STATUS_FIXED,
                Device::REPAIR_STATUS_REPAIRABLE_STR => Device::REPAIR_STATUS_REPAIRABLE,
                Device::REPAIR_STATUS_ENDOFLIFE_STR => Device::REPAIR_STATUS_ENDOFLIFE,
            ];

            $wheres[] = ['repair_status', '=', $statuses[$status] ?? intval($status)];
        }

        if ($request->input('from_date')) {
            $wheres[] = ['events.event_start_utc', '>=', Carbon::parse($request->input('from_date'))->startOfDay()];
        }

        if ($request->input('to_date')) {
            // The date is inclusive - events on that day count.
            $wheres[] = ['events.event_start_utc', '<', Carbon::parse($request->input('to_date'))->startOfDay()->addDay()];
        }

        return $wheres;
    }

    /**
     * List/search devices.
     */
    public static function getDevices(Request $request, $page, $size): JsonResponse
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        // The client asks to sort by one of its table columns; map those onto database columns.  Anything else
        // gets the default of most recent event first.
        $sortColumns = [
            'item_type' => 'devices.item_type',
            'category' => 'categories.name',
            'device_category.name' => 'categories.name',
            'brand' => 'devices.brand',
            'groupname' => 'groups.name',
            'repair_status' => 'devices.repair_status',
            'event_date' => 'events.event_start_utc',
            'created_at' => 'devices.created_at',
            'iddevices' => 'devices.iddevices',
        ];

        $sortBy = $sortColumns[$request->input('sortBy')] ?? 'events.event_start_utc';
        $sortDesc = strtolower($request->input('sortDesc', 'desc')) === 'asc' ? 'asc' : 'desc';

        $wheres = self::deviceFilters($request);

        // Get the items we want for this page.  Select only device columns - the joined tables share column names
        // such as created_at, which would otherwise overwrite the device's own.
        $query = Device::with(['deviceEvent.theGroup', 'deviceCategory', 'barriers'])
            ->select('devices.*')
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'events.group', '=', 'groups.idgroups')
            ->join('categories', 'devices.category', '=', 'categories.idcategories')
            ->whereNull('events.deleted_at')
            ->where($wheres)
            ->orderBy($sortBy, $sortDesc)
            ->orderBy('devices.iddevices', $sortDesc);

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
