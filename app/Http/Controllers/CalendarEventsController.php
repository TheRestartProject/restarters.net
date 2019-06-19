<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Party;
use App\Group;
use App\GrouptagsGroups;
use Carbon\Carbon;
use FixometerHelper;

class CalendarEventsController extends Controller
{
    public $ical_format;

    public function __construct()
    {
      $this->middleware('auth')->except('allEvents');
      $this->ical_format = 'Ymd\THis\Z';
    }

    public function allEventsByUser(Request $request, $calendar_hash)
    {
      if ( empty(auth()->user()->calendar_hash)) {
        return redirect()->back();
      }

      $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
      ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
      ->join('events_users', 'events_users.event', '=', 'events.idevents')
      ->whereNull('deleted_at')
      ->where(function ($query) {
        $query->where('events_users.user', auth()->id())
        ->orWhere('users_groups.user', auth()->id());
      })
      ->select('events.*')
      ->groupBy('idevents')
      ->orderBy('event_date', 'ASC')
      ->get();

      $icalObject = "BEGIN:VCALENDAR
      VERSION:2.0
      METHOD:PUBLISH\n";

      // loop over events
      foreach ($events as $event) {
        $icalObject .=
        "BEGIN:VEVENT
        SUMMARY:{$event->venue}
        DTSTART:".date($this->ical_format, strtotime($event->event_date.' '.$event->start))."
        DTEND:".date($this->ical_format, strtotime($event->event_date.' '.$event->end))."
        LOCATION:{$event->location}
        STATUS:CONFIRMED
        END:VEVENT\n";
      }

      // close calendar
      $icalObject .= "END:VCALENDAR";

      $icalObject = str_replace(' ', '', $icalObject);

      header('Content-type: text/calendar; charset=utf-8');
      header('Content-Disposition: attachment; filename="cal.ics"');

      echo $icalObject;
    }

    public function allEventsByGroup(Request $request, Group $group)
    {
      $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
      ->where(function ($query) use ($group) {
        $query->where('groups.idgroups', $group->idgroups)
              ->whereNull('events.deleted_at');
      })
      ->select('events.*')
      ->groupBy('events.idevents')
      ->orderBy('events.event_date', 'ASC')
      ->get();

      if ( empty($events)) {
        return abort(404, 'No events found.');
      }

      $icalObject = "BEGIN:VCALENDAR
      VERSION:2.0
      METHOD:PUBLISH\n";

      // loop over events
      foreach ($events as $event) {
        $icalObject .=
        "BEGIN:VEVENT
        SUMMARY:{$event->venue}
        DTSTART:".date($this->ical_format, strtotime($event->event_date.' '.$event->start))."
        DTEND:".date($this->ical_format, strtotime($event->event_date.' '.$event->end))."
        LOCATION:{$event->location}
        STATUS:CONFIRMED
        END:VEVENT\n";
      }

      // close calendar
      $icalObject .= "END:VCALENDAR";

      $icalObject = str_replace(' ', '', $icalObject);

      header('Content-type: text/calendar; charset=utf-8');
      header('Content-Disposition: attachment; filename="cal.ics"');

      echo $icalObject;
    }

    public function allEventsByArea(Request $request, $area)
    {
      $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
      ->where(function ($query) use ($area) {
        $query->where('groups.area', 'like', '%'.$area.'%');
      })
      ->select('events.*')
      ->groupBy('events.idevents')
      ->orderBy('events.event_date', 'ASC')
      ->get();

      if ( empty($events)) {
        return abort(404, 'No events found.');
      }

      $icalObject = "BEGIN:VCALENDAR
      VERSION:2.0
      METHOD:PUBLISH\n";

      // loop over events
      foreach ($events as $event) {
        $icalObject .=
        "BEGIN:VEVENT
        SUMMARY:{$event->venue}
        DTSTART:".date($this->ical_format, strtotime($event->event_date.' '.$event->start))."
        DTEND:".date($this->ical_format, strtotime($event->event_date.' '.$event->end))."
        LOCATION:{$event->location}
        STATUS:CONFIRMED
        END:VEVENT\n";
      }

      // close calendar
      $icalObject .= "END:VCALENDAR";

      $icalObject = str_replace(' ', '', $icalObject);

      header('Content-type: text/calendar; charset=utf-8');
      header('Content-Disposition: attachment; filename="cal.ics"');

      echo $icalObject;
    }

    public function allEventsByGroupTag(Request $request, GrouptagsGroups $grouptags_groups)
    {
      $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
      ->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
      ->where(function ($query) use($grouptags_groups) {
        $query->where('grouptags_groups.id', $grouptags_groups->id)
              ->whereNull('events.deleted_at');
      })
      ->select('events.*')
      ->groupBy('events.idevents')
      ->orderBy('events.event_date', 'ASC')
      ->get();

      if ( empty($events)) {
        return abort(404, 'No events found.');
      }

      $icalObject = "BEGIN:VCALENDAR
      VERSION:2.0
      METHOD:PUBLISH\n";

      // loop over events
      foreach ($events as $event) {
        $icalObject .=
        "BEGIN:VEVENT
        SUMMARY:{$event->venue}
        DTSTART:".date($this->ical_format, strtotime($event->event_date.' '.$event->start))."
        DTEND:".date($this->ical_format, strtotime($event->event_date.' '.$event->end))."
        LOCATION:{$event->location}
        STATUS:CONFIRMED
        END:VEVENT\n";
      }

      // close calendar
      $icalObject .= "END:VCALENDAR";

      $icalObject = str_replace(' ', '', $icalObject);

      header('Content-type: text/calendar; charset=utf-8');
      header('Content-Disposition: attachment; filename="cal.ics"');

      echo $icalObject;
    }

    public function allEvents(Request $request, $env_hash)
    {
      if ($env_hash != env('CALENDAR_HASH')) {
        return abort(404);
      }

      if ( ! FixometerHelper::hasRole(\Auth::user(), 'Administrator')) {
        return abort(404, 'Not Administrator.');
      }

      $events = Party::whereNull('deleted_at')->get();

      $icalObject = "BEGIN:VCALENDAR
      VERSION:2.0
      METHOD:PUBLISH\n";

      // loop over events
      foreach ($events as $event) {
        $icalObject .=
        "BEGIN:VEVENT
        SUMMARY:{$event->venue}
        DTSTART:".date($this->ical_format, strtotime($event->event_date.' '.$event->start))."
        DTEND:".date($this->ical_format, strtotime($event->event_date.' '.$event->end))."
        LOCATION:{$event->location}
        STATUS:CONFIRMED
        END:VEVENT\n";
      }

      // close calendar
      $icalObject .= "END:VCALENDAR";

      $icalObject = str_replace(' ', '', $icalObject);

      header('Content-type: text/calendar; charset=utf-8');
      header('Content-Disposition: attachment; filename="cal.ics"');

      echo $icalObject;
    }
}
