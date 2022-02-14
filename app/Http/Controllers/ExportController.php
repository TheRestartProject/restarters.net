<?php

namespace App\Http\Controllers;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\GroupTags;
use App\GrouptagsGroups;
use App\Helpers\Fixometer;
use App\Helpers\SearchHelper;
use App\Search;
use App\UserGroups;
use Auth;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Response;

class ExportController extends Controller
{
    public function devices(Request $request)
    {
        // To not display column if the referring URL is therestartproject.org
        $host = parse_url(\Request::server('HTTP_REFERER'), PHP_URL_HOST);

        $all_devices = Device::with([
            'deviceCategory',
            'deviceEvent',
        ])
            ->join('events', 'events.idevents', '=', 'devices.event')
            ->join('groups', 'groups.idgroups', '=', 'events.group')
            ->select('devices.*', 'groups.name AS group_name')->get();

        $displacementFactor = \App\Device::getDisplacementFactor();
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        // Create CSV
        $filename = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'devices.csv';
        $file = fopen($filename, 'w+');

        // Do not include model column
        if ($host == 'therestartproject.org') {
            $columns = [
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
                    $device->deviceCategory->name,
                    $device->brand,
                    $device->problem,
                    $device->getRepairStatus(),
                    $device->getSpareParts(),
                    $device->deviceEvent->getEventName(),
                    $device->deviceEvent->theGroup->name,
                    $device->deviceEvent->getEventDate('Y-m-d'),
                    $wasteImpact,
                    $co2Diverted
                ]);
            }
        } else {
            $columns = [
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

            foreach ($all_devices as $device) {
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
                    $device->deviceCategory->name,
                    $device->brand,
                    $device->model,
                    $device->problem,
                    $device->getRepairStatus(),
                    $device->getSpareParts(),
                    $device->deviceEvent->getEventName(),
                    $device->deviceEvent->theGroup->name,
                    $device->deviceEvent->getEventDate('Y-m-d'),
                    $wasteImpact,
                    $co2Diverted,
                ]);
            }
        }

        fclose($file);

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        return Response::download($filename, 'devices.csv', $headers);
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
                $headers = array_merge(['Date', 'Venue', 'Group'], $statsKeys);

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
                        $party->getEventDate(),
                        $party->getEventName(),
                        $party->theGroup && $party->theGroup->name ? $party->theGroup->name : '?',
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

    // TODO: why is this in ExportController?
    public function getTimeVolunteered(Request $request, $search = null, $export = false)
    {
        $user = Auth::user();

        //Get all group tags
        $all_group_tags = GroupTags::all();

        //Get all applicable groups
        if (Fixometer::hasRole($user, 'Administrator')) {
            $all_groups = Group::all();
        } elseif (Fixometer::hasRole($user, 'Host')) {
            $host_groups = UserGroups::where('user', $user->id)->where('role', 3)->pluck('group')->toArray();
            $all_groups = Group::whereIn('groups.idgroups', $host_groups);
        } elseif (Fixometer::hasRole($user, 'Restarter')) {
            $all_groups = null;
        }

        //See whether it is a get search or index page
        if (is_null($search)) {
            $user_events = EventsUsers::join('users', 'events_users.user', 'users.id')
                ->join('events', 'events_users.event', 'events.idevents')
                ->join('groups', 'events.group', 'groups.idgroups')
                ->whereNotNull('events_users.user');

            if (Fixometer::hasRole($user, 'Host')) {
                $user_events = $user_events->whereIn('groups.idgroups', $host_groups);
            } elseif (Fixometer::hasRole($user, 'Restarter')) {
                $user_events = $user_events->where('users.id', $user->id);
            }
        } else {
            //Misc
            //Anonymous
            if ($request->input('misc') !== null && $request->input('misc') == 1) {
                $user_events = EventsUsers::leftJoin('users', 'events_users.user', 'users.id')
                    ->join('events', 'events_users.event', 'events.idevents')
                    ->join('groups', 'events.group', 'groups.idgroups');
            } else {
                $user_events = EventsUsers::join('users', 'events_users.user', 'users.id')
                    ->join('events', 'events_users.event', 'events.idevents')
                    ->join('groups', 'events.group', 'groups.idgroups')
                    ->whereNotNull('events_users.user');
            }

            if (Fixometer::hasRole($user, 'Host')) {
                $user_events = $user_events->whereIn('groups.idgroups', $host_groups);
            } elseif (Fixometer::hasRole($user, 'Restarter')) {
                $user_events = $user_events->where('users.id', $user->id);
            }

            //Taxonomy
            //Group filter
            if ($request->input('groups') !== null) {
                $user_events = $user_events->whereIn('groups.idgroups', $request->input('groups'));
            }

            //Group tags filter
            if ($request->input('tags') !== null) {
                $user_events = $user_events->whereIn('groups.idgroups', GrouptagsGroups::whereIn('group_tag', $request->input('tags'))->pluck('group'));
            }

            //By users
            //Name
            if ($request->input('name') !== null) {
                $user_events = $user_events->where('users.name', 'like', '%'.$request->input('name').'%');
            }

            //Birth year
            if ($request->input('year') !== null) {
                $user_events = $user_events->whereBetween('users.age', explode('-', $request->input('year')));
            }

            //Gender
            if ($request->input('gender') !== null) {
                $user_events = $user_events->where('users.gender', 'like', '%'.$request->input('gender').'%');
            }

            //By date
            if ($request->input('from_date') !== null && $request->input('to_date') == null) {
                $user_events = $user_events->whereDate('events.event_date', '>', $request->input('from_date'));
            } elseif ($request->input('to_date') !== null && $request->input('from_date') == null) {
                $user_events = $user_events->whereDate('events.event_date', '<', $request->input('to_date'));
            } elseif ($request->input('to_date') !== null && $request->input('from_date') !== null) {
                $user_events = $user_events->whereBetween('events.event_date', [
                    $request->input('from_date'),
                    $request->input('to_date'),
                ]);
            }

            //By location
            //Country
            if ($request->input('country') !== null) {
                $user_events = $user_events->where('users.country', $request->input('country'));
            }

            //Region
            //Need to add this in later is disabled at the moment
        }

        // Filter out the old 'superhero' Restarter that was automatically added
        // in the old system (pre-July 2018) to any event that was created.
        $user_events->where('users.id', '<>', 29);

        //total users
        $total_users = clone $user_events;
        $total_users = $total_users->distinct('users.id')->count('users.id');

        //anonymous users
        $anonymous_users = clone $user_events;
        $anonymous_users = $anonymous_users->whereNull('user')->count('*');

        //group count
        $group_count = clone $user_events;
        $group_count = $group_count->distinct('group')->count('group');

        //average age
        $average_age = clone $user_events;
        $average_age = $average_age->distinct('users.id')->pluck('users.age')->toArray();

        foreach ($average_age as $key => $value) {
            if (! is_int(intval($value)) || intval($value) <= 0) {
                unset($average_age[$key]);
            } else {
                $average_age[$key] = intval($value);
            }
        }

        if (! empty($average_age)) {
            $average_age = array_sum($average_age) / count($average_age);
            $average_age = intval(date('Y')) - $average_age;
        } else {
            $average_age = 0;
        }

        //hours completed
        $hours_completed = clone $user_events;
        $hours_completed = substr($hours_completed->sum(DB::raw('TIMEDIFF(end, start)')), 0, -4);

        //country hours completed
        $country_hours_completed = clone $user_events;
        $country_hours_completed = $country_hours_completed->groupBy('users.country')->select('users.country', DB::raw('SUM(TIMEDIFF(end, start)) as event_hours'));
        $all_country_hours_completed = $country_hours_completed->orderBy('event_hours', 'DESC')->get();
        $country_hours_completed = $country_hours_completed->orderBy('event_hours', 'DESC')->take(5)->get();

        //city hours completed
        $city_hours_completed = clone $user_events;
        $city_hours_completed = $city_hours_completed->groupBy('users.location')->select('users.location', DB::raw('SUM(TIMEDIFF(end, start)) as event_hours'));
        $all_city_hours_completed = $city_hours_completed->orderBy('event_hours', 'DESC')->get();
        $city_hours_completed = $city_hours_completed->orderBy('event_hours', 'DESC')->take(5)->get();

        //order by users id
        $user_events = $user_events->orderBy('events.event_date', 'DESC');

        //Select all necessary information for table
        $user_events = $user_events->select(
            'users.id',
            'users.name as username',
            'events.idevents',
            'events.start',
            'events.end',
            'events.event_date',
            'events.location',
            'events.venue',
            'groups.name as groupname'
        );

        if (! $export) {
            $user_events = $user_events->paginate(env('PAGINATE'));
        } else {
            $user_events = $user_events->get();
        }

        if (! $export) {
            return view('reporting.time-volunteered', [
                'user' => $user,
                'user_events' => $user_events,
                'all_groups' => $all_groups,
                'all_group_tags' => $all_group_tags,
                'groups' => $request->input('groups'),
                'selected_tags' => $request->input('tags'),
                'name' => $request->input('name'),
                'age' => $request->input('year'),
                'gender' => $request->input('gender'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'country' => $request->input('country'),
                'region' => null,
                'misc' => $request->input('misc'),
                'total_users' => $total_users,
                'anonymous_users' => $anonymous_users,
                'group_count' => $group_count,
                'hours_completed' => $hours_completed,
                'average_age' => number_format($average_age, 1),
                'country_hours_completed' => $country_hours_completed,
                'all_country_hours_completed' => $all_country_hours_completed,
                'city_hours_completed' => $city_hours_completed,
                'all_city_hours_completed' => $all_city_hours_completed,
            ]);
        }

        return [
            'user' => $user,
            'user_events' => $user_events,
            'all_groups' => $all_groups,
            'all_group_tags' => $all_group_tags,
            'total_users' => $total_users,
            'anonymous_users' => $anonymous_users,
            'group_count' => $group_count,
            'hours_completed' => $hours_completed,
            'average_age' => number_format($average_age, 1),
            'country_hours_completed' => $country_hours_completed,
            'city_hours_completed' => $city_hours_completed,
        ];
    }
}
