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

        $export = app(\App\Services\RepairDataExport::class);
        $all_devices = $export->query($me, $idevents, $idgroups);

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

        $export->write($all_devices, $file);

        fclose($file);

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return Response::download($fullpath, $filename, $headers)->deleteFileAfterSend(true);
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
            fputcsv($file, \App\Services\RepairDataExport::csvSafeRow($d));
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
}
