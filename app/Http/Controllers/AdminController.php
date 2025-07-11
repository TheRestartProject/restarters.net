<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Device;
use App\Party;

/**
 * Embedded at https://therestartproject.org/impact
 */
class AdminController extends Controller
{
    public static function stats($section = 1, $paragraph_only = false): View
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

        $allparties = Party::past()
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

        // statusCount() and findMostSeen() might not return 3 values, depending on what entries are in the DB.  So make sure that we
        // always have values, otherwise the blade fails (for example on Circle, where the DB is empty).
        $statusCount = [];
        $mostSeen = [];

        for ($i = 0; $i < 3; $i++) {
            $statusCount[$i] = [
                'counter' => 0
            ];
            $mostSeen[$i] = [
                'name' => null,
                'counter' => 0
            ];
        }

        $i = 0;

        foreach ($Device->statusCount() as $status) {
            $statusCount[$i++]['counter'] = $status->counter;
        }

        $i = 0;

        foreach ($Device->findMostSeen(1, null, null) as $top) {
            $mostSeen[$i]['name'] = $top->name;
            $mostSeen[$i++]['counter'] = $top->counter;
        }

        return [
            'pax' => $participants,
            'hours' => $hours_volunteered,
            'device_count_status' => $statusCount,
            'top' => $mostSeen,
        ];
    }

    public static function getStats2()
    {
        $stats = \App\Helpers\LcaStats::getWasteStats();
        // preference is for powered only
        return [
            'co2Total' => $stats[0]->powered_footprint, // + $stats[0]->unpowered_footprint,
            'wasteTotal' => $stats[0]->powered_waste, // + $stats[0]->unpowered_waste,
        ];
    }
}
