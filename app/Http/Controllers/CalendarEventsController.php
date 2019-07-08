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

    private $icalObject = [];

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

      $this->exportCalendar($events);
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

      $this->exportCalendar($events);
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

      $this->exportCalendar($events);
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

      $this->exportCalendar($events);
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

      $this->exportCalendar($events);
    }

    public function exportCalendar($events)
    {
      $icalObject[] =  "BEGIN:VCALENDAR";
      $icalObject[] =  "VERSION:2.0";
      $icalObject[] =  "PRODID:-//Restarters//NONSGML Events Calendar/EN";

      // loop over events
      foreach ($events as $event) {
          if ( ! is_null($event->event_date) && $event->event_date != '0000-00-00') {
              $icalObject[] =  "BEGIN:VEVENT";
              $icalObject[] =  "UID:{$event->idevents}";
              $icalObject[] =  "DTSTAMP:".date($this->ical_format)."";
              $icalObject[] =  "SUMMARY:{$event->venue}";
              $icalObject[] =  "DTSTART:".date($this->ical_format, strtotime($event->event_date.' '.$event->start))."";
              $icalObject[] =  "DTEND:".date($this->ical_format, strtotime($event->event_date.' '.$event->end))."";
              $icalObject[] =  "LOCATION:{$event->location}";
              $icalObject[] =  "STATUS:CONFIRMED";
              $icalObject[] =  "END:VEVENT";
          }
      }

      // close calendar
      $icalObject[] =  "END:VCALENDAR";

      $icalObject = implode("\r\n",$icalObject);

      header('Content-type: text/calendar; charset=utf-8');
      header('Content-Disposition: attachment; filename="cal.ics"');

      echo $icalObject;
    }
}
