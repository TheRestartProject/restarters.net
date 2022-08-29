<?php

namespace App\Http\Controllers;

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
 *      description="An API for accessing Restarters data.  TODO Consider license, check contact address.  No API authorisation is necessary - all data is read-only and public.",
 *      @OA\Contact(
 *          email="tech@therestartproject.org"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST_TEST,
 *      description="Test API Server"
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST_LIVE,
 *      description="Live API Server"
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
    public static function homepage_data()
    {
        $result = [];

        $Device = new Device;

        $allparties = Party::past()->get();

        $participants = 0;
        $hours_volunteered = 0;

        foreach ($allparties as $party) {
            $participants += $party->pax;

            $hours_volunteered += $party->hoursVolunteered();
        }

        $result['participants'] = $participants;
        $result['hours_volunteered'] = $hours_volunteered;
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

        return response()
            ->json($result, 200);
    }

    public static function partyStats($partyId)
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

    public static function groupStats($groupId)
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

    public static function getUserInfo()
    {
        $user = Auth::user();

        $user->makeHidden('api_token');

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
     *
     * @param  Request  $request
     * @return Response
     */
    public static function getDevices(Request $request, $page, $size)
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

        foreach ($items as &$item) {
            $item['shortProblem'] = $item->getShortProblem();
            $item['images'] = $item->getImages();
            $item['category'] = $item['deviceCategory'];
        }

        return response()->json([
            'count' => $count,
            'items' => $items,
        ]);
    }

    public function timezones() {
        return response()->json(\DB::select("SELECT name FROM mysql.time_zone_name WHERE name NOT LIKE 'posix%' AND name NOT LIKE 'right%';"));
    }
}
