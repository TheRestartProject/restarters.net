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
use App\Skills;
use App\User;
use App\UserGroups;
use App\UsersSkills;
use Auth;
use DB;
use FixometerFile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Notification;
use Spatie\ValidationRules\Rules\Delimited;

class GroupController extends Controller
{
    private function indexVariations($tab, $network)
    {
        //Get current logged in user
        $user = Auth::user();

        // Get all group tags
        $all_group_tags = GroupTags::all();
        $networks = Network::all();

        // Look for groups we have joined, not just been invited to.  We have to explicitly test on deleted_at because
        // the normal filtering out of soft deletes won't happen for joins.
        $your_groups = array_column(Group::with(['networks'])
            ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->leftJoin('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->where('users_groups.status', 1)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups')
            ->get()
            ->toArray(), 'idgroups');

        $nearby_groups = [];

        if ($user->latitude || $user->longitude) {
            // We pass a high limit to the groups nearby; there is a distance limit which will normally kick in first.
            $nearby_groups = $user->groupsNearby(1000);

            // Now find the lat/lng bounding box which contains these groups.
            $min_lat = 90;
            $max_lat = -90;
            $min_lng = 180;
            $max_lng = -180;

            foreach ($nearby_groups as $group) {
                if ($group->latitude < $min_lat) {
                    $min_lat = $group->latitude;
                }
                if ($group->latitude > $max_lat) {
                    $max_lat = $group->latitude;
                }
                if ($group->longitude < $min_lng) {
                    $min_lng = $group->longitude;
                }
                if ($group->longitude > $max_lng) {
                    $max_lng = $group->longitude;
                }
            }
        }

        return view('group.index', [
            'your_groups' => $your_groups,
            'nearby_groups' => [ [ $min_lat, $min_lng ], [ $max_lat, $max_lng ] ],
            'your_area' => $user->location,
            'your_lat' => $user->latitude,
            'your_lng' => $user->longitude,
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

    public function create(Request $request)
    {
        $user = User::find(Auth::id());

        // Anyone who is logged in can create a group.
        if (!$user) {
            return redirect('/user/forbidden');
        }

        return view('group.create');
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
        }

        if (! $group) {
            return abort(404, 'Invalid group.');
        }

        $allPastEvents = Party::past()
            ->with('devices.deviceCategory')
            ->forGroup($group->idgroups)
            ->get();

        $Device->ofThisGroup($group->idgroups);

        $counts = $Device->countByClustersYearStatus($group->idgroups);
        $template = [
            0 => [
                'counter' => 0,
                'repair_status' => 1,
            ],
            1 => [
                'counter' => 0,
                'repair_status' => 2,
            ],
            2 => [
                'counter' => 0,
                'repair_status' => 3,
            ],
            'total' => 0
        ];

        $clusters = [
            'all' => [
                1 => $template,
                2 => $template,
                3 => $template,
                4 => $template,
            ]
        ];

        foreach ($counts as $count) {
            $year = $count->year;
            $cluster = $count->cluster;
            $repair_status = $count->repair_status;
            $counter = $count->counter;

            if ($repair_status && $cluster) {
                if (array_key_exists($cluster, $clusters['all'])) {
                    $clusters['all'][$cluster][$repair_status - 1]['counter'] += $counter;
                    $clusters['all'][$cluster]['total'] += $counter;

                    if (!array_key_exists($year, $clusters)) {
                        $clusters[$year] = [
                            1 => $template,
                            2 => $template,
                            3 => $template,
                            4 => $template,
                        ];
                    }

                    $clusters[$year][$cluster][$repair_status - 1]['counter'] += $counter;
                    $clusters[$year][$cluster]['total'] += $counter;
                }
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
            'group_id' => $groupid
        ]);
    }

    public function postSendInvite(Request $request)
    {
        $request->validate([
            'manual_invite_box' => [(new Delimited('email'))->min(1)],
        ]);

        $from_id = Auth::id();
        $group_name = $request->input('group_name');
        $group_id = $request->input('group_id');
        $emails = explode(',', str_replace(' ', '', $request->input('manual_invite_box')));
        $message = $request->input('message_to_restarters');

        if (empty($emails)) {
            \Sentry\CaptureMessage('You have not entered any emails!');
            return redirect()->back()->with('warning', __('groups.invite.no_emails'));
        }

        $invalid = [];

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid[] = $email;
            }
        }

        if (count($invalid)) {
            return redirect()->back()->with('warning', __('groups.invite_invalid_emails', [
                'emails' => implode(', ', $invalid)
            ]));
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
            return redirect()->back()->with('success', __('groups.invite_success'));
        }

        // Don't log to Sentry - legitimate user error.
        return redirect()->back()->with('warning', __('groups.invite_success_apart_from', [
            'emails' => rtrim(implode(', ', $not_sent))
        ]));
    }

    public function confirmInvite($group_id, $hash)
    {
        // Find user/group relationship based on the invitation hash.
        $user_group = UserGroups::where('status', $hash)->where('group', $group_id)->first();
        if (empty($user_group)) {
            \Sentry\CaptureMessage(__('groups.invite_invalid'));
            return redirect('/group/view/'.intval($group_id))->with('warning', __('groups.invite_invalid'));
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

        return redirect('/group/view/'.$user_group->group)->with('success', __('groups.invite_confirmed'));
    }

    public function edit(Request $request, $id, Geocoder $geocoder)
    {
        $user = Auth::user();

        $group = Group::findOrFail($id);
        $is_host_of_group = Fixometer::userHasEditGroupPermission($id, $user->id);
        $isCoordinatorForGroup = $user->isCoordinatorForGroup($group);

        if (! Fixometer::hasRole($user, 'Administrator') && ! $is_host_of_group && ! $isCoordinatorForGroup) {
            abort(403);
        }

        return view('group.edit', [
            'id' => $id,
            'name' => $group->name,
            'audits' => $group->audits,
            'networks' => Network::all(),
            'can_approve' => Fixometer::hasRole($user, 'Administrator') ||
                Fixometer::hasRole($user, 'NetworkCoordinator') && $isCoordinatorForGroup
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

    public static function expandGroups($groups, $your_groupids)
    {
        $ret = [];
        $user = Auth::user();

        if ($groups) {
            foreach ($groups as $group) {
                $group_image = $group->groupImage;

                $event = $group->getNextUpcomingEvent();

                // We want to return the distance from our own location.
                $distance = null;
                $grouplat = $group->latitude;
                $grouplng = $group->longitude;
                $userlat = $user->latitude;
                $userlng = $user->longitude;

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
                        'country' => Fixometer::getCountryFromCountryCode($group->country_code),
                        'country_code' => $group->country_code,
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
                    'archived_at' => $group->archived_at ? Carbon::parse($group->archived_at)->toIso8601String() : null
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
            $response['danger'] = __('groups.follow_group_error');
            \Sentry\CaptureMessage($response['danger']);

            return redirect()->back()->with('response', $response)->with('warning', __('groups.follow_group_error'));
        }
    }

    public function imageUpload(Request $request, $id)
    {
        try {
            if (isset($_FILES) && ! empty($_FILES)) {
                $existing_image = Fixometer::hasImage($id, 'groups', true);
                if (! empty($existing_image)) {
                    Fixometer::removeImage($id, 'groups', $existing_image[0]);
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
