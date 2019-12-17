<?php

namespace App\Http\Controllers;

use App\Device;
use App\DeviceList;
use App\EventsUsers;
use App\Group;
use App\GroupTags;
use App\GrouptagsGroups;
use App\Helpers\FootprintRatioCalculator;
use App\Party;
use App\Search;
use App\User;
use App\UserGroups;

use Auth;

use DateTime;
use DB;
use FixometerHelper;
use Illuminate\Http\Request;

use Response;

class ExportController extends Controller
{
    public $TotalWeight;
    public $TotalEmission;
    public $EmissionRatio;

    public function __construct()
    {
        //($model, $controller, $action)
        //     parent::__construct($model, $controller, $action);

        $Device = new Device;
        $weights = $Device->getWeights();

        if (isset($weights[0]) && ! empty($weights[0])) {
            $this->TotalWeight = $weights[0]->total_weights;
            $this->TotalEmission = $weights[0]->total_footprints;
        } else {
            $this->TotalWeight = null;
            $this->TotalEmission = null;
        }
        $footprintRatioCalculator = new FootprintRatioCalculator();
        $this->EmissionRatio = $footprintRatioCalculator->calculateRatio();
    }

    public function devices(Request $request)
    {
        $Device = new Device;

        // To not display column if the referring URL is therestartproject.org
        $host = parse_url(\Request::server('HTTP_REFERER'), PHP_URL_HOST);

        $all_devices = app('App\Http\Controllers\DeviceController')->search($request, true);

        // Create CSV
        $filename = 'devices.csv';
        $file = fopen($filename, 'w+');

        // Do not include model column
        if ($host == 'therestartproject.org') {
            $columns = array(
                'Product Category',
                'Brand',
                'Comments',
                'Repair Status',
                'Spare parts (needed/used)',
                'Event',
                'Group',
                'Date',
            );

            fputcsv($file, $columns);

            foreach ($all_devices as $device) {
                fputcsv($file, [
                    $device->deviceCategory->name,
                    $device->brand,
                    $device->problem,
                    $device->getRepairStatus(),
                    $device->getSpareParts(),
                    $device->deviceEvent->getEventName(),
                    $device->deviceEvent->theGroup->name,
                    $device->deviceEvent->getEventDate('Y-m-d'),
                ]);
            }
        } else {
            $columns = array(
                'Product Category',
                'Brand',
                'Model',
                'Comments',
                'Repair Status',
                'Spare parts (needed/used)',
                'Event',
                'Group',
                'Date',
            );

            fputcsv($file, $columns);

            foreach ($all_devices as $device) {
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
                ]);
            }
        }

        fclose($file);

        $headers = array(
            'Content-Type' => 'text/csv',
        );

        return Response::download($filename, 'devices.csv', $headers);

        // $header = array(
        //             array(
        //                 'Category',
        //                 'Brand',
        //                 'Model',
        //                 'Comments',
        //                 'Repair Status',
        //                 'Spare parts (needed/used)',
        //                 'Restart Party Location',
        //                 'Restart Group',
        //                 'Restart Party Date'
        //                 )
        //             );
        // $data = array_merge($header, $data);
        //
        // return view('export.devices', [
        //   'data' => $data,
        // ]);
    }

    public function parties()
    {
        $Groups = new Group;
        $Parties = new Party;
        $Device = new Device;
        $Search = new Search;
        unset($_GET['url']);
        if (isset($_GET['fltr']) && ! empty($_GET['fltr'])) {
            $searched_groups = null;
            $searched_parties = null;
            $toTimeStamp = null;
            $fromTimeStamp = null;
            $group_tags = null;

            /** collect params **/
            if (isset($_GET['groups'])) {
                $searched_groups = filter_var_array($_GET['groups'], FILTER_SANITIZE_NUMBER_INT);
            }
            if (isset($_GET['parties'])) {
                $searched_parties = filter_var_array($_GET['parties'], FILTER_SANITIZE_NUMBER_INT);
            }

            if (isset($_GET['from-date']) && ! empty($_GET['from-date'])) {
                if (! DateTime::createFromFormat('Y-m-d', $_GET['from-date'])) {
                    $response['danger'] = 'Invalid "from date"';
                    $fromTimeStamp = null;
                } else {
                    $fromDate = DateTime::createFromFormat('Y-m-d', $_GET['from-date']);
                    $fromTimeStamp = strtotime($fromDate->format('Y-m-d'));
                }
            }

            if (isset($_GET['to-date']) && ! empty($_GET['to-date'])) {
                if (! DateTime::createFromFormat('Y-m-d', $_GET['to-date'])) {
                    $response['danger'] = 'Invalid "to date"';
                } else {
                    $toDate = DateTime::createFromFormat('Y-m-d', $_GET['to-date']);
                    $toTimeStamp = strtotime($toDate->format('Y-m-d'));
                }
            }

            if (isset($_GET['group_tags']) && is_array($_GET['group_tags'])) {
                $group_tags = $_GET['group_tags'];
            }

            $PartyList = $Search->parties($searched_parties, $searched_groups, $fromTimeStamp, $toTimeStamp, $group_tags);
            $PartyArray = array();
            $need_attention = 0;
            $participants = 0;
            $hours_volunteered = 0;
            $totalCO2 = 0;
            foreach ($PartyList as $i => $party) {
                if ($party->device_count == 0) {
                    $need_attention++;
                }

                $partyIds[] = $party->idevents;

                $party->co2 = 0;
                $party->weight = 0;
                $party->fixed_devices = 0;
                $party->repairable_devices = 0;
                $party->dead_devices = 0;
                $party->guesstimates = false;

                $participants += $party->pax;
                $party->hours_volunteered = $party->hoursVolunteered();
                $hours_volunteered += $party->hours_volunteered;

                foreach ($party->devices as $device) {
                    switch ($device->repair_status) {
                        case 1:
                            $party->co2 += $device->co2Diverted($this->EmissionRatio, $Device->displacement);
                            $party->fixed_devices++;
                            $party->weight += $device->ewasteDiverted();

                            break;
                        case 2:
                            $party->repairable_devices++;

                            break;
                        case 3:
                            $party->dead_devices++;

                            break;
                    }
                    if ($device->category == 46) {
                        $party->guesstimates = true;
                    }
                }
                $party->weight = $party->weight;

                $totalCO2 += $party->co2;

                $partyName = ! is_null($party->venue) ? $party->venue : $party->location;
                $groupName = $party->name; // because of the way the join in the query works
                $PartyArray[$i] = array(
                    date('d/m/Y', strtotime($party->event_date)),
                    $partyName,
                    $groupName,
                    ($party->pax > 0 ? $party->pax : 0),
                    ($party->volunteers > 0 ? $party->volunteers : 0),
                    ($party->co2 > 0 ? round($party->co2, 2) : 0),
                    ($party->weight > 0 ? round($party->weight, 2) : 0),
                    ($party->fixed_devices > 0 ? $party->fixed_devices : 0),
                    ($party->repairable_devices > 0 ? $party->repairable_devices : 0),
                    ($party->dead_devices > 0 ? $party->dead_devices : 0),
                    ($party->hours_volunteered > 0 ? $party->hours_volunteered : 0),
                );
            }

            /** lets format the array **/
            $columns = array(
                'Date', 'Venue', 'Group', 'Participants', 'Volunteers', 'CO2 (kg)', 'Weight (kg)', 'Fixed', 'Repairable', 'Dead', 'Hours Volunteered',
            );

            $filename = 'parties.csv';

            $file = fopen($filename, 'w+');
            fputcsv($file, $columns);

            foreach ($PartyArray as $d) {
                //$d = array_filter((array) $d, 'utf8_encode');
                fputcsv($file, $d);
            }
            fclose($file);

            $headers = array(
                'Content-Type' => 'text/csv',
            );

            return Response::download($filename, $filename, $headers);

            /** lets format the array **/
          // $headers = array(
          //       "Date","Venue","Group","Participants","Volunteers","CO2 (kg)","Weight (kg)","Fixed","Repairable","Dead","Hours Volunteered"
          // );
          // $data = array_merge($columns, $PartyArray);

          // return view('export.parties', [
          //   'data' => $data,
          // ]);
        }
        $data = array('No data to return');

        return view('export.parties', [
            'data' => $data,
        ]);
    }

    // TODO: why is this in ExportController?
    public function getTimeVolunteered($search = null, $export = false, Request $request)
    {
        $user = Auth::user();

        //Get all group tags
        $all_group_tags = GroupTags::all();

        //Get all applicable groups
        if (FixometerHelper::hasRole($user, 'Administrator')) {
            $all_groups = Group::all();
        } elseif (FixometerHelper::hasRole($user, 'Host')) {
            $host_groups = UserGroups::where('user', $user->id)->where('role', 3)->pluck('group')->toArray();
            $all_groups = Group::whereIn('groups.idgroups', $host_groups);
        } elseif (FixometerHelper::hasRole($user, 'Restarter')) {
            $all_groups = null;
        }

        //See whether it is a get search or index page
        if (is_null($search)) {
            $user_events = EventsUsers::join('users', 'events_users.user', 'users.id')
                             ->join('events', 'events_users.event', 'events.idevents')
                                ->join('groups', 'events.group', 'groups.idgroups')
                                  ->whereNotNull('events_users.user');

            if (FixometerHelper::hasRole($user, 'Host')) {
                $user_events = $user_events->whereIn('groups.idgroups', $host_groups);
            } elseif (FixometerHelper::hasRole($user, 'Restarter')) {
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

            if (FixometerHelper::hasRole($user, 'Host')) {
                $user_events = $user_events->whereIn('groups.idgroups', $host_groups);
            } elseif (FixometerHelper::hasRole($user, 'Restarter')) {
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
                $user_events = $user_events->whereBetween('events.event_date', array($request->input('from_date'),
                    $request->input('to_date'), ));
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
        $all_country_hours_completed = $country_hours_completed->orderBy('event_hours', 'DSC')->get();
        $country_hours_completed = $country_hours_completed->orderBy('event_hours', 'DSC')->take(5)->get();

        //city hours completed
        $city_hours_completed = clone $user_events;
        $city_hours_completed = $city_hours_completed->groupBy('users.location')->select('users.location', DB::raw('SUM(TIMEDIFF(end, start)) as event_hours'));
        $all_city_hours_completed = $city_hours_completed->orderBy('event_hours', 'DSC')->get();
        $city_hours_completed = $city_hours_completed->orderBy('event_hours', 'DSC')->take(5)->get();

        //order by users id
        $user_events = $user_events->orderBy('events.event_date', 'DSC');

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

        return array(
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
        );
    }

    public function exportTimeVolunteered(Request $request)
    {
        if (! empty($request->all())) {
            $data = $this->getTimeVolunteered(true, true, $request);
        } else {
            $data = $this->getTimeVolunteered(null, true, $request);
        }

        //Creat new file and set headers
        $file_name = 'time_reporting.csv';
        $file = fopen($file_name, 'w+');
        $file_headers = array(
            'Content-type' => 'text/csv',
        );

        //Put stats in csv
        $stats_headers = array('Hours Volunteered', 'Average age', 'Number of groups', 'Total number of users', 'Number of anonymous users');
        fputcsv($file, array('Overall Stats:'));
        fputcsv($file, $stats_headers);
        fputcsv($file, array($data['hours_completed'], $data['average_age'], $data['group_count'], $data['total_users'], $data['anonymous_users']));
        fputcsv($file, array());

        //Put breakdown by country in csv
        $country_headers = array('Country name', 'Total hours');
        fputcsv($file, array('Breakdown by country:'));
        fputcsv($file, $country_headers);
        foreach ($data['country_hours_completed'] as $country_hours) {
            if (! is_null($country_hours->country)) {
                $country = $country_hours->country;
            } else {
                $country = 'N/A';
            }
            fputcsv($file, array($country, substr($country_hours->event_hours, 0, -4)));
        }
        fputcsv($file, array());

        //Put breakdown by city in csv
        $city_headers = array('Town/city name', 'Total hours');
        fputcsv($file, array('Breakdown by city:'));
        fputcsv($file, $city_headers);
        foreach ($data['city_hours_completed'] as $city_hours) {
            if (! is_null($city_hours->location)) {
                $city = $city_hours->location;
            } else {
                $city = 'N/A';
            }
            fputcsv($file, array($city, substr($city_hours->event_hours, 0, -4)));
        }
        fputcsv($file, array());

        //Put users in csv
        $users_headers = array('#', 'Hours', 'Event date', 'Restart group', 'Location');
        fputcsv($file, array('Results:'));
        fputcsv($file, $users_headers);
        foreach ($data['user_events'] as $ue) {
            $start_time = new DateTime($ue->start);
            $diff = $start_time->diff(new DateTime($ue->end));
            fputcsv($file, array($ue->idevents, $diff->h.'.'.sprintf('%02d', $diff->i / 60 * 100),
                date('d/m/Y', strtotime($ue->event_date)), $ue->groupname, $ue->location, ));
        }
        fputcsv($file, array());

        //close file
        fclose($file);

        return Response::download($file_name, $file_name, $file_headers);
    }
}
