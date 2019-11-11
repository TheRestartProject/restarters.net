<?php

namespace App\Http\Controllers;

use App\Device;
use App\Events\ApproveGroup;
use App\Events\EditGroup;
use App\Group;
use App\GroupTags;
use App\GrouptagsGroups;
use App\Helpers\FootprintRatioCalculator;
use App\Invite;
use App\Notifications\AdminModerationGroup;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\Notifications\NewGroupWithinRadius;
use App\Party;
use App\User;
use App\UserGroups;
use Auth;
use DB;
use FixometerFile;
use FixometerHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Notification;

class GroupController extends Controller
{
    public function __construct()
    {
        $Device = new Device;
        $weights = $Device->getWeights();

        $this->TotalWeight = $weights[0]->total_weights;
        $this->TotalEmission = $weights[0]->total_footprints;
        $footprintRatioCalculator = new FootprintRatioCalculator();
        $this->EmissionRatio = $footprintRatioCalculator->calculateRatio();
    }

    public function index($all = false)
    {
        //Get current logged in user
        $user = Auth::user();

        $groups = null;

        if ($all) {

            // All groups only
            $groupsQuery = Group::orderBy('name', 'ASC');
            $groups = $groupsQuery->paginate(env('PAGINATE'));
            $groups_count = $groupsQuery->count();

            //Get all group tags
            $all_group_tags = GroupTags::all();

            //Look for groups where user ID exists in pivot table
            $your_groups_uniques = UserGroups::where('user', auth()->id())->pluck('group')->toArray();

            return view('group.index', [
                'your_groups' => null,
                'your_groups_uniques' => $your_groups_uniques,
                'groups_near_you' => null,
                'groups' => $groups,
                'your_area' => null,
                'all' => $all,
                'all_group_tags' => $all_group_tags,
                'sort_direction' => 'ASC',
                'sort_column' => 'name',
                'groups_count' => $groups_count,
            ]);
        }

        $sort_direction = request()->input('sort_direction');
        $sort_column = request()->input('sort_column');

        //Look for groups where user ID exists in pivot table
        $your_groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->join('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id);

            if ( ! empty($sort_direction) && ! empty($sort_column)) {
              $your_groups = $your_groups->whereDate('events.event_date', '>=', date('Y-m-d'))
                    ->orderBy('events.event_date', $sort_direction);
            }

            $your_groups = $your_groups->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups')
            ->select('groups.*')
            ->get();

        //Make sure we don't show the same groups in nearest to you
        $your_groups_uniques = $your_groups->pluck('idgroups')->toArray();

        //Assuming we have valid lat and long values, let's see what is nearest
        if ( ! is_null($user->latitude) && ! is_null($user->longitude)) {
          $groups_near_you = Group::select(DB::raw('`groups`.*, ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( groups.latitude ) ) * cos( radians( groups.longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( groups.latitude ) ) ) ) AS distance'))
              ->having('distance', '<=', 150)
              ->join('events', 'events.group', '=', 'groups.idgroups')
              ->whereNotIn('groups.idgroups', $your_groups_uniques);

          if ( ! empty($sort_direction) && ! empty($sort_column)) {
            $groups_near_you = $groups_near_you->whereDate('events.event_date', '>=', date('Y-m-d'))
                  ->orderBy('events.event_date', $sort_direction);
          }

          $groups_near_you = $groups_near_you->groupBy('groups.idgroups')
              ->orderBy('distance', 'ASC')
              ->orderBy('distance', 'ASC')
              ->take(10)
              ->get();

        } else {
            $groups_near_you = null;
        }

        return view('group.index', [
            'your_groups' => $your_groups,
            'your_groups_uniques' => $your_groups_uniques,
            'groups_near_you' => $groups_near_you,
            'groups' => $groups,
            'your_area' => $user->location,
            'all' => $all,
            'sort_direction' => $sort_direction,
            'sort_column' => $sort_column,
        ]);
    }

    public function searchColumn(Request $request)
    {
        $all = false;
        $groups = null;

        $sort_direction = $request->input('sort_direction');
        $sort_column = $request->input('sort_column');

        //Get current logged in user
        $user = Auth::user();

        $your_area = $user->location;

        //Look for groups where user ID exists in pivot table
        $your_groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->orderBy('name', 'ASC')
            ->select('groups.*', 'users_groups.user')
            ->get();

        //Make sure we don't show the same groups in nearest to you
        $your_groups_uniques = $your_groups->pluck('idgroups')->toArray();

        //Assuming we have valid lat and long values, let's see what is nearest
        $groups_near_you = $user->groupsNearby(150, 10, $your_groups_uniques);

        return view('group.index', [
            'your_groups' => $your_groups,
            'groups_near_you' => $groups_near_you,
            'groups' => $groups,
            'your_area' => $your_area,
            'all' => $all,
            'sort_direction' => $sort_direction,
            'sort_column' => $sort_column,
        ]);
    }

    /**
     * [search description]
     * All groups only
     *
     * @author Christopher Kelker - @date 2019-03-26
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @param   Request     $request
     * @return  [type]
     */
    public function search(Request $request)
    {
        // variables
        $groups = new Group;

        //Get all group tags
        $all_group_tags = GroupTags::all();

        $sort_direction = $request->input('sort_direction');
        $sort_column = $request->input('sort_column');

        if ( ! empty($request->input('name'))) {
            $groups = $groups->where('name', 'like', '%'.$request->input('name').'%');
        }

        if ( ! empty($request->input('location'))) {
            $groups = $groups->where(function ($query) use ($request){
                  $query->where('groups.location', 'like', '%'.$request->input('location').'%')
                        ->orWhere('groups.area', 'like', '%'.$request->input('location').'%');
              });
        }

        if ( ! empty($request->input('country'))) {
            $groups = $groups->where('country', $request->input('country'));
        }

        if ( ! empty($request->input('tags'))) {
            $groups = $groups->whereIn('idgroups', GrouptagsGroups::whereIn('group_tag', $request->input('tags'))->pluck('group'));
        }

        if ( ! empty($sort_column) && $sort_column == 'name') {
            $groups = $groups->orderBy('name', $sort_direction);
        }

        if ( ! empty($sort_column) && $sort_column == 'distance') {
            $groups = $groups->orderBy('location', $sort_direction);
        }

        if ( ! empty($sort_column) && $sort_column == 'hosts') {
            $groups = $groups->with('allHosts')
                              ->with('allRestarters')
                              ->orderBy('all_hosts_count', $sort_direction);
        }

        if ( ! empty($sort_column) && $sort_column == 'upcoming_event') {
          $groups = $groups->leftJoin('events', 'events.group', '=', 'groups.idgroups')
                            ->whereDate('events.event_date', '>=', date('Y-m-d'))
                            ->orderBy('events.event_date', $sort_direction)
                            ->select('groups.*')
                            ->groupBy('groups.idgroups');
        }

        if ( ! empty($sort_column) && $sort_column == 'restarters') {
            $groups = $groups->with('allHosts')
                              ->with('allRestarters')
                              ->orderBy('all_restarters_count', $sort_direction);
        }

        if ( ! empty($sort_column) && $sort_column == 'created_at') {
            $groups = $groups->orderBy('created_at', $sort_direction)
                                ->whereNotNull('created_at');
        }

        $groups = $groups->paginate(env('PAGINATE'));
        $groups_count = $groups->total();

        //Look for groups where user ID exists in pivot table
        $your_groups_uniques = UserGroups::where('user', auth()->id())->pluck('group')->toArray();

        return view('group.index', [
            'your_groups' => null,
            'groups_near_you' => null,
            'groups' => $groups,
            'your_area' => null,
            'all' => true,
            'all_group_tags' => $all_group_tags,
            'your_groups_uniques' => $your_groups_uniques,
            'name' => $request->input('name'),
            'location' => $request->input('location'),
            'selected_country' => $request->input('country'),
            'selected_tags' => $request->input('tags'),
            'sort',
            'sort_direction' => $sort_direction,
            'sort_column' => $sort_column,
            'groups_count' => $groups_count,
        ]);
    }

    public function create()
    {
        $user = User::find(Auth::id());

        // Only administrators can add groups
        if (FixometerHelper::hasRole($user, 'Restarter')) {
            return redirect('/user/forbidden');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST)) {
            $error = array();
            $Group = new Group;

            // We got data! Elaborate. //NB:: Taken out frequency as it doesn't appear in the post data might be gmaps
            $name = $_POST['name'];
            $website = $_POST['website'];
            $location = $_POST['location'];
            $text = $_POST['free_text'];

            if (empty($name)) {
                $error['name'] = 'Please input a name.';
            }
            
            if ( ! empty($location)) {
                $lat_long = FixometerHelper::getLatLongFromCityCountry($location);

                if ( empty($lat_long) ) {
                  $response['danger'] = 'Group could not be created. Address not found.';
                  return view('group.create', [
                      'title' => 'New Group',
                      'gmaps' => true,
                      'response' => $response,
                  ]);
                }

                $latitude = $lat_long[0];
                $longitude = $lat_long[1];
                $country = $lat_long[2];
            } else {
                $latitude = null;
                $longitude = null;
                $country = null;
            }

            if (empty($error)) {
                // No errors. We can proceed and create the User.
                $data = array('name' => $name,
                    'website' => $website,
                    // 'frequency'     => $freq,
                    'location' => $location,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'country' => $country,
                    'free_text' => $text,
                    'shareable_code' => FixometerHelper::generateUniqueShareableCode('App\Group', 'shareable_code'),
                );

                $idGroup = $Group->create($data)->idgroups;

                if (is_numeric($idGroup) && $idGroup !== false) {
                    $idGroup = Group::find($idGroup);
                    $lat1 = $idGroup->latitude;
                    $lon1 = $idGroup->longitude;

                    $idGroup = $idGroup->idgroups;

                    $response['success'] = 'Group created correctly.';

                    if (isset($_FILES) && ! empty($_FILES)) {
                        $file = new FixometerFile;
                        $group_avatar = $file->upload('file', 'image', $idGroup, env('TBL_GROUPS'), false, true);
                    }

                    //Associate current logged in user as a host
                    UserGroups::create([
                        'user' => Auth::user()->id,
                        'group' => $idGroup,
                        'status' => 1,
                        'role' => 3,
                    ]);

                    // Notify relevant admins
                    $notify_admins = FixometerHelper::usersWhoHavePreference('admin-moderate-group');
                    Notification::send($notify_admins, new AdminModerationGroup([
                        'group_name' => $name,
                        'group_url' => url('/group/edit/'.$idGroup),
                    ]));
                } else {
                    $response['danger'] = 'Group could <strong>not</strong> be created. Something went wrong with the database.';
                }
            } else {
                $response['danger'] = 'Group could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
            }

            if ( ! isset($response)) {
                $response = null;
            }

            if ( ! isset($error)) {
                $error = null;
            }

            if ( ! isset($_POST)) {
                $udata = null;
            } else {
                $udata = $_POST;
            }

            if (is_numeric($idGroup) && $idGroup !== false) {
                return redirect('/group/edit/'.$idGroup)->with('response', $response);
            }

            return view('group.create', [
                'title' => 'New Group',
                'gmaps' => true,
                'response' => $response,
                'error' => $error,
                'udata' => $udata,
            ]);
        }

        return view('group.create', [
            'title' => 'New Group',
            'gmaps' => true,
        ]);
    }

    public function view($groupid)
    {
        if (isset($_GET['action']) && isset($_GET['code'])) {
            $actn = $_GET['action'];
            $code = $_GET['code'];

            switch ($actn) {
                case 'gu':
                    $response['success'] = 'Group updated.';

                    break;
                case 'pe':
                    $response['success'] = 'Party updated.';

                    break;
                case 'pc':
                    $response['success'] = 'Party created.';

                    break;
                case 'ue':
                    $response['success'] = 'Profile updated.';

                    break;
                case 'de':
                    if ($code == 200) {
                        $response['success'] = 'Party deleted.';
                    } elseif ($code == 403) {
                        $response['danger'] = 'Couldn\'t delete the party!';
                    } elseif ($code == 500) {
                        $response['warning'] = 'The party has been deleted, but <strong>something went wrong while deleting it from WordPress</strong>. <br /> You\'ll need to do that manually!';
                    }

                    break;
            }

            // $this->set('response', $response);
        }

        $user = User::find(Auth::id());

        //Object Instances
        $Group = new Group;
        $User = new User;
        $Party = new Party;
        $Device = new Device;
        $groups = $Group->ofThisUser($user->id);

        // get list of ids to check in if condition
        $gids = array();
        foreach ($groups as $group) {
            $gids[] = $group->idgroups;
        }

        if ((isset($groupid) && is_numeric($groupid)) || in_array($groupid, $gids)) {
            //$group = (object) array_fill_keys( array('idgroups') , $groupid);
            $group = Group::where('idgroups', $groupid)->first();
        // $this->set('grouplist', $Group->findList());
        } else {
            $group = $groups[0];
            unset($groups[0]);
        }
        // $this->set('userGroups', $groups);

        $groupStats = $group->getGroupStats($this->EmissionRatio);
        $allPastEvents = Party::pastEvents()
        ->with('devices.deviceCategory')
        ->where('events.group', $group->idgroups)
        ->get();

        // $this->set('pax', $participants);
        // $this->set('hours', $hours_volunteered);
        $weights = $Device->getWeights($group->idgroups);
        // $this->set('weights', $weights);

        $devices = $Device->ofThisGroup($group->idgroups);

        // more stats...

        /** co2 counters **/
        $co2_years = $Device->countCO2ByYear($group->idgroups);
        $stats = array();
        foreach ($co2_years as $year) {
            $stats[$year->year] = $year->co2;
        }

        $waste_years = $Device->countWasteByYear($group->idgroups);

        $wstats = array();
        foreach ($waste_years as $year) {
            $wstats[$year->year] = $year->waste;
        }

        $clusters = array();

        for ($i = 1; $i <= 4; $i++) {
            $cluster = $Device->countByCluster($i, $group->idgroups);
            $total = 0;
            foreach ($cluster as $state) {
                $total += $state->counter;
            }
            $cluster['total'] = $total;
            $clusters['all'][$i] = $cluster;
        }

        for ($y = date('Y', time()); $y >= 2013; $y--) {
            for ($i = 1; $i <= 4; $i++) {
                //$cluster = $Device->countByCluster($i, $group->idgroups);
                $cluster = $Device->countByCluster($i, $group->idgroups, $y);

                $total = 0;
                foreach ($cluster as $state) {
                    $total += $state->counter;
                }
                $cluster['total'] = $total;
                $clusters[$y][$i] = $cluster;
            }
        }

        // most/least stats for clusters
        $mostleast = array();
        for ($i = 1; $i <= 4; $i++) {
            $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i, $group->idgroups);
            $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i, $group->idgroups);
            $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i, $group->idgroups);
        }

        if ( ! isset($response)) {
            $response = null;
        }

        //Event tabs
        $upcoming_events = Party::upcomingEvents()
        ->where('events.group', $group->idgroups)
        ->take(5)
        ->get();

        $past_events = Party::pastEvents()
        ->where('events.group', $group->idgroups)
        ->take(5)
        ->get();

        //Checking user for validatity
        $in_group = ! empty(UserGroups::where('group', $groupid)
        ->where('user', $user->id)
        ->where(function ($query) {
            $query->where('status', '1')
            ->orWhereNull('status');
        })->first());

        $is_host_of_group = FixometerHelper::userHasEditGroupPermission($groupid, $user->id);

        $user_groups = UserGroups::where('user', Auth::user()->id)->count();
        $view_group = Group::find($groupid);

        $hasPendingInvite = ! empty(UserGroups::where('group', $groupid)
        ->where('user', $user->id)
        ->where(function ($query) {
            $query->where('status', '<>', '1')
            ->whereNotNull('status');
        })->first());

        return view('group.view', [ //host.index
            'title' => 'Host Dashboard',
            'has_pending_invite' => $hasPendingInvite,
            'showbadges' => true,
            'charts' => false,
            'response' => $response,
            'grouplist' => $Group->findList(),
            'userGroups' => $groups,
            'pax' => $groupStats['pax'],
            'hours' => $groupStats['hours'],
            'weights' => $weights,
            'group' => $group,
            'groupCo2' => $groupStats['co2'],
            'groupWaste' => $groupStats['waste'],
            'profile' => $User->getProfile($user->id),
            'upcomingparties' => $Party->findNextParties($group->idgroups),
            'allparties' => $allPastEvents,
            'devices' => $Device->ofThisGroup($group->idgroups),
            'device_count_status' => $Device->statusCount(),
            'group_device_count_status' => $Device->statusCount($group->idgroups),
            'year_data' => $co2_years,
            'bar_chart_stats' => array_reverse($stats, true),
            'waste_year_data' => $waste_years,
            'waste_bar_chart_stats' => array_reverse($wstats, true),
            'co2Total' => $Party->TotalEmission,
            'wasteTotal' => $this->TotalWeight,
            'clusters' => $clusters,
            'mostleast' => $mostleast,
            'top' => $Device->findMostSeen(1, null, $group->idgroups),
            'user' => $user,
            'upcoming_events' => $upcoming_events,
            'past_events' => $past_events,
            'EmissionRatio' => $this->EmissionRatio,
            'in_group' => $in_group,
            'is_host_of_group' => $is_host_of_group,
            'user_groups' => $user_groups,
            'view_group' => $view_group,
            'group_id' => $groupid,
        ]);
    }

    public function postSendInvite(Request $request)
    {
        $from_id = Auth::id();
        $group_name = $request->input('group_name');
        $group_id = $request->input('group_id');
        $emails = explode(',', str_replace(' ', '', $request->input('manual_invite_box')));
        $message = $request->input('message_to_restarters');

        if (empty($emails)) {
            return redirect()->back()->with('warning', 'You have not entered any emails!');
        }

        $users = User::whereIn('email', $emails)->get();

        $non_users = array_diff($emails, User::whereIn('email', $emails)->pluck('email')->toArray());
        $from = User::find($from_id);

        // users already on the platform
        foreach ($users as $user) {
            $user_group = UserGroups::where('user', $user->id)->where('group', $group_id)->first();
            // not already a confirmed member of the group
            if (is_null($user_group) || $user_group->status != '1') {
                $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
                $url = url('/').'/group/accept-invite/'.$group_id.'/'.$hash;

                // already been invited once, set a new invite hash
                if ( ! is_null($user_group)) {
                    $user_group->update([
                        'status' => $hash,
                    ]);
                // not associated with the group at all yet
                } else {
                    UserGroups::create([
                        'user' => $user->id,
                        'group' => $group_id,
                        'status' => $hash,
                        'role' => 4,
                    ]);
                }

                if ($user->invites == 1) {
                    Notification::send($user, new JoinGroup([
                        'name' => $from->name,
                        'group' => $group_name,
                        'url' => $url,
                        'message' => $message,
                    ], $user));
                } else {
                    $not_sent[] = $user->email;
                }
            } else { // already a confirmed member of the group or been sent an invite
                $not_sent[] = $user->email;
            }
        }

        // users not on the platform
        if ( ! empty($non_users)) {
            foreach ($non_users as $non_user) {
                $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
                $url = url('/user/register/'.$hash);

                $invite = Invite::create(array(
                    'record_id' => $group_id,
                    'email' => $non_user,
                    'hash' => $hash,
                    'type' => 'group',
                ));

                Notification::send($invite, new JoinGroup([
                    'name' => $from->name,
                    'group' => $group_name,
                    'url' => $url,
                    'message' => $message,
                ]));
            }
        }

        if ( ! isset($not_sent)) {
            return redirect()->back()->with('success', 'Invites sent!');
        }

        return redirect()->back()->with('warning', 'Invites sent - apart from these ('.rtrim(implode(', ', $not_sent), ', ').') who have already joined the group, have already been sent an invite, or have not opted in to receive emails');
    }

    public function confirmInvite($group_id, $hash)
    {
        // Find user/group relationship based on the invitation hash.
        $user_group = UserGroups::where('status', $hash)->where('group', $group_id)->first();
        if (empty($user_group)) {
            return redirect('/group/view/'.$group_id)->with('warning', 'Something went wrong - this invite is invalid or has expired');
        }

        // Set user as confirmed member of group.
        UserGroups::where('status', $hash)->where('group', $group_id)->update([
            'status' => 1,
        ]);

        // Send emails to hosts of group to let them know.
        // (only those that have opted in to receiving emails).
        $user = User::find($user_group->user);

        $group_hosts = User::join('users_groups', 'users_groups.user', '=', 'users.id')
                        ->where('users_groups.group', $group_id)
                          ->where('users_groups.role', 3)
                            ->select('users.*')
                              ->get();

        if ( ! empty($group_hosts)) {
            Notification::send($group_hosts, new NewGroupMember([
                'user_name' => $user->name,
                'group_name' => Group::find($group_id)->name,
                'group_url' => url('/group/view/'.$group_id),
            ]));
        }

        return redirect('/group/view/'.$user_group->group)->with('success', 'Excellent! You have joined the group');
    }

    public function edit(Request $request, $id)
    {
        $user = Auth::user();
        $Group = new Group;
        $File = new FixometerFile;

        $is_host_of_group = FixometerHelper::userHasEditGroupPermission($id, $user->id);
        if ( ! FixometerHelper::hasRole($user, 'Administrator') && ! $is_host_of_group) {
            return redirect('/user/forbidden');
        }

        if ($request->isMethod('post') && ! empty($_POST)) {
            $data = $_POST;

            // remove the extra "files" field that Summernote generates -
            unset($data['files']);
            unset($data['image']);

            if ( ! empty($data['location'])) {
                $lat_long = FixometerHelper::getLatLongFromCityCountry($data['location']);

                if ( empty($lat_long) ) {
                  $response['danger'] = 'Group could not be saved. Address not found.';
                  $group = Group::find($id);
                  $images = $File->findImages(env('TBL_GROUPS'), $id);
                  $tags = GroupTags::all();
                  $group_tags = GrouptagsGroups::where('group', $id)->pluck('group_tag')->toArray();

                  if ( ! isset($images)) {
                      $images = null;
                  }

                  return view('group.edit-group', [
                      'response' => $response,
                      'gmaps' => true,
                      'title' => 'Edit Group '.$group->name,
                      'formdata' => $group,
                      'user' => $user,
                      'images' => $images,
                      'tags' => $tags,
                      'group_tags' => $group_tags,
                      'audits' => $group->audits,
                  ]);
                } // TODO

                $latitude = $lat_long[0];
                $longitude = $lat_long[1];
                $country = $lat_long[2];
            } else {
                $latitude = null;
                $longitude = null;
                $country = null;
            }
            $data['latitude'] = $latitude;
            $data['longitude'] = $longitude;

            //Validation
            if (empty($data['name'])) {
                return redirect()->back()->with('error', 'Group name must not be empty');
            }

            if (is_null($latitude) || is_null($longitude)) {
                return redirect()->back()->with('error', 'Could not find group location - please try again!');
            }

            $update = array(
                'name' => $data['name'],
                'website' => $data['website'],
                'free_text' => $data['free_text'],
                'location' => $data['location'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country' => $country,
            );

            if (FixometerHelper::hasRole($user, 'Administrator')) {
                $update['area'] = $data['area'];
            }

            $u = Group::findOrFail($id)->update($update);

            if (FixometerHelper::hasRole($user, 'Administrator')) {
                if ( ! empty($_POST['group_tags'])) {
                    $Group->find($id)->group_tags()->sync($_POST['group_tags']);
                } else {
                    $Group->find($id)->group_tags()->sync([]);
                }
            }

            if ( ! $u) {
                $response['danger'] = 'Something went wrong. Please check the data and try again.';
                echo $response['danger'];
            } else {
                $response['success'] = 'Group updated!';

                if (isset($_FILES['file']) && ! empty($_FILES['file']) && $_FILES['file']['error'] != 4) {
                    $existing_image = FixometerHelper::hasImage($id, 'groups', true);
                    if (count($existing_image) > 0) {
                        //$Group = new Group;
                      //$Group->removeImage($id, $existing_image[0]);
                    }
                    $file = new FixometerFile;
                    $group_avatar = $file->upload('file', 'image', $id, env('TBL_GROUPS'), false, true);
                    $group_avatar = env('UPLOADS_URL').'mid_'.$group_avatar;
                } else {
                    $existing_image = FixometerHelper::hasImage($id, 'groups', true);
                    if (count($existing_image) > 0) {
                        $group_avatar = env('UPLOADS_URL').'mid_'.$existing_image[0]->path;
                    } else {
                        $group_avatar = 'null';
                    }
                }

                // Now pass group avatar via the data array
                $data['group_avatar'] = $group_avatar;

                // Get group model to pass to event handler
                $group = Group::find($id);

                // Send WordPress Notification if group approved with POSTed data
                if (isset($data['moderate']) && $data['moderate'] == 'approve') {
                    event(new ApproveGroup($group, $data));

                    // Notify nearest users.
                    if ( ! is_null($latitude) && ! is_null($longitude)) {
                        $restarters_nearby = User::nearbyRestarters($latitude, $longitude, 25)
                                                ->orderBy('name', 'ASC')
                                                  ->get();

                        Notification::send($restarters_nearby, new NewGroupWithinRadius([
                            'group_name' => $group->name,
                            'group_url' => url('/group/view/'.$id),
                        ]));
                    }
                } elseif ( ! empty($group->wordpress_post_id)) {
                    event(new EditGroup($group, $data));
                }
            }
        }

        $group = $Group->findOne($id);

        if ( ! isset($response)) {
            $response = null;
        }

        $images = $File->findImages(env('TBL_GROUPS'), $id);

        if ( ! isset($images)) {
            $images = null;
        }

        $tags = GroupTags::all();
        $group_tags = GrouptagsGroups::where('group', $id)->pluck('group_tag')->toArray();

        compact($audits = $Group->findOrFail($id)->audits);

        return view('group.edit-group', [
            'response' => $response,
            'gmaps' => true,
            'title' => 'Edit Group '.$group->name,
            'formdata' => $group,
            'user' => $user,
            'images' => $images,
            'tags' => $tags,
            'group_tags' => $group_tags,
            'audits' => $audits,
        ]);
    }

    public function updateGroupInWordpress($id, $data, $group_avatar, $latitude, $longitude)
    {
        // TODO: host.  Groups don't just have one host.  Is host
        // displayed on the front-end anywhere?
        // TODO: receiving area field from posted data.  It's currently not in the interface.
        $group = Group::where('idgroups', $id)->first();

        $custom_fields = array(
            array('key' => 'group_city',            'value' => $group->area),
            //                                    array('key' => 'group_host',            'value' => $Host->hostname),
            array('key' => 'group_website',         'value' => $data['website']),
            //array('key' => 'group_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' . $Host->path),
            array('key' => 'group_hash',            'value' => $id),
            array('key' => 'group_avatar_url',      'value' => $group_avatar),
            array('key' => 'group_latitude',        'value' => $latitude),
            array('key' => 'group_longitude',       'value' => $longitude),
        );

        /** Start WP XML-RPC **/
        $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
        $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

        $content = array(
            'post_type' => 'group',
            'post_title' => $data['name'],
            'post_content' => $data['free_text'],
            'custom_fields' => $custom_fields,
        );

        if ( ! empty($group->wordpress_post_id)) {
            // We need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
            $existingPost = $wpClient->getPost($group->wordpress_post_id);

            foreach ($existingPost['custom_fields'] as $i => $field) {
                foreach ($custom_fields as $k => $set_field) {
                    if ($field['key'] == $set_field['key']) {
                        $custom_fields[$k]['id'] = $field['id'];
                    }
                }
            }

            $content['custom_fields'] = $custom_fields;
            $wpClient->editPost($group->wordpress_post_id, $content);
        } else {
            $wpid = $wpClient->newPost($data['name'], $data['free_text'], $content);
            $group->wordpress_post_id = $wpid;
            $group->save();
        }
    }

    public function delete($id)
    {
        if (FixometerHelper::hasRole($this->user, 'Administrator')) {
            $r = $this->Group->delete($id);
            if ( ! $r) {
                $response = 'd:err';
            } else {
                $response = 'd:ok';
            }
            header('Location: /group/index/'.$response);
        } else {
            header('Location: /user/forbidden');
        }
    }

    public function deleteImage($group_id, $id, $path)
    {
        $user = Auth::user();

        $is_host_of_group = FixometerHelper::userHasEditGroupPermission($group_id, $user->id);
        if (FixometerHelper::hasRole($user, 'Administrator') || $is_host_of_group) {
            $Image = new FixometerFile;
            $Image->deleteImage($id, $path);

            return redirect()->back()->with('message', 'Thank you, the image has been deleted');
        }

        return redirect()->back()->with('message', 'Sorry, but the image can\'t be deleted');
    }

    public static function stats($id, $format = 'row')
    {
        $Device = new Device;

        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();

        $group = Group::where('idgroups', $id)->first();
        $groupStats = $group->getGroupStats($emissionRatio);

        $groupStats['format'] = $format;

        return view('group.stats', $groupStats);
    }

    public static function statsByGroupTag($group_tag_id, $format = 'row')
    {
        $Device = new Device;

        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();

        $groups = Group::join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
            ->where('grouptags_groups.group_tag', $group_tag_id)
              ->select('groups.*')
                ->get();

        $groupStats = [
            'pax' => 0,
            'hours' => 0,
            'parties' => 0,
            'co2' => 0,
            'waste' => 0,
        ];

        // Loop through all groups and increase the values for groupStats
        foreach ($groups as $group) {
            // Get stats for this particular group
            $single_group_stats = $group->getGroupStats($emissionRatio);

            // Loop through the stats whilst adding the new value to the existing value
            foreach ($single_group_stats as $key => $value) {
                $groupStats[$key] = $groupStats[$key] + $value;
            }
        }

        $groupStats['format'] = $format;

        // Return json for api.php
        if (\Request::is('api*')) {
            return response()->json($groupStats);
        }

        return view('group.stats', $groupStats);
    }

    public function getJoinGroup($group_id)
    {
        $user_id = Auth::id();
        $alreadyInGroup = UserGroups::where('group', $group_id)
        ->where('user', $user_id)
        ->where('status', 1)
        ->exists();

        if ($alreadyInGroup) {
            $response['warning'] = 'You are already part of this group';
            return redirect()->back()->with('response', $response)->with('warning', 'You are already part of this group');
        }


        try {
            $user_group = UserGroups::updateOrCreate([
                'user' => $user_id,
                'group' => $group_id,
            ], [
                'status' => 1,
                'role' => 4,
            ]);

            // A new User has joined your group
            $group = Group::find($group_id);
            $groupHostLinks = UserGroups::where('group', $group->idgroups)->where('role', 3)->get();

            foreach ($groupHostLinks as $groupHostLink) {
                $host = User::where('id', $groupHostLink->user)->first();
                $arr = [
                    'user_name' => Auth::user()->name,
                    'group_name' => $group->name,
                    'group_url' => url('/group/view/'.$group->idgroups),
                    'preferences' => url('/profile/edit/'.$host->id),
                ];
                Notification::send($host, new NewGroupMember($arr, $host));
            }

            return redirect()
                    ->back()
                    ->with('success', "You are now following {$group->name}!");

        } catch (\Exception $e) {
            $response['danger'] = 'Failed to follow this group';

            return redirect()->back()->with('response', $response)->with('warning', 'Failed to follow this group');
        }
    }

    public function imageUpload(Request $request, $id)
    {
        try {
            if (isset($_FILES) && ! empty($_FILES)) {
                $existing_image = FixometerHelper::hasImage($id, 'groups', true);
                if (count($existing_image) > 0) {
                    $Group->removeImage($id, $existing_image[0]);
                }
                $file = new FixometerFile;
                $file->upload('file', 'image', $id, env('TBL_GROUPS'), false, true, true);
            }

            return 'success - image uploaded';
        } catch (\Exception $e) {
            return 'fail - image could not be uploaded';
        }
    }

    public function ajaxDeleteImage($group_id, $id, $path)
    {
        $user = Auth::user();

        $is_host_of_group = FixometerHelper::userHasEditGroupPermission($group_id, $user->id);
        if (FixometerHelper::hasRole($user, 'Administrator') || $is_host_of_group) {
            $Image = new FixometerFile;
            $Image->deleteImage($id, $path);

            return 'Thank you, the image has been deleted';
        }

        return 'Sorry, but the image can\'t be deleted';
    }

    public function getMakeHost($group_id, $user_id, Request $request)
    {
        //Has current logged in user got permission to add host
        if ((FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userIsHostOfGroup($group_id, Auth::id())) || FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
            // Retrieve user
            $user = User::find($user_id);

            // Let's make the user a host
            $volunteer = UserGroups::where('user', $user_id)
            ->where('group', $group_id)
            ->update([
                'role' => 3,
            ]);

            // Update user's role as well, but don't demote admins!
            $update_role = User::where('id', $user_id)
            ->where('role', '>', 3)
            ->update([
                'role' => 3,
            ]);

            return redirect()->back()->with('success', 'We have made '.$user->name.' a host for this group');
        }

        return redirect()->back()->with('warning', 'Sorry, you do not have permission to do this');
    }

    public function getRemoveVolunteer($group_id, $user_id, Request $request)
    {
        //Has current logged in user got permission to remove volunteer
        if ((FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userIsHostOfGroup($group_id, Auth::id())) || FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
            // Retrieve user
            $user = User::find($user_id);

            //Let's delete the user
            $delete_user = UserGroups::where('user', $user_id)->where('group', $group_id)->delete();
            if ($delete_user == 1) {
                return redirect()->back()->with('success', 'We have removed '.$user->name.' from this group');
            }

            return redirect()->back()->with('warning', 'We are unable to remove '.$user->name.' from this group');
        }

        return redirect()->back()->with('warning', 'Sorry, you do not have permission to do this');
    }

    public function volunteersNearby($groupid)
    {
        if (isset($_GET['action']) && isset($_GET['code'])) {
            $actn = $_GET['action'];
            $code = $_GET['code'];

            switch ($actn) {
                case 'gu':
                    $response['success'] = 'Group updated.';

                    break;
                case 'pe':
                    $response['success'] = 'Party updated.';

                    break;
                case 'pc':
                    $response['success'] = 'Party created.';

                    break;
                case 'ue':
                    $response['success'] = 'Profile updated.';

                    break;
                case 'de':
                    if ($code == 200) {
                        $response['success'] = 'Party deleted.';
                    } elseif ($code == 403) {
                        $response['danger'] = 'Couldn\'t delete the party!';
                    } elseif ($code == 500) {
                        $response['warning'] = 'The party has been deleted, but <strong>something went wrong while deleting it from WordPress</strong>. <br /> You\'ll need to do that manually!';
                    }

                    break;
            }

            // $this->set('response', $response);
        }

        $user = User::find(Auth::id());

        //Object Instances
        $Group = new Group;
        $User = new User;
        $Party = new Party;
        $Device = new Device;
        $groups = $Group->ofThisUser($user->id);

        // get list of ids to check in if condition
        $gids = array();
        foreach ($groups as $group) {
            $gids[] = $group->idgroups;
        }

        if ((isset($groupid) && is_numeric($groupid)) || in_array($groupid, $gids)) {
            //$group = (object) array_fill_keys( array('idgroups') , $groupid);
            $group = Group::where('idgroups', $groupid)->first();
        // $this->set('grouplist', $Group->findList());
        } else {
            $group = $groups[0];
            unset($groups[0]);
        }
        // $this->set('userGroups', $groups);
        if ( ! is_null($group->latitude) && ! is_null($group->longitude)) {
            $restarters_nearby = User::nearbyRestarters($group->latitude, $group->longitude, 20)->orderBy('name', 'ASC')->get();
            foreach ($restarters_nearby as $restarter) {
                $membership = UserGroups::where('user', $restarter->id)->where('group', $groupid)->first();
                $restarter->notAMember = $membership == null;
                $restarter->hasPendingInvite = ! empty(UserGroups::where('group', $groupid)
                                         ->where('user', $restarter->id)
                                         ->where(function ($query) {
                                             $query->where('status', '<>', '1')
                                                 ->whereNotNull('status');
                                         })->first());
            }
        } else {
            $restarters_nearby = null;
        }

        $groupStats = $group->getGroupStats($this->EmissionRatio);
        $allPastEvents = Party::pastEvents()
                     ->with('devices.deviceCategory')
                     ->where('events.group', $group->idgroups)
                     ->get();

        $weights = $Device->getWeights($group->idgroups);
        $devices = $Device->ofThisGroup($group->idgroups);

        // more stats...

        /** co2 counters **/
        $co2_years = $Device->countCO2ByYear($group->idgroups);
        $stats = array();
        foreach ($co2_years as $year) {
            $stats[$year->year] = $year->co2;
        }

        $waste_years = $Device->countWasteByYear($group->idgroups);

        $wstats = array();
        foreach ($waste_years as $year) {
            $wstats[$year->year] = $year->waste;
        }

        $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));

        $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));

        $clusters = array();

        for ($i = 1; $i <= 4; $i++) {
            $cluster = $Device->countByCluster($i, $group->idgroups);
            $total = 0;
            foreach ($cluster as $state) {
                $total += $state->counter;
            }
            $cluster['total'] = $total;
            $clusters['all'][$i] = $cluster;
        }

        for ($y = date('Y', time()); $y >= 2013; $y--) {
            for ($i = 1; $i <= 4; $i++) {
                //$cluster = $Device->countByCluster($i, $group->idgroups);
                $cluster = $Device->countByCluster($i, $group->idgroups, $y);

                $total = 0;
                foreach ($cluster as $state) {
                    $total += $state->counter;
                }
                $cluster['total'] = $total;
                $clusters[$y][$i] = $cluster;
            }
        }

        // most/least stats for clusters
        $mostleast = array();
        for ($i = 1; $i <= 4; $i++) {
            $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i, $group->idgroups);
            $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i, $group->idgroups);
            $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i, $group->idgroups);
        }

        if ( ! isset($response)) {
            $response = null;
        }

        //Event tabs
        $upcoming_events = Party::upcomingEvents()
                            ->where('events.group', $group->idgroups)
                              ->take(5)
                                ->get();

        $past_events = Party::pastEvents()
                            ->where('events.group', $group->idgroups)
                              ->take(5)
                                ->get();

        //Checking user for validatity
        $in_group = ! empty(UserGroups::where('group', $groupid)
                          ->where('user', $user->id)
                            ->where(function ($query) {
                                $query->where('status', '1')
                                  ->orWhereNull('status');
                            })->first());

        $is_host_of_group = FixometerHelper::userHasEditGroupPermission($groupid, $user->id);

        $user_groups = UserGroups::where('user', Auth::user()->id)->count();
        $view_group = Group::find($groupid);

        $hasPendingInvite = ! empty(UserGroups::where('group', $groupid)
                                             ->where('user', $user->id)
                                             ->where(function ($query) {
                                                 $query->where('status', '<>', '1')
                                                       ->whereNotNull('status');
                                             })->first());

        return view('group.nearby', [ //host.index
            'title' => 'Host Dashboard',
            'has_pending_invite' => $hasPendingInvite,
            'showbadges' => true,
            'charts' => false,
            'response' => $response,
            'grouplist' => $Group->findList(),
            'userGroups' => $groups,
            'pax' => $groupStats['pax'],
            'hours' => $groupStats['hours'],
            'weights' => $weights,
            'group' => $group,
            'groupCo2' => $groupStats['co2'],
            'groupWaste' => $groupStats['waste'],
            'profile' => $User->getProfile($user->id),
            'upcomingparties' => $Party->findNextParties($group->idgroups),
            'allparties' => $allPastEvents,
            'devices' => $Device->ofThisGroup($group->idgroups),
            'device_count_status' => $Device->statusCount(),
            'group_device_count_status' => $Device->statusCount($group->idgroups),
            'year_data' => $co2_years,
            'bar_chart_stats' => array_reverse($stats, true),
            'waste_year_data' => $waste_years,
            'waste_bar_chart_stats' => array_reverse($wstats, true),
            'co2Total' => $Party->TotalEmission,
            'co2ThisYear' => $co2ThisYear[0]->co2,
            'wasteTotal' => $this->TotalWeight,
            'wasteThisYear' => $wasteThisYear[0]->waste,
            'clusters' => $clusters,
            'mostleast' => $mostleast,
            'top' => $Device->findMostSeen(1, null, $group->idgroups),
            'user' => $user,
            'upcoming_events' => $upcoming_events,
            'past_events' => $past_events,
            'EmissionRatio' => $Party->EmissionRatio,
            'in_group' => $in_group,
            'is_host_of_group' => $is_host_of_group,
            'user_groups' => $user_groups,
            'view_group' => $view_group,
            'group_id' => $groupid,
            'restarters_nearby' => $restarters_nearby,
        ]);
    }

    public function inviteNearbyRestarter($groupId, $userId)
    {
        $user_group = UserGroups::where('user', $userId)->where('group', $groupId)->first();
        $user = User::where('id', $userId)->first();

        // not already a confirmed member of the group
        if (is_null($user_group) || $user_group->status != '1') {
            $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
            $url = url('/').'/group/accept-invite/'.$groupId.'/'.$hash;

            // already been invited once, set a new invite hash
            if ( ! is_null($user_group)) {
                $user_group->update([
                    'status' => $hash,
                ]);
            // not associated with the group at all yet
            } else {
                UserGroups::create([
                    'user' => $userId,
                    'group' => $groupId,
                    'status' => $hash,
                    'role' => 4,
                ]);
            }

            try {
                $from = Auth::user();
                $group = Group::where('idgroups', $groupId)->first();
                if ($user->invites == 1) {
                    Notification::send($user, new JoinGroup([
                        'name' => $from->name,
                        'group' => $group->name,
                        'url' => $url,
                        'message' => null,
                    ], $user));
                } else {
                    $not_sent[] = $user->email;
                }
            } catch (\Exception $ex) {
                Log::error('An error occurred while sending invitation to nearby Restarter:'.$ex->getMessage());
            }
        } else { // already a confirmed member of the group or been sent an invite
            $not_sent[] = $user->email;
        }

        return redirect('/group/nearby/'.$groupId)->with('success', $user->name.' has been invited');
    }

    /**
     * [confirmCodeInvite description]
     *
     * @author Christopher Kelker - @date 2019-03-25
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @param   Request     $request
     * @param   [type]      $code
     * @return  [type]
     */
    public function confirmCodeInvite(Request $request, $code)
    {
        // Variables
        $group = Group::where('shareable_code', $code)->first();
        $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);

        // Validate a record exists with the Group code
        if (empty($group)) {
            abort(404);
        }

        // Create a new Invite record
        $invite = Invite::create([
            'record_id' => $group->idgroups,
            'email' => '',
            'hash' => $hash,
            'type' => 'group',
        ]);

        // Push this into a session variable to find by the Group prefix
        session()->push('groups.'.$code, $hash);

        return redirect('/user/register')->with('auth-for-invitation', __('auth.login_before_using_shareable_link', ['login_url' => url('/login')]));
    }

    /**
     * [getGroupsByKey description]
     * Find Groups from User Access Key,
     * If the Groups are not found, through 404 error,
     * Else return the Groups JSON data
     *
     * @author Christopher Kelker - @date 2019-03-26
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @param   Request     $request
     * @param   [type]      $api_key
     * @return  [type]
     */
    public function getGroupsByKey(Request $request, $api_key)
    {
        // Find User by Access Key
        $user = User::where('api_key', $api_key)->first();

        // Get Emission Ratio
        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();

        if (empty($user->groupTag) || is_null($user->groupTag)) {
            return abort(404, 'No groups tags found.');
        }

        $group_tags_groups = $user->groupTag->groupTagGroups;

        // If Group is not found, through 404 error
        if ($user->groupTag->groupTagGroups->isEmpty()
        || $user->groupTag->groupTagGroups->count() <= 0) {
            return abort(404, 'No groups found.');
        }

        // New Collection Instance
        $collection = collect([]);

        foreach ($group_tags_groups as $key => $group_tags_group) {
            $group = $group_tags_group->theGroup;
            if ( ! empty($group)) {
                $collection->push([
                    'id' => $group->idgroups,
                    'name' => $group->name,
                    'location' => [
                        'value' => $group->location,
                        'country' => $group->country,
                        'latitude' => $group->latitude,
                        'longitude' => $group->longitude,
                    ],
                    'website' => $group->website,
                    'description' => $group->free_text,
                    'image_url' => $group->groupImagePath(),
                    'upcoming_parties' => $upcoming_parties_collection = collect([]),
                    'past_parties' => $past_parties_collection = collect([]),
                    'impact' => [
                        'volunteers' => $group->getGroupStats($emissionRatio)['pax'],
                        'hours_volunteered' => $group->getGroupStats($emissionRatio)['hours'],
                        'parties_thrown' => $group->getGroupStats($emissionRatio)['parties'],
                        'waste_prevented' => $group->getGroupStats($emissionRatio)['waste'],
                        'co2_emissions_prevented' => $group->getGroupStats($emissionRatio)['co2'],
                    ],
                    'widgets' => [
                        'headline_stats' => url("/group/stats/{$group->idgroups}"),
                        'co2_equivalence_visualisation' => url("/outbound/info/group/{$group->idgroups}/manufacture"),
                    ],
                ]);

                foreach ($group->upcomingParties() as $key => $event) {
                    $upcoming_parties_collection->push([
                        'event_id' => $event->idevents,
                        'event_date' => $event->event_date,
                        'start_time' => $event->start,
                        'end_time' => $event->end,
                        'name' => $event->venue,
                        'location' => [
                            'value' => $event->location,
                            'latitude' => $event->latitude,
                            'longitude' => $event->longitude,
                        ],
                    ]);
                }

                foreach ($group->pastParties() as $key => $event) {
                    $past_parties_collection->push([
                        'event_id' => $event->idevents,
                        'event_date' => $event->event_date,
                        'start_time' => $event->start,
                        'end_time' => $event->end,
                        'name' => $event->venue,
                        'location' => [
                            'value' => $event->location,
                            'latitude' => $event->latitude,
                            'longitude' => $event->longitude,
                        ],
                    ]);
                }
            }
        }

        return $collection;
    }

    /**
     * [getGroupByKeyAndId description]
     * Find Group from User Access Key and Group ID,
     * If the Group is not found, through 404 error,
     * Else return the Group JSON data
     *
     * @author Christopher Kelker - @date 2019-03-26
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @param   Request     $request
     * @param   [type]      $api_key
     * @param   Group       $group
     * @return  [type]
     */
    public function getGroupByKeyAndId(Request $request, $api_key, Group $group, $date_from = null, $date_to = null)
    {
        // Get Group from Access Key and Group ID
        $group_tags_group = User::where('api_key', $api_key)->first()
        ->groupTag->groupTagGroups->where('group', $group->idgroups)->first();

        // If Group is not found, through 404 error
        if (empty($group_tags_group)) {
            return abort(404, 'No groups found.');
        }

        // If Event is found but is not the of the date specified
        $exclude_parties = [];
        if ( ! empty($date_from) && ! empty($date_to)) {
            foreach ($group->parties as $key => $party) {
                if ( ! FixometerHelper::validateBetweenDates($party->event_date, $date_from, $date_to)) {
                    $exclude_parties[] = $party->idevents;
                }
            }
        }

        // Get Emission Ratio
        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();

        // New Collection Instance
        $collection = collect([
            'id' => $group->idgroups,
            'name' => $group->name,
            'location' => [
                'value' => $group->location,
                'country' => $group->country,
                'latitude' => $group->latitude,
                'longitude' => $group->longitude,
            ],
            'website' => $group->website,
            'description' => $group->free_text,
            'image_url' => $group->groupImagePath(),
            'upcoming_parties' => $upcoming_parties_collection = collect([]),
            'past_parties' => $past_parties_collection = collect([]),
            'impact' => [
                'volunteers' => $group->getGroupStats($emissionRatio)['pax'],
                'hours_volunteered' => $group->getGroupStats($emissionRatio)['hours'],
                'parties_thrown' => $group->getGroupStats($emissionRatio)['parties'],
                'waste_prevented' => $group->getGroupStats($emissionRatio)['waste'],
                'co2_emissions_prevented' => $group->getGroupStats($emissionRatio)['co2'],
            ],
            'widgets' => [
                'headline_stats' => url("/group/stats/{$group->idgroups}"),
                'co2_equivalence_visualisation' => url("/{$group->idgroups}/manufacture"),
            ],
        ]);

        foreach ($group->upcomingParties($exclude_parties) as $key => $event) {
            $upcoming_parties_collection->push([
                'event_id' => $event->idevents,
                'event_date' => $event->event_date,
                'start_time' => $event->start,
                'end_time' => $event->end,
                'name' => $event->venue,
                'location' => [
                    'value' => $event->location,
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                ],
            ]);
        }

        foreach ($group->pastParties($exclude_parties) as $key => $event) {
            $past_parties_collection->push([
                'event_id' => $event->idevents,
                'event_date' => $event->event_date,
                'start_time' => $event->start,
                'end_time' => $event->end,
                'name' => $event->venue,
                'location' => [
                    'value' => $event->location,
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                ],
            ]);
        }

        return $collection;
    }
}
