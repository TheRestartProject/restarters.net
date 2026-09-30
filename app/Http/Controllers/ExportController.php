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

        $me = auth()->user();

        // Only export devices from events this user can see - the same rules as User::userCanSeeEvent(), but
        // applied in the query so that we don't have to look up each event and group in turn.
        $all_devices = Device::with([
            'deviceCategory',
            'deviceEvent.theGroup',
        ])
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'groups.idgroups', '=', 'events.group')
            ->whereNull('events.deleted_at')
            ->when($idevents != NULL, function($query) use ($idevents) {
                return $query->where('events.idevents', $idevents);
            })
            ->when($idgroups != NULL, function($query) use ($idgroups) {
                return $query->where('events.group', $idgroups);
            })
            ->when(!$me || !$me->hasRole('Administrator'), function($query) use ($me) {
                $extraGroups = $this->groupsWithUnapprovedEventsVisibleTo($me);

                return $query->where(function($query) use ($extraGroups) {
                    $query->where(function($query) {
                        $query->where('events.approved', true)
                            ->where('groups.approved', true);
                    })->orWhereIn('events.group', $extraGroups);
                });
            })
            ->select('devices.*', 'groups.name AS group_name');

        $displacementFactor = \App\Device::getDisplacementFactor();
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        // Create CSV
        $filename = 'repair-data';

        if ($idevents != NULL) {
            $event = Party::findOrFail($idevents);
            $eventName = $event->venue ? $event->venue : $event->location;
            $eventName = iconv("UTF-8", "ISO-8859-9//IGNORE", $eventName);
            $eventName = str_replace([' ', '/'],  '-', $eventName);
            $filename .= '-' . $eventName . '-' . (new Carbon($event->event_start_utc))->format('Y-m-d');
        } else if ($idgroups != NULL) {
            $group = Group::findOrFail($idgroups);
            $groupName = iconv("UTF-8", "ISO-8859-9//IGNORE", $group->name);
            $groupName = str_replace([' ', '/'], '-', $groupName);
            $filename .= '-' . $groupName;
        }

        $filename .= '.csv';
        $fullpath = $this->exportPath($filename);
        $file = fopen($fullpath, 'w+');

        // We can't put accented characters into a CSV file, so flatten them.
        // Use //TRANSLIT//IGNORE to handle characters that can't be transliterated on
        // servers with older glibc (e.g. 2.27) and POSIX locale, which lack transliteration
        // tables for certain Unicode characters like emdash (—). Without //IGNORE, iconv
        // throws "Detected an illegal character in input string" on such systems.
        $columns = [
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.item_type_short')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.category')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.brand')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.model')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.title_assessment')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.repair_status')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('devices.spare_parts')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('events.event')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.group')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('events.event_date')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('events.stat-7')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('events.stat-6')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ucfirst(__('devices.title_powered')))
        ];

        fputcsv($file, $columns);

        // Work through the devices in chunks so that the whole dataset isn't held in memory at once.
        foreach ($all_devices->lazyById(1000, 'devices.iddevices', 'iddevices') as $device) {
            set_time_limit(60);

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

            fputcsv($file, $this->csvSafeRow([
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
            ]));
        }

        fclose($file);

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return Response::download($fullpath, $filename, $headers)->deleteFileAfterSend(true);
    }

    /**
     * Groups whose events a user can see even when the event or group isn't approved: those they host, and those in
     * networks they coordinate.
     */
    private function groupsWithUnapprovedEventsVisibleTo($user): array
    {
        if (!$user) {
            return [];
        }

        $groups = [];

        if ($user->hasRole('Host')) {
            $groups = UserGroups::where('user', $user->id)
                ->where('role', \App\Role::HOST)
                ->pluck('group')
                ->all();
        }

        $networks = $user->networks->pluck('id');

        if ($networks->count()) {
            $groups = array_merge($groups, DB::table('group_network')
                ->whereIn('network_id', $networks)
                ->pluck('group_id')
                ->all());
        }

        return array_values(array_unique($groups));
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
        // Use //TRANSLIT//IGNORE to handle characters that can't be transliterated on
        // servers with older glibc (e.g. 2.27) and POSIX locale, which lack transliteration
        // tables for certain Unicode characters like emdash (—). Without //IGNORE, iconv
        // throws "Detected an illegal character in input string" on such systems.
        $headers = [
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.date')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.event')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.volunteers')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.participants')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.items_total')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.items_fixed')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.items_repairable')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.items_end_of_life')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.items_kg_waste_prevented')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.items_kg_co2_prevent')),
            iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', __('groups.export.events.group'))
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
                iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $party->theGroup && $party->theGroup->name ? $party->theGroup->name : '?'),
            ];
        }

        // write content to file
        $filename = 'events.csv';

        $fullpath = $this->exportPath($filename);
        $file = fopen($fullpath, 'w+');
        fputcsv($file, $headers);

        foreach ($PartyArray as $d) {
            fputcsv($file, $this->csvSafeRow($d));
        }
        fclose($file);

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return Response::download($fullpath, $filename, $headers)->deleteFileAfterSend(true);
    }

    /**
     * Somewhere to build an export that is not served by the webserver.  These files were
     * previously written into public/ under a name derived from the group or venue, where
     * they persisted and could be fetched by anyone who guessed the name.
     */
    private function exportPath($filename)
    {
        $dir = storage_path('app' . DIRECTORY_SEPARATOR . 'exports');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Spreadsheets treat a cell starting with =, +, - or @ as a formula, so prefix those
     * with an apostrophe.  Device fields are free text entered at events.
     */
    private function csvSafeRow(array $row)
    {
        return array_map(function ($value) {
            if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                return "'" . $value;
            }

            return $value;
        }, $row);
    }
}
