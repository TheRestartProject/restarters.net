<?php

namespace App\Http\Controllers;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\GroupTags;
use App\GrouptagsGroups;
use App\Helpers\Fixometer;
use App\Helpers\SearchHelper;
use App\Party;
use App\Search;
use App\User;
use App\UserGroups;
use Auth;
use Carbon\Carbon;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Response;
use Illuminate\Database\Eloquent\Collection;

class ExportController extends Controller
{
    public function devicesEvent(Request $request, $idevents = NULL) {
        return $this->devices($request, $idevents);
    }

    public function devicesGroup(Request $request, $idgroups = NULL) {
        return $this->devices($request, NULL, $idgroups);
    }

    public function devices(Request $request, $idevents = NULL, $idgroups = NULL)
    {
        // To not display column if the referring URL is therestartproject.org
        $host = parse_url(\Request::server('HTTP_REFERER'), PHP_URL_HOST);

        $all_devices = Device::with([
            'deviceCategory',
            'deviceEvent',
        ])
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'groups.idgroups', '=', 'events.group')
            ->when($idevents != NULL, function($query) use ($idevents) {
                return $query->where('events.idevents', $idevents);
            })
            ->when($idgroups != NULL, function($query) use ($idgroups) {
                return $query->where('events.group', $idgroups);
            })
            ->select('devices.*', 'groups.name AS group_name')->get();

        $displacementFactor = \App\Device::getDisplacementFactor();
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        // Create CSV
        $filename = 'repair-data';

        if ($idevents != NULL) {
            $event = Party::findOrFail($idevents);
            $eventName = $event->venue ? $event->venue : $event->location;
            $eventName = iconv("UTF-8", "ISO-8859-9//TRANSLIT", $eventName);
            $eventName = str_replace(' ', '-', $eventName);
            $filename .= '-' . $eventName . '-' . (new Carbon($event->event_start_utc))->format('Y-m-d');
        } else if ($idgroups != NULL) {
            $group = Group::findOrFail($idgroups);
            $groupName = iconv("UTF-8", "ISO-8859-9//TRANSLIT", $group->name);
            $groupName = str_replace(' ', '-', $groupName);
            $filename .= '-' . $groupName;
        }

        $filename .= '.csv';
        $file = fopen(base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $filename, 'w+');

        $me = auth()->user();

        // Do not include model column
        if ($host == 'therestartproject.org') {
            $columns = [
                'Item Type',
                'Product Category',
                'Brand',
                'Comments',
                'Repair Status',
                'Spare parts (needed/used)',
                'Event',
                'Group',
                'Date',
                'Waste Prevented',
                'CO2 Prevented',
            ];

            fputcsv($file, $columns);

            foreach ($all_devices as $device) {
                set_time_limit(60);
                if (User::userCanSeeEvent($me, $event)) {
                    $wasteImpact = 0;
                    $co2Diverted = 0;

                    if ($device->isFixed()) {
                        if ($device->deviceCategory->powered) {
                            $wasteImpact = $device->eWasteDiverted();
                            $co2Diverted = $device->eCo2Diverted($eEmissionRatio, $displacementFactor);
                        } else {
                            $wasteImpact = $device->uWasteDiverted();
                            $co2Diverted = $device->uCo2Diverted($uEmissionratio, $displacementFactor);
                        }
                    }

                    fputcsv($file, [
                        $device->item_type,
                        $device->deviceCategory->name,
                        $device->brand,
                        $device->problem,
                        $device->getRepairStatus(),
                        $device->getSpareParts(),
                        $device->deviceEvent->getEventName(),
                        $device->deviceEvent->theGroup->name,
                        $device->deviceEvent->getFormattedLocalStart('Y-m-d'),
                        $wasteImpact,
                        $co2Diverted
                    ]);
                }
            }
        } else {
            $columns = [
                'Item Type',
                'Product Category',
                'Brand',
                'Model',
                'Comments',
                'Repair Status',
                'Spare parts (needed/used)',
                'Event',
                'Group',
                'Date',
                'Waste Prevented',
                'CO2 Prevented',
            ];

            fputcsv($file, $columns);
            $party = null;

            foreach ($all_devices as $device) {
                set_time_limit(60);
                $party = !$party || $party->idevents != $device->event ? Party::findOrFail($device->event) : $party;

                if (User::userCanSeeEvent($me, $party)) {
                    $wasteImpact = 0;
                    $co2Diverted = 0;

                    if ($device->isFixed())
                    {
                        if ($device->deviceCategory->powered)
                        {
                            $wasteImpact = $device->eWasteDiverted();
                            $co2Diverted = $device->eCo2Diverted($eEmissionRatio, $displacementFactor);
                        } else
                        {
                            $wasteImpact = $device->uWasteDiverted();
                            $co2Diverted = $device->uCo2Diverted($uEmissionratio, $displacementFactor);
                        }
                    }

                    fputcsv($file, [
                        $device->item_type,
                        $device->deviceCategory->name,
                        $device->brand,
                        $device->model,
                        $device->problem,
                        $device->getRepairStatus(),
                        $device->getSpareParts(),
                        $device->deviceEvent->getEventName(),
                        $device->deviceEvent->theGroup->name,
                        $device->deviceEvent->getFormattedLocalStart('Y-m-d'),
                        $wasteImpact,
                        $co2Diverted,
                    ]);
                }
            }
        }

        fclose($file);

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return Response::download(base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $filename, $filename, $headers);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function parties(Request $request)
    {
        if ($request->has('fltr') && ! empty($request->input('fltr'))) {
            $dropdowns = SearchHelper::getUserGroupsAndParties();
            $filters = SearchHelper::getSearchFilters($request);

            $Search = new Search;
            $PartyList = $Search->parties(
                $filters['searched_parties'],
                $filters['searched_groups'],
                $filters['from_date'],
                $filters['to_date'],
                $filters['group_tags'],
                $dropdowns['allowed_parties']
            );

            if (count($PartyList) > 0) {

                // prepare the column headers
                $statsKeys = array_keys(\App\Party::getEventStatsArrayKeys());
                array_walk($statsKeys, function (&$k) {
                    $key = explode('_', $k);
                    array_walk($key, function (&$v) {
                        $v = str_replace('Waste', 'Weight', str_replace('Co2', 'CO2', ucfirst($v)));
                    });
                    $k = implode(' ', $key);
                });
                $headers = array_merge(['Date', 'Venue', 'Group', 'Approved'], $statsKeys);

                // Send these to getEventStats() to speed things up a bit.
                $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
                $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

                // prepare the column values
                $PartyArray = [];
                foreach ($PartyList as $i => $party) {
                    $stats = $party->getEventStats($eEmissionRatio, $uEmissionratio);
                    array_walk($stats, function (&$v) {
                        $v = round($v);
                    });

                    $PartyArray[$i] = [
                        $party->getFormattedLocalStart(),
                        $party->getEventName(),
                        $party->theGroup && $party->theGroup->name ? $party->theGroup->name : '?',
                        $party->approved ? 'true' : 'false',
                    ];
                    $PartyArray[$i] += $stats;
                }

                // write content to file
                $filename = 'parties.csv';

                $file = fopen($filename, 'w+');
                fputcsv($file, $headers);

                foreach ($PartyArray as $d) {
                    fputcsv($file, $d);
                }
                fclose($file);

                $headers = [
                    'Content-Type' => 'text/csv',
                ];

                return Response::download($filename, $filename, $headers);
            }
            // }
        }

        return view('export.parties', [
            'data' => ['No data to return'],
        ]);
    }
}
