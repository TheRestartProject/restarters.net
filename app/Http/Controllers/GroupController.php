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
use App\Party;
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

        // We only need some attributes.
        $group_atts = ['groups.idgroups', 'groups.name', 'groups.location', 'groups.country'];

        // Get all groups
        $groups = Group::with(['networks'])
            ->select($group_atts)
            ->orderBy('name', 'ASC')
            ->get();

        // Get all group tags
        $all_group_tags = GroupTags::all();
        $networks = Network::all();

        // Look for groups where user ID exists in pivot table.  We have to explicitly test on deleted_at because
        // the normal filtering out of soft deletes won't happen for joins.
        $your_groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->leftJoin('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups')
            ->select($group_atts)
            ->get();

        //Make sure we don't show the same groups in nearest to you
        $your_groups_uniques = $your_groups->pluck('idgroups')->toArray();

        //Assuming we have valid lat and long values, let's see what is nearest
        if (! is_null($user->latitude) && ! is_null($user->longitude)) {
            $groups_near_you = Group::select(DB::raw(implode(',', $group_atts).', ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( groups.latitude ) ) * cos( radians( groups.longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( groups.latitude ) ) ) ) AS distance'))
                ->having('distance', '<=', 150)
                ->join('events', 'events.group', '=', 'groups.idgroups')
                ->whereNotIn('groups.idgroups', $your_groups_uniques)
                ->groupBy('groups.idgroups')
                ->orderBy('distance', 'ASC')
                ->take(10)
                ->get();
        } else {
            $groups_near_you = null;
        }

        return view('group.index', [
            'your_groups' => $this->expandGroups($your_groups),
            'your_groups_uniques' => $your_groups_uniques,
            'groups_near_you' => $this->expandGroups($groups_near_you),
            'groups' => $this->expandGroups($groups),
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

            if (empty($name)) {
                $error['name'] = 'Please input a name.';
            }

            if (! empty($location)) {
                $geocoded = $geocoder->geocode($location);

                if (empty($geocoded)) {
                    $response['danger'] = 'Group could not be created. Address not found.';

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
                    }
                } catch (QueryException $e) {
                    $errorCode = $e->errorInfo[1];
                    if ($errorCode == 1062) {
                        $response['danger'] = __('groups.duplicate', [
                            'name' => $name,
                        ]);
                    } else {
                        $response['danger'] = __('groups.database_error');
                    }
                }
            } else {
                $response['danger'] = __('groups.create_failed');
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

                default:
                    $response['danger'] = 'Unexpected arguments';
                    break;
            }
        }

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

        $allPastEvents = Party::pastEvents()
        ->with('devices.deviceCategory')
        ->where('events.group', $group->idgroups)
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
        $upcoming_events = Party::upcomingEvents()
        ->where('events.group', $group->idgroups)
        ->get();

        $past_events = Party::pastEvents()
        ->where('events.group', $group->idgroups)
        ->get();

        //Checking user for validatity
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

        $hasPendingInvite = ! empty(UserGroups::where('group', $groupid)
        ->where('user', $user->id)
        ->where(function ($query) {
            $query->where('status', '<>', '1')
            ->whereNotNull('status');
        })->first());

        $groupStats = $group->getGroupStats();

        $expanded_events = [];

        foreach (array_merge($upcoming_events->all(), $past_events->all()) as $event) {
            $thisone = $event->getAttributes();
            $thisone['attending'] = Auth::user() && $event->isBeingAttendedBy(Auth::user()->id);
            $thisone['allinvitedcount'] = $event->allInvited->count();

            // TODO LATER Consider whether these stats should be in the event or passed into the store.
            $thisone['stats'] = $event->getEventStats();
            $thisone['participants_count'] = $event->participants;
            $thisone['volunteers_count'] = $event->allConfirmedVolunteers->count();

            $thisone['isVolunteer'] = $event->isVolunteer();
            $thisone['requiresModeration'] = $event->requiresModerationByAdmin();
            $thisone['canModerate'] = Auth::user() && (\App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') || \App\Helpers\Fixometer::hasRole(Auth::user(), 'NetworkCoordinator'));

            $expanded_events[] = $thisone;
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
            'upcomingparties' => $Party->findNextParties($group->idgroups),
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

        return redirect()->back()->with('warning', 'Invites sent - apart from these ('.rtrim(implode(', ', $not_sent), ', ').') who have already joined the group, have already been sent an invite, or have not opted in to receive emails');
    }

    public function confirmInvite($group_id, $hash)
    {
        // Find user/group relationship based on the invitation hash.
        $user_group = UserGroups::where('status', $hash)->where('group', $group_id)->first();
        if (empty($user_group)) {
            return redirect('/group/view/'.intval($group_id))->with('warning', 'Something went wrong - this invite is invalid or has expired');
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

        if (! empty($group_hosts)) {
            Notification::send($group_hosts, new NewGroupMember([
                'user_name' => $user->name,
                'group_name' => Group::find($group_id)->name,
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

        $group = Group::find($id);
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
                    $response['danger'] = 'Group could not be saved. Address not found.';
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

            $update = [
                'name' => $data['name'],
                'website' => $data['website'],
                'free_text' => $data['free_text'],
                'location' => $data['location'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'country' => $country,
            ];

            if ($user->hasRole('Administrator') || $user->hasRole('NetworkCoordinator')) {
                $update['area'] = $data['area'];
                $update['postcode'] = $data['postcode'];
            }

            $u = Group::findOrFail($id)->update($update);

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
                } elseif (! empty($group->wordpress_post_id)) {
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
            $allEvents = Party::where('events.group', $id)->get();

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

    private function expandGroups($groups)
    {
        $ret = [];

        if ($groups) {
            foreach ($groups as $group) {
                $group_image = $group->groupImage;

                $event = $group->getNextUpcomingEvent();

                $ret[] = [
                    'idgroups' => $group['idgroups'],
                    'name' => $group['name'],
                    'image' => (is_object($group_image) && is_object($group_image->image)) ?
                        asset('uploads/mid_'.$group_image->image->path) : null,
                    'location' => rtrim($group['location']),
                    'next_event' => $event ? $event['event_date'] : null,
                    'all_restarters_count' => $group->all_restarters_count,
                    'all_hosts_count' => $group->all_hosts_count,
                    'networks' => Arr::pluck($group->networks, 'id'),
                    'country' => $group->country,
                    'group_tags' => $group->group_tags()->get()->pluck('id'),
                ];
            }
        }

        return $ret;
    }

    public static function stats($id, $format = 'row')
    {
        $group = Group::where('idgroups', $id)->first();
        if (!$group) {
            return abort(404, 'Invalid group id');
        }

        $groupStats = $group->getGroupStats();

        $groupStats['format'] = $format;

        return view('group.stats', $groupStats);
    }

    public static function statsByGroupTag($group_tag_id, $format = 'row')
    {
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
            'ewaste' => 0,
            'unpowered_waste' => 0,
            'fixed_devices' => 0,
            'fixed_powered' => 0,
            'fixed_unpowered' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
            'devices_powered' => 0,
            'devices_unpowered' => 0,
        ];

        // Loop through all groups and increase the values for groupStats
        foreach ($groups as $group) {
            // Get stats for this particular group
            $single_group_stats = $group->getGroupStats();

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
                    ->with('success', __('groups.now_following', [
                        'name' => $group->name,
                        'link' => url('/group/view/'.$group->idgroups),
                    ]));
        } catch (\Exception $e) {
            $response['danger'] = 'Failed to follow this group';

            return redirect()->back()->with('response', $response)->with('warning', 'Failed to follow this group');
        }
    }

    // TODO: is this alive?  Not completely clear, but it is referenced from a route.
    public function imageUpload(Request $request, $id)
    {
        try {
            if (isset($_FILES) && ! empty($_FILES)) {
                $existing_image = Fixometer::hasImage($id, 'groups', true);
                if (! empty($existing_image)) {
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
        $group = Group::find($group_id);
        $loggedInUser = Auth::user();

        if (($loggedInUser->hasRole('Host') && Fixometer::userIsHostOfGroup($group_id, $loggedInUser->id)) ||
            $loggedInUser->isCoordinatorForGroup($group) ||
            $loggedInUser->hasRole('Administrator')) {
            $user = User::find($user_id);

            $group->makeMemberAHost($user);

            return redirect()->back()->with('success', 'We have made '.$user->name.' a host for this group');
        }

        return redirect()->back()->with('warning', 'Sorry, you do not have permission to do this');
    }

    public function getRemoveVolunteer($group_id, $user_id, Request $request)
    {
        //Has current logged in user got permission to remove volunteer
        if ((Fixometer::hasRole(Auth::user(), 'Host') && Fixometer::userIsHostOfGroup($group_id, Auth::id())) || Fixometer::hasRole(Auth::user(), 'Administrator')) {
            // Retrieve user
            $user = User::find($user_id);

            //Let's delete the user
            $userGroupAssociation = UserGroups::where('user', $user_id)->where('group', $group_id)->first();

            if (! is_null($userGroupAssociation)) {
                $userGroupAssociation->delete();

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
                default: {
                    $response['danger'] = 'Unexpected arguments';
                    break;
                }
            }
        }

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

        $allPastEvents = Party::pastEvents()
                     ->with('devices.deviceCategory')
                     ->where('events.group', $group->idgroups)
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

        $is_host_of_group = Fixometer::userHasEditGroupPermission($groupid, $user->id);

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

    /**
     * [getGroupsByKey description]
     * Find Groups from User Access Key,
     * If the Groups are not found, through 404 error,
     * Else return the Groups JSON data.
     *
     * @author Christopher Kelker - @date 2019-03-26
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @param   Request     $request
     * @param   [type]      $api_token
     * @return  [type]
     */
    public function getGroupsByKey(Request $request, $api_token)
    {
        // Find User by Access Key
        $user = User::where('api_token', $api_token)->first();

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

        foreach ($group_tags_groups as $group_tags_group) {
            $group = $group_tags_group->theGroup;
            if (! empty($group)) {
                $stats = $group->getGroupStats();
                $collection->push([
                    'id' => $group->idgroups,
                    'name' => $group->name,
                    'location' => [
                        'value' => $group->location,
                        'country' => $group->country,
                        'latitude' => $group->latitude,
                        'longitude' => $group->longitude,
                        'area' => $group->area,
                        'postcode' => $group->postcode,
                    ],
                    'website' => $group->website,
                    'facebook' => $group->facebook,
                    'description' => $group->free_text,
                    'image_url' => $group->groupImagePath(),
                    'upcoming_parties' => $upcoming_parties_collection = collect([]),
                    'past_parties' => $past_parties_collection = collect([]),
                    'impact' => [
                        'volunteers' => $stats['pax'],
                        'hours_volunteered' => $stats['hours'],
                        'parties_thrown' => $stats['parties'],
                        'waste_prevented' => $stats['waste'],
                        'co2_emissions_prevented' => $stats['co2'],
                    ],
                    'widgets' => [
                        'headline_stats' => url("/group/stats/{$group->idgroups}"),
                        'co2_equivalence_visualisation' => url("/outbound/info/group/{$group->idgroups}/manufacture"),
                    ],
                    'created_at' => $group->created_at,
                    'updated_at' => $group->updated_at,
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
                        'created_at' => $event->created_at,
                        'updated_at' => $event->updated_at,
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
                        'created_at' => $event->created_at,
                        'updated_at' => $event->updated_at,
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
     * Else return the Group JSON data.
     *
     * @author Christopher Kelker - @date 2019-03-26
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @param   Request     $request
     * @param   [type]      $api_token
     * @param   Group       $group
     * @return  [type]
     */
    public function getGroupByKeyAndId(Request $request, $api_token, Group $group, $date_from = null, $date_to = null)
    {
        // Get Group from Access Key and Group ID
        $group_tags_group = User::where('api_token', $api_token)->first()
        ->groupTag->groupTagGroups->where('group', $group->idgroups)->first();

        // If Group is not found, through 404 error
        if (empty($group_tags_group)) {
            return abort(404, 'No groups found.');
        }

        // If Event is found but is not the of the date specified
        $exclude_parties = [];
        if (! empty($date_from) && ! empty($date_to)) {
            foreach ($group->parties as $party) {
                if (! Fixometer::validateBetweenDates($party->event_date, $date_from, $date_to)) {
                    $exclude_parties[] = $party->idevents;
                }
            }
        }

        $stats = $group->getGroupStats();
        // New Collection Instance
        $collection = collect([
            'id' => $group->idgroups,
            'name' => $group->name,
            'location' => [
                'value' => $group->location,
                'country' => $group->country,
                'latitude' => $group->latitude,
                'longitude' => $group->longitude,
                'area' => $group->area,
                'postcode' => $group->postcode,
            ],
            'website' => $group->website,
            'description' => $group->free_text,
            'image_url' => $group->groupImagePath(),
            'upcoming_parties' => $upcoming_parties_collection = collect([]),
            'past_parties' => $past_parties_collection = collect([]),
            'impact' => [
                'volunteers' => $stats['pax'],
                'hours_volunteered' => $stats['hours'],
                'parties_thrown' => $stats['parties'],
                'waste_prevented' => $stats['waste'],
                'co2_emissions_prevented' => $stats['co2'],
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
