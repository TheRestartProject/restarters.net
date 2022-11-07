<?php

namespace App\Http\Controllers;

use App\Device;
use App\Events\ApproveGroup;
use App\Events\EditGroup;
use App\Events\UserFollowedGroup;
use App\EventsUsers;
use App\Group;
use App\GroupNetwork;
use App\GroupTags;
use App\GrouptagsGroups;
use App\Helpers\Fixometer;
use App\Helpers\Geocoder;
use App\Invite;
use App\Network;
use App\Notifications\AdminModerationGroup;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\Notifications\NewGroupWithinRadius;
use App\Notifications\GroupConfirmed;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use Auth;
use DB;
use FixometerFile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Notification;

class GroupController extends Controller
{
    private function indexVariations($tab, $network)
    {
        //Get current logged in user
        $user = Auth::user();

        // Get all groups
        $groups = Group::with(['networks'])
            ->orderBy('name', 'ASC')
            ->get();

        // Get all group tags
        $all_group_tags = GroupTags::all();
        $networks = Network::all();

        // Look for groups we have joined, not just been invited to.  We have to explicitly test on deleted_at because
        // the normal filtering out of soft deletes won't happen for joins.
        $your_groups =array_column(Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->leftJoin('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->where('users_groups.status', 1)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups')
            ->get()
            ->toArray(), 'idgroups');

        // We pass a high limit to the groups nearby; there is a distance limit which will normally kick in first.
        $groups_near_you = array_column($user->groupsNearby(1000), 'idgroups');

        return view('group.index', [
            'groups' => GroupController::expandGroups($groups, $your_groups, $groups_near_you),
            'your_area' => $user->location,
            'tab' => $tab,
            'network' => $network,
            'networks' => $networks,
            'all_group_tags' => $all_group_tags,
        ]);
    }

    public function all()
    {
        return $this->indexVariations('all', null);
    }

    public function mine()
    {
        return $this->indexVariations('mine', null);
    }

    public function nearby()
    {
        return $this->indexVariations('nearby', null);
    }

    public function network($id)
    {
        return $this->indexVariations('all', $id);
    }

    public function create(Request $request, $networkId = null)
    {
        $geocoder = new \App\Helpers\Geocoder();

        $idGroup = false;

        $user = User::find(Auth::id());

        // Only administrators can add groups
        if (Fixometer::hasRole($user, 'Restarter')) {
            return redirect('/user/forbidden');
        }

        if ($request->isMethod('post') && ! empty($request->post())) {
            $error = [];

            $name = $request->input('name');
            $website = $request->input('website');
            $location = $request->input('location');
            $text = $request->input('free_text');
            $timezone = $request->input('timezone');
            $phone = $request->input('phone');

            if (empty($name)) {
                $error['name'] = 'Please input a name.';
            }

            if ($timezone && !in_array($timezone, \DateTimeZone::listIdentifiers())) {
                $error['timezone'] =  __('partials.validate_timezone');
                $response['warning'] = $error['timezone'];
            }

            if (! empty($location)) {
                $geocoded = $geocoder->geocode($location);

                if (empty($geocoded)) {
                    error_log("Geocode failed $location");
                    $response['danger'] = __('groups.geocode_failed');
                    \Sentry\CaptureMessage($response['danger']);

                    return view('group.create', [
                        'title' => 'New Group',
                        'gmaps' => true,
                        'response' => $response,
                    ]);
                }

                $latitude = $geocoded['latitude'];
                $longitude = $geocoded['longitude'];
                $country = $geocoded['country'];
            } else {
                $latitude = null;
                $longitude = null;
                $country = null;
            }

            if (empty($error)) {
                $data = [
                    'name' => $name,
                    'website' => $website,
                    'location' => $location,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'country' => $country,
                    'free_text' => $text,
                    'shareable_code' => Fixometer::generateUniqueShareableCode(\App\Group::class, 'shareable_code'),
                    'timezone' => $timezone,
                    'phone' => $phone,
                ];

                try {
                    $group = Group::create($data);
                    $idGroup = $group->idgroups;

                    $network = Network::find(session()->get('repair_network'));
                    $network->addGroup($group);

                    if (is_numeric($idGroup) && $idGroup !== false) {
                        $idGroup = Group::find($idGroup);
                        $idGroup = $idGroup->idgroups;

                        $response['success'] = 'Group created correctly.';

                        //Associate current logged in user as a host
                        UserGroups::create([
                            'user' => Auth::user()->id,
                            'group' => $idGroup,
                            'status' => 1,
                            'role' => 3,
                        ]);

                        // Notify relevant admins
                        $notify_admins = Fixometer::usersWhoHavePreference('admin-moderate-group');
                        Notification::send($notify_admins, new AdminModerationGroup([
                            'group_name' => $name,
                            'group_url' => url('/group/edit/'.$idGroup),
                        ]));

                        if (isset($_FILES) && ! empty($_FILES)) {
                            $file = new FixometerFile;
                            $file->upload('file', 'image', $idGroup, env('TBL_GROUPS'), false, true);
                        }
                    } else {
                        $response['danger'] = 'Group could <strong>not</strong> be created. Something went wrong with the database.';
                        \Sentry\CaptureMessage($response['danger']);
                    }
                } catch (QueryException $e) {
                    $errorCode = $e->errorInfo[1];
                    if ($errorCode == 1062) {
                        // This is a legitimate user error, and will be displayed to them, so we don't need to log it.
                        $response['danger'] = __('groups.duplicate', [
                            'name' => $name,
                        ]);
                    } else {
                        $response['danger'] = __('groups.database_error');
                        \Sentry\CaptureMessage($response['danger']);
                    }
                }
            } else {
                $response['danger'] = __('groups.create_failed');
                \Sentry\CaptureMessage($response['danger']);
            }

            if (! isset($response)) {
                $response = null;
            }

            if (! isset($error)) {
                $error = null;
            }

            if (! isset($_POST)) {
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
                'selectedNetworkId' => $networkId,
            ]);
        }

        return view('group.create', [
            'title' => 'New Group',
            'gmaps' => true,
            'selectedNetworkId' => $networkId,
        ]);
    }

    private function expandVolunteers($volunteers)
    {
        $ret = [];

        foreach ($volunteers as $volunteer) {
            $volunteer['volunteer'] = $volunteer->volunteer;

            if ($volunteer['volunteer']) {
                $volunteer['userSkills'] = $volunteer->volunteer->userSkills->all();

                foreach ($volunteer['userSkills'] as $skill) {
                    // Force expansion
                    $skill->skillName->skill_name;
                }

                $volunteer['fullName'] = $volunteer->name;
                $volunteer['profilePath'] = '/uploads/thumbnail_'.$volunteer->volunteer->getProfile($volunteer->volunteer->id)->path;
                $ret[] = $volunteer;
            }
        }

        return $ret;
    }

    public function view($groupid)
    {
        $user = User::find(Auth::id());

        //Object Instances
        $Group = new Group;
        $User = new User;
        $Party = new Party;
        $Device = new Device;
        $groups = $Group->ofThisUser($user->id);

        // get list of ids to check in if condition
        $gids = [];
        foreach ($groups as $group) {
            $gids[] = $group->idgroups;
        }

        $group = null;

        if ((isset($groupid) && is_numeric($groupid)) || in_array($groupid, $gids)) {
            $group = Group::where('idgroups', $groupid)->first();
        } elseif (count($groups)) {
            $group = $groups[0];
            unset($groups[0]);
        }

        if (! $group) {
            return abort(404, 'Invalid group.');
        }

        $allPastEvents = Party::past()
            ->with('devices.deviceCategory')
            ->forGroup($group->idgroups)
            ->get();

        $Device->ofThisGroup($group->idgroups);

        $clusters = [];

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
        $mostleast = [];
        for ($i = 1; $i <= 4; $i++) {
            $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i, $group->idgroups);
            $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i, $group->idgroups);
            $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i, $group->idgroups);
        }

        if (! isset($response)) {
            $response = null;
        }

        //Event tabs
        $upcoming_events = Party::future()
            ->forGroup($group->idgroups)
            ->get();
        $active_events = Party::active()
            ->forGroup($group->idgroups)
            ->get();
        $past_events = Party::past()
            ->forGroup($group->idgroups)
            ->get();

        //Checking user for validity
        $in_group = ! empty(UserGroups::where('group', $groupid)
            ->where('user', $user->id)
            ->where(function ($query) {
                $query->where('status', '1')
                    ->orWhereNull('status');
            })->first());

        $is_host_of_group = Fixometer::userHasEditGroupPermission($groupid, $user->id);
        $isCoordinatorForGroup = $user->isCoordinatorForGroup($group);

        $user_groups = UserGroups::where('user', Auth::user()->id)->count();
        $view_group = Group::find($groupid);
        $view_group->allConfirmedVolunteers = $this->expandVolunteers($view_group->allConfirmedVolunteers);

        $pendingInvite = UserGroups::where('group', $groupid)
        ->where('user', $user->id)
        ->where(function ($query) {
            $query->where('status', '<>', '1')
            ->whereNotNull('status');
        })->first();
        $hasPendingInvite = ! empty($pendingInvite) ? $pendingInvite['status'] : false;

        $groupStats = $group->getGroupStats();

        $expanded_events = [];

        // Send these to getEventStats() to speed things up a bit.
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        foreach (array_merge($upcoming_events->all(), $active_events->all(), $past_events->all()) as $event) {
            $expanded_event = \App\Http\Controllers\PartyController::expandEvent($event, $group);
            // TODO: here we seem to expect group to be the group id, not the group object.
            // expandEvent seems to expect the object.
            $expanded_event['group'] = $event->group;
            $expanded_events[] = $expanded_event;
        }

        return view('group.view', [ //host.index
            'title' => 'Host Dashboard',
            'has_pending_invite' => $hasPendingInvite,
            'showbadges' => true,
            'charts' => false,
            'response' => $response,
            'grouplist' => $Group->findList(),
            'userGroups' => $groups,
            'group' => $group,
            'profile' => $User->getProfile($user->id),
            'allparties' => $allPastEvents,
            'devices' => $Device->ofThisGroup($group->idgroups),
            'device_count_status' => $Device->statusCount(),
            'group_device_count_status' => $Device->statusCount($group->idgroups),
            'group_stats' => $groupStats,
            'expanded_events' => $expanded_events,
            'clusters' => $clusters,
            'mostleast' => $mostleast,
            'top' => $Device->findMostSeen(1, null, $group->idgroups),
            'user' => $user,
            'upcoming_events' => $upcoming_events,
            'past_events' => $past_events,
            'in_group' => $in_group,
            'is_host_of_group' => $is_host_of_group,
            'isCoordinatorForGroup' => $isCoordinatorForGroup,
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
            \Sentry\CaptureMessage('You have not entered any emails!');
            return redirect()->back()->with('warning', 'You have not entered any emails!');
        }

        $invalid = [];

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid[] = $email;
            }
        }

        if (count($invalid)) {
            return redirect()->back()->with('warning', 'Invalid emails were entered, so no notifications were sent - please send your invitation again.  The invalid emails were: ' . implode(', ', $invalid));
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
                if (! is_null($user_group)) {
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
        if (! empty($non_users)) {
            foreach ($non_users as $non_user) {
                $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
                $url = url('/user/register/'.$hash);

                $invite = Invite::create([
                    'record_id' => $group_id,
                    'email' => $non_user,
                    'hash' => $hash,
                    'type' => 'group',
                ]);

                Notification::send($invite, new JoinGroup([
                    'name' => $from->name,
                    'group' => $group_name,
                    'url' => $url,
                    'message' => $message,
                ]));
            }
        }

        if (! isset($not_sent)) {
            return redirect()->back()->with('success', 'Invites sent!');
        }

        // Don't log to Sentry - legitimate user error.
        return redirect()->back()->with('warning', 'Invites sent - apart from these ('.rtrim(implode(', ', $not_sent), ', ').') who have already joined the group, have already been sent an invite, or have not opted in to receive emails');
    }

    public function confirmInvite($group_id, $hash)
    {
        // Find user/group relationship based on the invitation hash.
        $user_group = UserGroups::where('status', $hash)->where('group', $group_id)->first();
        if (empty($user_group)) {
            \Sentry\CaptureMessage('Something went wrong - this invite is invalid or has expired');
            return redirect('/group/view/'.intval($group_id))->with('warning', 'Something went wrong - this invite is invalid or has expired');
        }

        // Set user as confirmed member of group.
        UserGroups::where('status', $hash)->where('group', $group_id)->update([
            'status' => 1,
        ]);

        // Send emails to hosts of group to let them know.
        // (only those that have opted in to receiving emails).
        $user = User::find($user_group->user);
        $group = Group::find($group_id);

        $group_hosts = $group->membersHosts();

        if ($group_hosts->count()) {
            Notification::send($group_hosts->get(), new NewGroupMember([
                'user_name' => $user->name,
                'group_name' => $group->name,
                'group_url' => url('/group/view/'.$group_id),
            ]));
        }

        return redirect('/group/view/'.$user_group->group)->with('success', 'Excellent! You have joined the group');
    }

    public function edit(Request $request, $id, Geocoder $geocoder)
    {
        $user = Auth::user();
        $Group = new Group;
        $File = new FixometerFile;

        $group = Group::findOrFail($id);
        $is_host_of_group = Fixometer::userHasEditGroupPermission($id, $user->id);
        $isCoordinatorForGroup = $user->isCoordinatorForGroup($group);

        if (! Fixometer::hasRole($user, 'Administrator') && ! $is_host_of_group && ! $isCoordinatorForGroup) {
            abort(403);
        }

        $networks = Network::all();

        if ($request->isMethod('post') && ! empty($request->post())) {
            $data = $request->post();

            // Remove some inputs.  Probably these aren't present any more, but it does no harm to ensure that.
            unset($data['files']);
            unset($data['image']);

            if (! empty($data['location'])) {
                $geocoded = $geocoder->geocode($data['location']);

                if (empty($geocoded)) {
                    $response['danger'] = __('groups.geocode_failed');
                    \Sentry\CaptureMessage($response['danger']);
                    $group = Group::find($id);
                    $images = $File->findImages(env('TBL_GROUPS'), $id);
                    $tags = GroupTags::all();
                    $group_tags = GrouptagsGroups::where('group', $id)->pluck('group_tag')->toArray();
                    $group_networks = $group->networks->pluck('id')->toArray();

                    if (! isset($images)) {
                        $images = null;
                    }

                    return view('group.edit', [
                        'response' => $response,
                        'gmaps' => true,
                        'title' => 'Edit Group '.$group->name,
                        'formdata' => $group,
                        'user' => $user,
                        'images' => $images,
                        'tags' => $tags,
                        'group_tags' => $group_tags,
                        'networks' => $networks,
                        'group_networks' => $group_networks,
                        'audits' => $group->audits,
                    ]);
                }

                $latitude = $geocoded['latitude'];
                $longitude = $geocoded['longitude'];
                $country = $geocoded['country'];
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
                // TODO This case looks like it's worth considering.
                //return redirect()->back()->with('error', 'Could not find group location - please try again!');
            }

            $old_zone = $group->timezone;
            $update = [
                'name' => $data['name'],
                'website' => array_key_exists('website', $data) ? $data['website'] : null,
                'phone' => array_key_exists('phone', $data) ? $data['phone'] : null,
                'free_text' => $data['free_text'],
                'location' => array_key_exists('location', $data) ? $data['location'] : null,
                'timezone' => array_key_exists('timezone', $data) ? $data['timezone'] : null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country' => $country,
            ];

            if ($user->hasRole('Administrator') || $user->hasRole('NetworkCoordinator')) {
                $update['area'] = $data['area'];
                $update['postcode'] = $data['postcode'];
            }

            $u = $group->update($update);

            if ($update['timezone'] != $old_zone) {
                // The timezone of the group has changed.  Update the zone of any future events.  This happens
                // sometimes when a group is created and events are created before the group is approved (and therefore
                // before the admin has a chance to set the zone on the group.
                foreach ($group->upcomingParties() as $party) {
                    $party->update([
                        'timezone' => $update['timezone']
                    ]);
                }
            }

            if (Fixometer::hasRole($user, 'Administrator')) {
                if (! empty($_POST['group_tags'])) {
                    $Group->find($id)->group_tags()->sync($_POST['group_tags']);
                } else {
                    $Group->find($id)->group_tags()->sync([]);
                }

                if (! empty($request->input('group_networks'))) {
                    Group::find($id)->networks()->sync($request->input('group_networks'));
                } else {
                    Group::find($id)->networks()->sync([]);
                }
            }

            if (! $u) {
                $response['danger'] = 'Something went wrong. Please check the data and try again.';
                \Sentry\CaptureMessage($response['danger']);
                echo $response['danger'];
            } else {
                $response['success'] = 'Group updated!';

                if (isset($_FILES['file']) && ! empty($_FILES['file']) && $_FILES['file']['error'] != 4) {
                    $existing_image = Fixometer::hasImage($id, 'groups', true);
                    if (! empty($existing_image)) {
                        // TODO This case looks like it's worth considering.
                        //$Group = new Group;
                        //$Group->removeImage($id, $existing_image[0]);
                    }
                    $file = new FixometerFile;
                    $group_avatar = $file->upload('file', 'image', $id, env('TBL_GROUPS'), false, true);
                    $group_avatar = env('UPLOADS_URL').'mid_'.$group_avatar;
                } else {
                    $existing_image = Fixometer::hasImage($id, 'groups', true);
                    if (! empty($existing_image)) {
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

                    // Notify the creator, as long as it's not the current user.
                    $creator = User::find(UserGroups::where('group', $id)->first()->user);

                    if ($creator->id != Auth::user()->id) {
                        Notification::send($creator, new GroupConfirmed($group));
                    }

                    // Notify nearest users.
                    if (! is_null($latitude) && ! is_null($longitude)) {
                        $restarters_nearby = User::nearbyRestarters($latitude, $longitude, 25)
                            ->orderBy('name', 'ASC')
                            ->get();

                        Notification::send($restarters_nearby, new NewGroupWithinRadius([
                            'group_name' => $group->name,
                            'group_url' => url('/group/view/'.$id),
                        ]));
                    }
                } else {
                    event(new EditGroup($group, $data));
                }
            }
        }

        $group = $Group->findOne($id);

        if (! isset($response)) {
            $response = null;
        }

        $images = $File->findImages(env('TBL_GROUPS'), $id);

        if (! isset($images)) {
            $images = null;
        }

        $tags = GroupTags::all();
        $group_tags = GrouptagsGroups::where('group', $id)->pluck('group_tag')->toArray();
        $group_networks = Group::find($id)->networks->pluck('id')->toArray();

        // TODO The return value is ignored, but this may be a bug.
        compact($audits = $Group->findOrFail($id)->audits);

        return view('group.edit', [
            'response' => $response,
            'gmaps' => true,
            'title' => 'Edit Group '.$group->name,
            'formdata' => $group,
            'user' => $user,
            'images' => $images,
            'tags' => $tags,
            'group_tags' => $group_tags,
            'networks' => $networks,
            'group_networks' => $group_networks,
            'audits' => $audits,
        ]);
    }

    public function delete($id)
    {
        $group = Group::where('idgroups', $id)->first();

        $name = $group->name;

        if (Auth::user()->hasRole('Administrator') && $group->canDelete()) {
            // We know we can delete the group; if it has any past events they must be empty, so delete all
            // events (including future).
            $allEvents = Party::withTrashed()->where('events.group', $id)->get();

            foreach ($allEvents as $event) {
                // Delete any users - these are not cascaded in the DB.
                $users = EventsUsers::where('event', $event->idevents)->get();

                foreach ($users as $user) {
                    // Need to force delete to get rid of the row and avoid constraint violations.
                    $user->forceDelete();
                }

                $event->forceDelete();
            }

            $r = $group->delete($id);

            if (! $r) {
                return redirect('/user/forbidden');
            } else {
                return redirect('/group')->with('success', __('groups.delete_succeeded', [
                    'name' => $name,
                ]));
            }
        } else {
            return redirect('/user/forbidden');
        }
    }

    public static function expandGroups($groups, $your_groupids, $nearby_groupids)
    {
        $ret = [];

        if ($groups) {
            foreach ($groups as $group) {
                $group_image = $group->groupImage;

                $event = $group->getNextUpcomingEvent();

                // We want to return the distance from our own location.
                $distance = null;
                $grouplat = $group->latitude;
                $grouplng = $group->longitude;
                $userlat = Auth::user()->latitude;
                $userlng = Auth::user()->longitude;

                if ($grouplat !== null && $grouplng !== null && $userlat !== null && $userlng !== null) {
                    if ($grouplat == $userlat && $grouplng == $userlng) {
                        $distance = 0;
                    } else {
                        $distance = 6371 * acos( cos(deg2rad($userlat)) * cos(deg2rad($grouplat)) * cos(deg2rad($grouplng) -
                                                                                                        deg2rad($userlng)) + sin(deg2rad($userlat) ) * sin(deg2rad($grouplat)));
                    }
                }

                $ret[] = [
                    'idgroups' => $group->idgroups,
                    'name' => $group->name,
                    'image' => (is_object($group_image) && is_object($group_image->image)) ?
                        asset('uploads/mid_'.$group_image->image->path) : null,
                    'location' => [
                        'location' => rtrim($group->location),
                        'country' => $group->country,
                        'distance' => $distance,
                    ],
                    'next_event' => $event ? $event->event_date_local : null,
                    'all_restarters_count' => $group->all_restarters_count,
                    'all_hosts_count' => $group->all_hosts_count,
                    'all_confirmed_restarters_count' => $group->all_confirmed_restarters_count,
                    'all_confirmed_hosts_count' => $group->all_confirmed_hosts_count,
                    'networks' => \Illuminate\Support\Arr::pluck($group->networks, 'id'),
                    'group_tags' => $group->group_tags()->get()->pluck('id'),
                    'following' => in_array($group->idgroups, $your_groupids),
                    'nearby' => in_array($group->idgroups, $nearby_groupids),
                ];
            }
        }

        return $ret;
    }

    public static function stats($id, $format = 'row')
    {
        $group = Group::where('idgroups', $id)->first();
        if (! $group) {
            return abort(404, 'Invalid group id');
        }

        $groupStats = $group->getGroupStats();

        $groupStats['format'] = $format;

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
            \Sentry\CaptureMessage($response['warning']);

            return redirect()->back()->with('response', $response)->with('warning', $response['warning']);
        }

        try {
            UserGroups::updateOrCreate([
                'user' => $user_id,
                'group' => $group_id,
            ], [
                'status' => 1,
                'role' => 4,
            ]);

            $user = Auth::user();
            $group = Group::find($group_id);

            event(new UserFollowedGroup($user, $group));

            // A new User has joined your group
            $groupHostLinks = UserGroups::where('group', $group->idgroups)->where('role', Role::HOST)->get();

            foreach ($groupHostLinks as $groupHostLink) {
                $host = User::where('id', $groupHostLink->user)->first();
                $arr = [
                    'user_name' => Auth::user()->name,
                    'group_name' => $group->name,
                    'group_url' => url('/group/view/'.$group->idgroups)
                ];
                Notification::send($host, new NewGroupMember($arr, $host));
            }

            return redirect()
                ->back()
                ->with('success', __('groups.now_following', [
                    'name' => $group->name,
                    'link' => url('/group/view/'.$group->idgroups),
                ]));
        } catch (\Exception $e) {
            $response['danger'] = 'Failed to follow this group';
            \Sentry\CaptureMessage($response['danger']);

            return redirect()->back()->with('response', $response)->with('warning', 'Failed to follow this group');
        }
    }

    // TODO: This is not currently used, so far as I can tell, even though it's referenced from a route.  But
    // something like this ought to exist, for when we Vue-ify the group edit page.
    public function imageUpload(Request $request, $id)
    {
        try {
            if (isset($_FILES) && ! empty($_FILES)) {
                $existing_image = Fixometer::hasImage($id, 'groups', true);
                if (! empty($existing_image)) {
                    Fixometer::removeImage($id, env('TBL_GROUPS'), $existing_image[0]);
                }
                $file = new FixometerFile;
                $file->upload('file', 'image', $id, env('TBL_GROUPS'), false, true, true);
            }

            return 'success - image uploaded';
        } catch (\Exception $e) {
            return 'fail - image could not be uploaded ' . $e->getMessage();
        }
    }

    public function ajaxDeleteImage($group_id, $id, $path)
    {
        $user = Auth::user();

        $is_host_of_group = Fixometer::userHasEditGroupPermission($group_id, $user->id);
        if (Fixometer::hasRole($user, 'Administrator') || $is_host_of_group) {
            $Image = new FixometerFile;
            $Image->deleteImage($id, $path);

            return 'Thank you, the image has been deleted';
        }

        return 'Sorry, but the image can\'t be deleted';
    }

    public function getMakeHost($group_id, $user_id, Request $request)
    {
        // Has current logged in user got permission to add host?
        // - Is a host of the group.
        // - Is a network coordinator of a network which the group is in.
        // - Is an Administrator
        $group = Group::findOrFail($group_id);
        $loggedInUser = Auth::user();

        if (($loggedInUser->hasRole('Host') && Fixometer::userIsHostOfGroup($group_id, $loggedInUser->id)) ||
            $loggedInUser->isCoordinatorForGroup($group) ||
            $loggedInUser->hasRole('Administrator')
        ) {
            $user = User::find($user_id);

            $group->makeMemberAHost($user);

            return redirect()->back()->with('success', 'We have made '.$user->name.' a host for this group');
        }

        \Sentry\CaptureMessage('Sorry, you do not have permission to do this');
        return redirect()->back()->with('warning', 'Sorry, you do not have permission to do this');
    }

    public function getRemoveVolunteer($group_id, $user_id, Request $request)
    {
        //Has current logged in user got permission to remove volunteer
        $group = Group::findOrFail($group_id);
        $loggedInUser = Auth::user();

        if ((Fixometer::hasRole($loggedInUser, 'Host') && Fixometer::userIsHostOfGroup($group_id, Auth::id())) ||
            $loggedInUser->isCoordinatorForGroup($group) ||
            Fixometer::hasRole($loggedInUser, 'Administrator')) {
            // Retrieve user
            $user = User::find($user_id);

            //Let's delete the user
            $userGroupAssociation = UserGroups::where('user', $user_id)->where('group', $group_id)->first();

            if (! is_null($userGroupAssociation)) {
                $userGroupAssociation->delete();

                return redirect()->back()->with('success', 'We have removed '.$user->name.' from this group');
            }
            \Sentry\CaptureMessage('We are unable to remove from this group');

            return redirect()->back()->with('warning', 'We are unable to remove '.$user->name.' from this group');
        }

        \Sentry\CaptureMessage('Sorry, you do not have permission to do this');
        return redirect()->back()->with('warning', 'Sorry, you do not have permission to do this');
    }

    public function volunteersNearby($groupid)
    {
        $user = User::find(Auth::id());

        //Object Instances
        $Group = new Group;
        $User = new User;
        $Party = new Party;
        $Device = new Device;
        $groups = $Group->ofThisUser($user->id);

        // get list of ids to check in if condition
        $gids = [];
        foreach ($groups as $group) {
            $gids[] = $group->idgroups;
        }

        if ((isset($groupid) && is_numeric($groupid)) || in_array($groupid, $gids)) {
            $group = Group::where('idgroups', $groupid)->first();
        } else {
            $group = $groups[0];
            unset($groups[0]);
        }

        if (! is_null($group->latitude) && ! is_null($group->longitude)) {
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

        $allPastEvents = Party::past()
            ->with('devices.deviceCategory')
            ->forGroup($group->idgroups)
            ->get();

        $clusters = [];

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
                $cluster = $Device->countByCluster($i, $group->idgroups, $y);

                $total = 0;
                foreach ($cluster as $state) {
                    $total += $state->counter;
                }
                $cluster['total'] = $total;
                $clusters[$y][$i] = $cluster;
            }
        }

        if (! isset($response)) {
            $response = null;
        }

        //Event tabs
        $upcoming_events = Party::futureForUser()
            ->forGroup($group->idgroups)
            ->take(5)
            ->get();

        $past_events = Party::past()
            ->forGroup($group->idgroups)
            ->take(5)
            ->get();

        //Checking user for validatity
        $in_group = ! empty(UserGroups::where('group', $groupid)
            ->where('user', $user->id)
            ->where(function ($query) {
                $query->where('status', '1')
                    ->orWhereNull('status');
            })->first());

        $is_host_of_group = Fixometer::userHasEditGroupPermission($groupid, $user->id);

        $user_groups = UserGroups::where('user', Auth::user()->id)->count();
        $view_group = Group::find($groupid);

        $pendingInvite = UserGroups::where('group', $groupid)
            ->where('user', $user->id)
            ->where(function ($query) {
                $query->where('status', '<>', '1')
                    ->whereNotNull('status');
            })->first();
        $hasPendingInvite = ! empty($pendingInvite) ? $pendingInvite['status'] : false;

        return view('group.nearby', [ //host.index
            'title' => 'Host Dashboard',
            'has_pending_invite' => $hasPendingInvite,
            'response' => $response,
            'grouplist' => $Group->findList(),
            'userGroups' => $groups,
            'group' => $group,
            'profile' => $User->getProfile($user->id),
            'upcomingparties' => $Party->findNextParties($group->idgroups),
            'allparties' => $allPastEvents,
            'devices' => $Device->ofThisGroup($group->idgroups),
            'user' => $user,
            'upcoming_events' => $upcoming_events,
            'past_events' => $past_events,
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
            if (! is_null($user_group)) {
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

        return redirect('/group/nearby/'.intval($groupId))->with('success', $user->name.' has been invited');
    }

    /**
     * [confirmCodeInvite description].
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
        Invite::create([
            'record_id' => $group->idgroups,
            'email' => '',
            'hash' => $hash,
            'type' => 'group',
        ]);

        // Push this into a session variable to find by the Group prefix
        session()->push('groups.'.$code, $hash);

        return redirect('/user/register')->with('auth-for-invitation', __('auth.login_before_using_shareable_link', ['login_url' => url('/login')]));
    }
}
