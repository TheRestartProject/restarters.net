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
                        SUM(pax) as participants,
                        SUM(CASE
                            WHEN cancelled = 1 THEN 3
                            WHEN volunteers > 0 THEN 9 + volunteers * CEIL(TIMESTAMPDIFF(MINUTE, event_start_utc, event_end_utc) / 60)
                            ELSE 21
                        END) as hours_volunteered
                    ")
                    ->first();

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
     * List/search devices.
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
