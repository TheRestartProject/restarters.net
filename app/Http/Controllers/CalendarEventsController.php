<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Party;
use App\Models\User;
use App\Models\Network;
use Carbon\Carbon;
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

        // We use two separate queries because they are a lot more efficient in DB terms than using an OR clause.
        $attendingEvents = Party::join('groups', 'groups.idgroups', '=', 'events.group')
          ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
          ->join('events_users', 'events_users.event', '=', 'events.idevents')
          ->where('events_users.user', $user->id)
          ->whereNull('users_groups.deleted_at')
          ->select('events.*', 'groups.name');

        $groupEvents = Party::join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->join('events_users', 'events_users.event', '=', 'events.idevents')
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->select('events.*', 'groups.name');

        // GROUP BY doesn't seem to be enough to get unique values, so do unique() on the results.
        $events = $attendingEvents->union($groupEvents)->groupBy('idevents')->orderBy('event_start_utc', 'asc')->get()->unique();

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
      ->orderBy('events.event_start_utc', 'ASC')
      ->get();

        if (empty($events) || !$events->count()) {
            return abort(404, 'No events found.');
        }

        $this->exportCalendar($events);
    }

    public function allEventsByNetwork(Request $request, Network $network)
    {
        $events = Party::join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where(function ($query) use ($network) {
                $query->where('group_network.network_id', $network->id)
                    ->whereNull('events.deleted_at');
            })
            ->select('events.*', 'groups.name')
            ->groupBy('events.idevents')
            ->orderBy('events.event_start_utc', 'ASC')
            ->get();

        if (empty($events) || !$events->count()) {
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
      ->orderBy('events.event_start_utc', 'ASC')
      ->get();

        if (empty($events) || !$events->count()) {
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
        $me = auth()->user();

        // We cache the group approval status to reduce DB queries.
        $groupApproved = [];

        foreach ($events as $event) {
            // We need to filter by approval status.  If the event is not approved, we can only see it if we are
            // an admin, network coordinator, or the host of the event.

            if (!User::userCanSeeEvent($me, $event)) {
                continue;
            }

            if (! is_null($event->event_start_utc) ) {
                if (!array_key_exists($event->group, $groupApproved)) {
                    $group = Group::find($event->group);

                    $groupApproved[$event->group] = $group ? $group->approved : false;
                }

                $ical[] = 'BEGIN:VEVENT';

                $ical[] = 'TZID:' . $event->timezone;
                $ical[] = "UID:{$event->idevents}";
                $ical[] = 'DTSTAMP:'.date($this->ical_format).'';
                $ical[] = "SUMMARY:{$event->venue} ({$event->name})";
                $ical[] = 'DTSTART;TZID=' . $event->timezone . ':'.$event->getFormattedLocalStart($this->ical_format);
                $ical[] = 'DTEND;TZID=' . $event->timezone . ':'.$event->getFormattedLocalEnd($this->ical_format);
                $ical[] = 'DESCRIPTION:'.url('/party/view').'/'.$event->idevents;
                $ical[] = "LOCATION:{$event->location}";
                $ical[] = 'URL:'.url('/party/view').'/'.$event->idevents;

                if ($event->cancelled) {
                    $ical[] = 'STATUS:CANCELLED';
                } else if ($event->approved && $groupApproved[$event->group]) {
                    // Events are only confirmed once the event and the group are approved.
                    $ical[] = 'STATUS:CONFIRMED';
                } else {
                    $ical[] = 'STATUS:TENTATIVE';
                }

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
}
