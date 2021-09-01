<?php

namespace App\Http\Controllers;

use App\Device;
use App\Party;

class AdminController extends Controller
{
    public static function stats($section = 1, $paragraph_only = false)
    {
        if ($section == 1) {
            $stats = self::getStats1();
        } elseif ($section == 2) {
            $stats = self::getStats2();
        }
        $stats['section'] = $section;
        $stats['paragraph_only'] = $paragraph_only;

        return view('admin.stats', $stats);
    }

    public static function getStats1()
    {
        $Device = new Device;

        $allparties = Party::pastEvents()
            ->with('devices.deviceCategory')
            ->get();

        $participants = 0;
        $hours_volunteered = 0;

        foreach ($allparties as $party) {
            $party->fixed_devices = 0;
            $party->repairable_devices = 0;
            $party->dead_devices = 0;

            $participants += $party->pax;
            $hours_volunteered += $party->hoursVolunteered();

            foreach ($party->devices as $device) {
                switch ($device->repair_status) {
                    case 1:
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
            }
        }

        return [
            'pax' => $participants,
            'hours' => $hours_volunteered,
            'device_count_status' => $Device->statusCount(),
            'top' => $Device->findMostSeen(1, null, null),
        ];
    }

    public static function getStats2()
    {
        $Device = new Device;
        $co2Total = $Device->getWeights();

        return [
            'co2Total' => $co2Total[0]->total_footprints,
            'wasteTotal' => $co2Total[0]->total_weights,
        ];
    }
}
