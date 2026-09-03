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
            $eventName = str_replace([' ', '/'],  '-', $eventName);
            $filename .= '-' . $eventName . '-' . (new Carbon($event->event_start_utc))->format('Y-m-d');
        } else if ($idgroups != NULL) {
            $group = Group::findOrFail($idgroups);
            $groupName = iconv("UTF-8", "ISO-8859-9//IGNORE", $group->name);
            $groupName = str_replace([' ', '/'], '-', $groupName);
            $filename .= '-' . $groupName;
        }

        $filename .= '.csv';

        // Built in a temp file, not public/. The rows below are filtered by
        // userCanSeeEvent for THIS caller, so the finished CSV holds whatever
        // that caller was allowed to see - writing it under the docroot
        // published it at a guessable URL (the name derives from the group or
        // event name) and left it there for anyone to fetch, since
        // Response::download does not remove the file. $filename stays as the
        // download's presented name.
        $path = tempnam(sys_get_temp_dir(), 'repair-data');
        $file = fopen($path, 'w+');

        $me = auth()->user();

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

        return Response::download($path, $filename, $headers)->deleteFileAfterSend(true);
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

    /**
     * Drop events the caller isn't allowed to see.
     *
     * undeleted() excludes deleted events, not unapproved ones, so without
     * this an anonymous request returned events belonging to groups still
     * awaiting moderation - data /api/v2 withholds from the same caller
     * (User::userCanSeeEvent, asserted by APIv2EventVisibilityTest). The
     * device export has always filtered this way; the event export did not.
     */
    private function visibleTo($parties)
    {
        $me = auth()->user();

        return $parties->filter(fn ($party) => User::userCanSeeEvent($me, $party));
    }

    private function exportEvents($parties) {
        $parties = $this->visibleTo($parties);

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

        // Per-request temp file. This was a fixed, relative path, so every
        // caller wrote to the same events.csv in the process working
        // directory - two concurrent exports for different groups raced, and
        // one caller could be handed the other's rows.
        $path = tempnam(sys_get_temp_dir(), 'events');
        $file = fopen($path, 'w+');
        fputcsv($file, $headers);

        foreach ($PartyArray as $d) {
            fputcsv($file, $d);
        }
        fclose($file);

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return Response::download($path, $filename, $headers)->deleteFileAfterSend(true);
    }
}
