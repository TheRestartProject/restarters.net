<?php

namespace App\Http\Controllers;

use App\Device;
use App\Group;
use App\Helpers\FootprintRatioCalculator;
use App\Party;
use App\User;
use Auth;
use DB;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public static function homepage_data()
    {
        $result = [];

        $Device = new Device;

        $allparties = Party::pastEvents()->get();

        $participants = 0;
        $hours_volunteered = 0;

        foreach ($allparties as $party) {
            $participants += $party->pax;

            $hours_volunteered += $party->hoursVolunteered();
        }

        $co2Total = $Device->getWeights();

        $result['participants'] = $participants;
        $result['hours_volunteered'] = $hours_volunteered;
        $fixed = $Device->statusCount();
        $result['items_fixed'] = count($fixed) ? $fixed[0]->counter : 0;
        $result['weights'] = round($co2Total[0]->total_weights);
        $result['ewaste'] = round($co2Total[0]->ewaste);
        $result['unpowered_waste'] = round($co2Total[0]->unpowered_waste);
        $result['emissions'] = round($co2Total[0]->total_footprints);

        $devices = new Device;
        $result['fixed_powered'] = $devices->fixedPoweredCount();
        $result['fixed_unpowered'] = $devices->fixedUnpoweredCount();
        $result['total_powered'] = $devices->poweredCount();
        $result['total_unpowered'] = $devices->unpoweredCount();

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

        $eventStats = $event->getEventStats();

        return response()
            ->json(
                [
                'kg_co2_diverted' => round($eventStats['co2']),
                'kg_waste_diverted' => round($eventStats['ewaste']),
                'num_fixed_devices' => $eventStats['fixed_devices'],
                'num_repairable_devices' => $eventStats['repairable_devices'],
                'num_dead_devices' => $eventStats['dead_devices'],
                'num_participants' => $eventStats['participants'],
                'num_volunteers' => $eventStats['volunteers'],
                ],
                200
            );
    }

    public static function groupStats($groupId)
    {
        $group = Group::where('idgroups', $groupId)->first();
        $groupStats = $group->getGroupStats();

        return response()
            ->json([
                'num_participants' => $groupStats['pax'],
                'num_hours_volunteered' => $groupStats['hours'],
                'num_parties' => $groupStats['parties'],
                'kg_co2_diverted' => round($groupStats['co2']),
                'kg_waste_diverted' => round($groupStats['waste']),
            ], 200);
    }

    public static function getEventsByGroupTag($group_tag_id)
    {
        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
                ->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
                  ->where('grouptags_groups.group_tag', $group_tag_id)
                    ->select('events.*', 'groups.area')
                      ->get();

        return response()->json($events, 200);
    }

    public static function getUserInfo()
    {
        $user = Auth::user();

        $user->makeHidden('api_token');

        return response()->json($user->toArray());
    }

    public static function getUserList()
    {
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
            $wheres[] = ['events.event_date', '>=', $from_date];
        }

        if ($to_date) {
            $wheres[] = ['events.event_date', '<=', $to_date];
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

        if ($status && $status !== env('DEVICE_FIXED')) {
            // We only count savings from fixed items.  So if we are filtering on repair status other than fixed, then
            // there can be no savings to return, so don't bother querying.
            $weight = 0;
            $co2 = 0;
        } else {
            // We need the total weight/CO2 impact for this filtering.
            $d = new Device();

            DB::enableQueryLog();

            $wheres[] = ['repair_status', '=', env('DEVICE_FIXED')];

            // We select the powered and unpowered weights separately and then add them afterwards just because
            // this keeps the logic separate and is easier to compare with other code.
            $counts = Device::select(
                DB::raw(
                    'sum(case when (categories.powered = 1) then (case when (devices.category = 46) then (devices.estimate + 0.0) else categories.weight end) else 0 end) as ewaste'
                ),
                DB::raw(
                    'sum(case when (categories.powered = 0) then devices.estimate + 0.0 else 0 end) as unpowered_waste'
                ),
                DB::raw(
                    "sum(case when (devices.category = 46) then (devices.estimate + 0.0) *
                     (select (sum(`categories`.`footprint`) * {$d->getDisplacementFactor()}) / sum(`categories`.`weight` + 0.0) from `devices`, `categories` where `categories`.`idcategories` = `devices`.`category` and `devices`.`repair_status` = 1 and categories.idcategories != 46)
                     else (categories.footprint * {$d->getDisplacementFactor()}) end) as `total_footprints`"
                )
            )->join('events', 'events.idevents', '=', 'devices.event')
                ->join('groups', 'events.group', '=', 'groups.idgroups')
                ->join('categories', 'devices.category', '=', 'categories.idcategories')
                ->where($wheres)
                ->first();

            $weight = round($counts->ewaste + $counts->unpowered_waste);
            $co2 = round($counts->total_footprints);
        }

        return response()->json([
            'count' => $count,
            'weight' => $weight,
            'co2' => $co2,
            'items' => $items,
        ]);
    }
}
