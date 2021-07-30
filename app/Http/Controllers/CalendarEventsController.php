<?php

namespace App\Http\Controllers;

use App\Group;
use App\GrouptagsGroups;
use App\Party;
use App\User;
use Carbon\Carbon;
use App\Helpers\Fixometer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CalendarEventsController extends Controller
{
    public $ical_format;

    public function __construct()
    {
        $this->ical_format = 'Ymd\THis';
    }

    public function allEventsByUser(Request $request, $calendar_hash)
    {
        if (empty($calendar_hash)) {
            throw new \Exception('No calendar hash provided');
        }

        $user = User::where('calendar_hash', $calendar_hash)->first();
        if (is_null($user)) {
            throw new \Exception('No user calendar found for provided calendar hash');
        }

        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
      ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
      ->join('events_users', 'events_users.event', '=', 'events.idevents')
      ->whereNull('users_groups.deleted_at')
      ->where(function ($query) use ($user) {
          $query->where('events_users.user', $user->id)
        ->orWhere('users_groups.user', $user->id);
      })
      ->select('events.*', 'groups.name')
      ->groupBy('idevents')
      ->orderBy('event_date', 'ASC')
      ->get();

        $this->exportCalendar($events);
    }

    public function allEventsByGroup(Request $request, Group $group)
    {
        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
      ->where(function ($query) use ($group) {
          $query->where('groups.idgroups', $group->idgroups)
              ->whereNull('events.deleted_at');
      })
      ->select('events.*', 'groups.name')
      ->groupBy('events.idevents')
      ->orderBy('events.event_date', 'ASC')
      ->get();

        if (empty($events)) {
            return abort(404, 'No events found.');
        }

        $this->exportCalendar($events);
    }

    public function allEventsByArea(Request $request, $area)
    {
        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
      ->where(function ($query) use ($area) {
          $query->where('groups.area', 'like', '%'.$area.'%');
      })
      ->select('events.*', 'groups.name')
      ->groupBy('events.idevents')
      ->orderBy('events.event_date', 'ASC')
      ->get();

        if (empty($events)) {
            return abort(404, 'No events found.');
        }

        $this->exportCalendar($events);
    }

    public function allEventsByGroupTag(Request $request, GrouptagsGroups $grouptags_groups)
    {
        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
      ->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
      ->where(function ($query) use ($grouptags_groups) {
          $query->where('grouptags_groups.id', $grouptags_groups->id)
              ->whereNull('events.deleted_at');
      })
      ->select('events.*', 'groups.name')
      ->groupBy('events.idevents')
      ->orderBy('events.event_date', 'ASC')
      ->get();

        if (empty($events)) {
            return abort(404, 'No events found.');
        }

        $this->exportCalendar($events);
    }

    public function allEvents(Request $request, $env_hash)
    {
        if ($env_hash != env('CALENDAR_HASH')) {
            return abort(404);
        }

        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
              ->whereNull('deleted_at')
              ->select('events.*', 'groups.name')
              ->get();

        $this->exportCalendar($events);
    }

    public function exportCalendar($events)
    {
        $ical = [];
        $ical[] = 'BEGIN:VCALENDAR';
        $ical[] = 'VERSION:2.0';
        $ical[] = 'PRODID:-//Restarters//NONSGML Events Calendar/EN';

        // loop over events
        foreach ($events as $event) {
            if (! is_null($event->event_date) && $event->event_date != '0000-00-00') {
                $ical[] = 'BEGIN:VEVENT';

                // Timezone currently fixed to Europe/London, but in future when we
                // have better timezone support in the app this will need amending.
                $ical[] = 'TZID:Europe/London';
                $ical[] = "UID:{$event->idevents}";
                $ical[] = 'DTSTAMP:'.date($this->ical_format).'';
                $ical[] = "SUMMARY:{$event->venue} ({$event->name})";
                $ical[] = 'DTSTART;TZID=Europe/London:'.date($this->ical_format, strtotime($event->event_date.' '.$event->start)).'';
                $ical[] = 'DTEND;TZID=Europe/London:'.date($this->ical_format, strtotime($event->event_date.' '.$event->end)).'';
                $ical[] = 'DESCRIPTION:'.url('/party/view').'/'.$event->idevents;
                $ical[] = "LOCATION:{$event->location}";
                $ical[] = 'URL:'.url('/party/view').'/'.$event->idevents;
                $ical[] = 'STATUS:CONFIRMED';
                $ical[] = 'END:VEVENT';
            }
        }

        // close calendar
        $ical[] = 'END:VCALENDAR';

        $ical = implode("\r\n", $ical);

        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');

        echo $ical;
    }

    protected function ical_split($preamble, $value)
    {
        $value = trim($value);
        $value = strip_tags($value);
        $value = preg_replace('/\n+/', ' ', $value);
        $value = preg_replace('/\s{2,}/', ' ', $value);
        $preamble_len = strlen($preamble);
        $lines = [];
        while (strlen($value) > (75 - $preamble_len)) {
            $space = (75 - $preamble_len);
            $mbcc = $space;
            while ($mbcc) {
                $line = mb_substr($value, 0, $mbcc);
                $oct = strlen($line);
                if ($oct > $space) {
                    $mbcc -= $oct - $space;
                } else {
                    $lines[] = $line;
                    $preamble_len = 1; // Still take the tab into account
                    $value = mb_substr($value, $mbcc);
                    break;
                }
            }
        }
        if (! empty($value)) {
            $lines[] = $value;
        }

        return implode($lines, "\n\t");
    }
}
