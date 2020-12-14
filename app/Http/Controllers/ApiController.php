<?php

namespace App\Http\Controllers;

use Auth;
use App\Group;
use App\Party;
use App\Device;
use App\User;
use App\Helpers\FootprintRatioCalculator;
use Illuminate\Http\Request;
use Mediawiki\Api\CategoryLoopException;

class ApiController extends Controller
{
    public static function homepage_data()
    {
        $result = array();

        $Device = new Device;

        $allparties = Party::pastEvents()->get();

        $participants = 0;
        $hours_volunteered = 0;

        foreach ($allparties as $i => $party) {
            $participants += $party->pax;

            $hours_volunteered += $party->hoursVolunteered();
        }

        $co2Total = $Device->getWeights();

        $result['participants'] = $participants;
        $result['hours_volunteered'] = $hours_volunteered;
        $result['items_fixed'] = $Device->statusCount()[0]->counter;
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
        $emissionRatio = ApiController::getEmissionRatio();

        $event = Party::where('idevents', $partyId)->first();

        $eventStats = $event->getEventStats($emissionRatio);

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
        $emissionRatio = ApiController::getEmissionRatio();

        $group = Group::where('idgroups', $groupId)->first();
        $groupStats = $group->getGroupStats($emissionRatio);

        return response()
            ->json([
                'num_participants' => $groupStats['pax'],
                'num_hours_volunteered' => $groupStats['hours'],
                'num_parties' => $groupStats['parties'],
                'kg_co2_diverted' => round($groupStats['co2']),
                'kg_waste_diverted' => round($groupStats['waste']),
            ], 200);
    }

    public static function getEmissionRatio()
    {
        $footprintRatioCalculator = new FootprintRatioCalculator();

        return $footprintRatioCalculator->calculateRatio();
    }

    public static function getEventsByGroupTag($group_tag_id)
    {

        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
                ->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
                  ->where('grouptags_groups.group_tag', $group_tag_id)
                    ->select('events.*')
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
     * List/search devices
     *
     * @param  Request  $request
     * @return Response
     */
    public static function getDevices(Request $request, $page, $size) {
        $powered = $request->input('powered');
        $sortBy = $request->input('sortBy');
        $sortDesc = $request->input('sortDesc');
        $category = $request->input('category');
        $brand = $request->input('brand');
        $model = $request->input('model');
        $item_type = $request->input('item_type');
        $status = $request->input('status');
        $comments = $request->input('comments');

        $wheres = [
            ['categories.powered', '=', $powered == 'true' ? 1 : 0],
        ];

        if ($category) {
            $wheres[] = [ 'idcategories', '=' , $category ];
        }

        if ($brand) {
            $wheres[] = [ 'devices.brand', 'LIKE', '%' . $brand . '%'];
        }

        if ($model) {
            $wheres[] = [ 'devices.model', 'LIKE', '%' . $model . '%'];
        }

        if ($item_type) {
            $wheres[] = [ 'devices.item_type', 'LIKE', '%' . $item_type . '%'];
        }

        if ($comments) {
            $wheres[] = [ 'devices.problem', 'LIKE', '%' . $comments . '%'];
        }

        if ($status) {
            $wheres[] = [ 'repair_status', '=', $status ];
        }

        $query = Device::with(['deviceEvent.theGroup', 'deviceCategory', 'barriers'])
        ->join('events', 'events.idevents', '=', 'devices.event')
        ->join('groups', 'events.group', '=', 'groups.idgroups')
        ->join('categories', 'devices.category', '=', 'categories.idcategories')
        ->where($wheres)
        ->orderBy($sortBy, $sortDesc);

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
            'items' => $items
        ]);
    }
}
