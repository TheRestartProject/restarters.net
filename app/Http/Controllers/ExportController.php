<?php

namespace App\Http\Controllers;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\GroupTags;
use App\GrouptagsGroups;
use App\Helpers\Fixometer;
use App\Helpers\SearchHelper;
use App\Network;
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
            $eventName = iconv("UTF-8", "ISO-8859-9//IGNORE", $eventName);
            $eventName = str_replace(' ', '-', $eventName);
            $filename .= '-' . $eventName . '-' . (new Carbon($event->event_start_utc))->format('Y-m-d');
        } else if ($idgroups != NULL) {
            $group = Group::findOrFail($idgroups);
            $groupName = iconv("UTF-8", "ISO-8859-9//IGNORE", $group->name);
            $groupName = str_replace(' ', '-', $groupName);
            $filename .= '-' . $groupName;
        }

        $filename .= '.csv';
        $file = fopen(base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $filename, 'w+');

        $me = auth()->user();

        // We can't put accented characters into a CSV file, so flatten them.
        $columns = [
            iconv('UTF-8', 'ASCII//TRANSLIT', __('devices.item_type_short')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('devices.category')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('devices.brand')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('devices.model')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('devices.title_assessment')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('devices.repair_status')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('devices.spare_parts')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('events.event')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.group')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('events.event_date')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('events.stat-7')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('events.stat-6')),
            iconv('UTF-8', 'ASCII//TRANSLIT', ucfirst(__('devices.title_powered')))
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
                    $device->deviceCategory->powered ? 'Powered' : 'Unpowered'
                ]);
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
    public function groupEvents(Request $request, $idgroups)
    {
        $group = Group::findOrFail($idgroups);
        $parties = $group->parties()->undeleted()->get();
        return $this->exportEvents($parties);
    }

    public function networkEvents(Request $request, $id)
    {
        $network = Network::findOrFail($id);
        $parties = collect([]);

        foreach ($network->groups as $group) {
            $parties = $parties->merge($group->parties()->undeleted()->get());
        }

        return $this->exportEvents($parties);
    }

    private function exportEvents($parties) {
        // We can't put accented characters into a CSV file, so flatten them.
        $headers = [
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.date')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.event')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.volunteers')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.participants')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.items_total')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.items_fixed')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.items_repairable')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.items_end_of_life')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.items_kg_waste_prevented')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.items_kg_co2_prevent')),
            iconv('UTF-8', 'ASCII//TRANSLIT', __('groups.export.events.group'))
        ];

        // Send these to getEventStats() to speed things up a bit.
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        // prepare the column values
        $PartyArray = [];
        foreach ($parties as $party) {
            $stats = $party->getEventStats($eEmissionRatio, $uEmissionratio);
            array_walk($stats, function (&$v) {
                $v = round($v);
            });

            $PartyArray[] = [
                $party->getFormattedLocalStart(),
                $party->getEventName(),
                $party->volunteers,
                $party->participants ? $party->participants : 0,
                $stats['fixed_devices'] + $stats ['repairable_devices'] + $stats['dead_devices'],
                $stats['fixed_devices'],
                $stats['repairable_devices'],
                $stats['dead_devices'],
                $stats['waste_powered'] + $stats['waste_unpowered'],
                $stats['co2_powered'] + $stats['co2_unpowered'],
                iconv('UTF-8', 'ASCII//TRANSLIT', $party->theGroup && $party->theGroup->name ? $party->theGroup->name : '?'),
            ];
        }

        // write content to file
        $filename = 'events.csv';

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
}
