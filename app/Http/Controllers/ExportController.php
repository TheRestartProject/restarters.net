<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
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

#[Feature('Platform', description: 'Platform-wide statistics and public impact data')]
class ExportController extends Controller
{
    #[UserStory('As a Guest, I can export device data from an event as CSV', persona: 'Guest', theme: 'Data exports')]
    #[UserStory('As a ThirdParty, I can export device data from an event as CSV, for example for the download-dataset page on therestartproject.org', persona: 'ThirdParty', theme: 'Data exports')]
    public function devicesEvent(Request $request, $idevents = NULL) {
        return $this->devices($request, $idevents);
    }

    #[UserStory('As a Guest, I can export device data from a group as CSV', persona: 'Guest', theme: 'Data exports')]
    #[UserStory('As a ThirdParty, I can export device data from a group as CSV, for example for the download-dataset page on therestartproject.org', persona: 'ThirdParty', theme: 'Data exports')]
    public function devicesGroup(Request $request, $idgroups = NULL) {
        return $this->devices($request, NULL, $idgroups);
    }

    #[UserStory('As a Guest, I can export all device records as CSV', persona: 'Guest', theme: 'Data exports')]
    #[UserStory('As a ThirdParty, I can export all device records as CSV, for example for the download-dataset page on therestartproject.org', persona: 'ThirdParty', theme: 'Data exports')]
    public function devices(Request $request, $idevents = NULL, $idgroups = NULL)
    {
        // To not display column if the referring URL is therestartproject.org
        $host = parse_url(\Request::server('HTTP_REFERER'), PHP_URL_HOST);

        $me = auth()->user();

        $export = app(\App\Services\RepairDataExport::class);

        // The Fixometer page can ask for just the records matching its filters.  With none, this is the full export.
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $filters = ApiController::deviceFilters($request);
        $all_devices = $export->query($me, $idevents, $idgroups, $filters);

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

        if (count($filters)) {
            $filename .= '-filtered';
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

        $export->write($all_devices, $file);

        fclose($file);

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return Response::download($path, $filename, $headers)->deleteFileAfterSend(true);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    #[UserStory('As a Guest, I can export a group\'s event summary as CSV', persona: 'Guest', theme: 'Data exports')]
    #[UserStory('As a ThirdParty, I can export a group\'s event summary as CSV, for example for the download-dataset page on therestartproject.org', persona: 'ThirdParty', theme: 'Data exports')]
    public function groupEvents(Request $request, $idgroups)
    {
        $group = Group::findOrFail($idgroups);
        $parties = $group->parties()->undeleted()->get();
        return $this->exportEvents($parties);
    }

    #[UserStory('As a Guest, I can export a network\'s event summary as CSV', persona: 'Guest', theme: 'Data exports')]
    #[UserStory('As a ThirdParty, I can export a network\'s event summary as CSV, for example for the download-dataset page on therestartproject.org', persona: 'ThirdParty', theme: 'Data exports')]
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
            fputcsv($file, \App\Services\RepairDataExport::csvSafeRow($d));
        }
        fclose($file);

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return Response::download($path, $filename, $headers)->deleteFileAfterSend(true);
    }
}
