<?php

namespace App\Http\Controllers;

use App\Device;
use App\Group;
use App\Helpers\FootprintRatioCalculator;
use App\Party;
use App\User;
use Auth;
use App\Helpers\Fixometer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public $TotalWeight;
    public $TotalEmission;
    public $EmissionRatio;

    public function __construct()
    {
        $Device = new Device;
        $weights = $Device->getWeights();

        $this->TotalWeight = $weights[0]->total_weights; //send to view
        $this->TotalEmission = $weights[0]->total_footprints; //send to view

        if ($this->TotalWeight != 0) {//send to view
            $this->EmissionRatio = $this->TotalEmission / $this->TotalWeight;
        } else {
            $this->EmissionRatio = $this->TotalEmission;
        }
    }

    public static function stats($section = 1, $paragraph_only = false)
    {
        //Object Instances
        $Group = new Group;
        $Party = new Party;
        $Device = new Device;

        $allparties = Party::pastEvents()
                  ->with('devices.deviceCategory')
                  ->get();

        $participants = 0;
        $hours_volunteered = 0;

        $weights = $Device->getWeights();
        $TotalWeight = $weights[0]->total_weights;
        $TotalEmission = $weights[0]->total_footprints;

        $footprintRatioCalculator = new FootprintRatioCalculator();
        $EmissionRatio = $footprintRatioCalculator->calculateRatio();

        $need_attention = 0;
        foreach ($allparties as $i => $party) {
            if ($party->device_count == 0) {
                $need_attention++;
            }

            $party->co2 = 0;
            $party->fixed_devices = 0;
            $party->repairable_devices = 0;
            $party->dead_devices = 0;
            $party->guesstimates = false;

            $participants += $party->pax;
            $hours_volunteered += $party->hoursVolunteered();

            foreach ($party->devices as $device) {
                switch ($device->repair_status) {
                    case 1:
                        $party->co2 += $device->co2Diverted($EmissionRatio, $Device->displacement);
                        $party->fixed_devices++;
                        break;
                    case 2:
                        $party->repairable_devices++;
                        break;
                    case 3:
                        $party->dead_devices++;
                        break;
                    default:
                        break;
                }
                if ($device->category == 46) {
                    $party->guesstimates = true;
                }
            }

            $party->co2 = number_format(round($party->co2), 0);
        }

        $devices = $Device->ofAllGroups();

        // more stats...

        /** co2 counters **/
        $co2_years = $Device->countCO2ByYear();
        $stats = [];
        foreach ($co2_years as $year) {
            $stats[$year->year] = $year->co2;
        }

        $waste_years = $Device->countWasteByYear();
        $wstats = [];
        foreach ($waste_years as $year) {
            $wstats[$year->year] = $year->waste;
        }

        $co2Total = $Device->getWeights();
        $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));

        $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));

        $clusters = [];

        for ($i = 1; $i <= 4; $i++) {
            $cluster = $Device->countByCluster($i);
            $total = 0;
            foreach ($cluster as $state) {
                $total += $state->counter;
            }
            $cluster['total'] = $total;
            $clusters['all'][$i] = $cluster;
        }

        for ($y = date('Y', time()); $y >= 2013; $y--) {
            for ($i = 1; $i <= 4; $i++) {
                $cluster = $Device->countByCluster($i, null, $y);

                $total = 0;
                foreach ($cluster as $state) {
                    $total += $state->counter;
                }
                $cluster['total'] = $total;
                $clusters[$y][$i] = $cluster;
            }
        }

        // most/least stats for clusters
        $mostleast = [];
        for ($i = 1; $i <= 4; $i++) {
            $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i);
            $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i);
            $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i);
        }

        $user = User::find(Auth::id());

        return view('admin.stats', [
        'section' => $section,
        'paragraph_only' => $paragraph_only,
        'grouplist' => $Group->findList(),
        'pax' => $participants,
        'hours' => $hours_volunteered,
        'showbadges' => $Device->guesstimates(),
        'need_attention' => $need_attention,
        'user' => $user,
        // 'profile' => $user->getProfile($user->id),
        'upcomingparties' => $Party->findNextParties(),
        'allparties' => $allparties,
        'devices' => $devices,
        'weights' => [0 => ['total_footprints' => $TotalEmission, 'total_weights' => $TotalWeight]],
        'device_count_status' => $Device->statusCount(),
        'year_data' => $co2_years,
        'bar_chart_stats' => array_reverse($stats, true),
        'waste_year_data' => $waste_years,
        'waste_bar_chart_stats' => array_reverse($wstats, true),
        'co2Total' => $co2Total[0]->total_footprints,
        'co2ThisYear' => $co2ThisYear[0]->co2,
        'wasteTotal' => $co2Total[0]->total_weights,
        'wasteThisYear' => $wasteThisYear[0]->waste,
        'clusters' => $clusters,
        'mostleast' => $mostleast,
        'top' => $Device->findMostSeen(1, null, null),
        ]);
    }
}
