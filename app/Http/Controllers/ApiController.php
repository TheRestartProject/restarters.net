<?php

namespace App\Http\Controllers;

use App\Group;
use App\Party;
use App\Device;
use App\Helpers\FootprintRatioCalculator;

class ApiController extends Controller
{
    public static function homepage_data()
    {
        $result = array();

        $Party = new Party;
        $Device = new Device;

        $allparties = Party::pastEvents()->get();

        $participants = 0;
        $hours_volunteered = 0;

        foreach ($allparties as $i => $party) {
            $participants += $party->pax;

            $hours_volunteered += $party->hoursVolunteered();
        }

        $co2Total = $Device->getWeights();

        $result['hours_volunteered'] = $hours_volunteered;
        $result['items_fixed'] = $Device->statusCount()[0]->counter;
        $result['weights'] = $co2Total[0]->total_weights;

        return response()
            ->json($result, 200);
    }

    public static function partyStats($partyId)
    {
        $emissionRatio = ApiController::getEmissionRatio();

        $event = Party::where('idevents', $partyId)->first();

        $eventStats = $event->getEventStats($emissionRatio);

        return response()
            ->json([
                'kg_co2_diverted' => $eventStats['co2'],
                'kg_waste_diverted' => $eventStats['ewaste'],
                'num_fixed_devices' => $eventStats['fixed_devices'],
                'num_repairable_devices' => $eventStats['repairable_devices'],
                'num_dead_devices' => $eventStats['dead_devices'],
                'num_participants' => $eventStats['participants'],
                'num_volunteers' => $eventStats['volunteers'],
            ]
            , 200);
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
                'kg_co2_diverted' => $groupStats['co2'],
                'kg_waste_diverted' => $groupStats['waste'],
            ], 200);
    }

    public static function getEmissionRatio()
    {
        $footprintRatioCalculator = new FootprintRatioCalculator();

        return $footprintRatioCalculator->calculateRatio();
    }
}
