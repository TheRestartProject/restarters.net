<?php

namespace App\Http\Controllers;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\Helpers\CachingRssRetriever;
use App\Helpers\CachingWikiPageRetriever;
use App\Helpers\FixometerHelper;
use App\Party;
use App\Services\DiscourseService;
use App\User;
use App\UserGroups;
use App\UsersSkills;

use Auth;
use Cache;
use DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(DiscourseService $discourseService)
    {
        $user = User::getProfile(Auth::id());

        // Update language every time you go to the dashboard
        Auth::user()->update(
            [
                'language' => session('locale'),
            ]
        );

        $in_group = !empty(UserGroups::where('user', Auth::id())->get()->toArray());
        $in_event = !empty(EventsUsers::where('user', Auth::id())->get()->toArray());

        //See whether user has any events
        if ($in_event) {
            $event_ids = EventsUsers::where('user', Auth::id())->pluck('event')->toArray();
        }

        //See whether user has any groups
        if ($in_group) {
            $group_ids = array_unique(UserGroups::where('user', Auth::id())->pluck('group')->toArray());
        }

        //If users has events, let's see whether they have any past events
        if ($in_event) {
            $past_events = Party::whereIn('idevents', $event_ids)
                ->whereDate('event_date', '<', date('Y-m-d'))
                ->join('groups', 'events.group', '=', 'idGroups')
                ->select('events.*', 'groups.name', 'groups.idgroups')
                ->orderBy('events.event_date', 'desc')
                ->get();

            if (empty($past_events->toArray())) {
                $past_events = null;
            }
        } else {
            $past_events = null;
        }

        //Host specific queries
        if (FixometerHelper::hasRole($user, 'Host') && $in_group) {
            $outdated_groups = Group::join('users_groups', 'groups.idgroups', '=', 'users_groups.group')
                ->where('users_groups.user', Auth::user()->id)
                ->where('users_groups.role', 3)
                ->whereDate('updated_at', '<=', date('Y-m-d', strtotime('-3 Months')))
                ->select('groups.*')
                ->take(3)
                ->get();

            if (empty($outdated_groups->toArray())) {
                $outdated_groups = null;
            }

            if ($in_event) {
                $active_group_ids = Party::whereIn('idevents', $event_ids)
                    ->whereDate('event_date', '>', date('Y-m-d'))
                    ->pluck('events.group')
                    ->toArray();

                $non_active_group_ids = array_diff($group_ids, $active_group_ids);
                $inactive_groups = Group::whereIn('idgroups', $non_active_group_ids)
                    ->take(3)
                    ->get();
            }

            if (!isset($inactive_groups) || empty($inactive_groups->toArray())) {
                $inactive_groups = null;
            }
        } else {
            $outdated_groups = null;
            $inactive_groups = null;
        }

        $groupsNearYou = null;

        if ($in_group) {
            $all_groups = Group::whereIn('idgroups', $group_ids)->get();
        } else {
            $groups = $user->groupsNearby(150, 2);
            $groupsNearYou = [];

            foreach ($groups as $group) {
                $group_image = $group->groupImage;
                if (is_object($group_image) && is_object($group_image->image)) {
                    $group_image->image->path;
                }

                $groupsNearYou[] = $group;
            }
        }

        if (!isset($all_groups) || empty($all_groups->toArray())) {
            $all_groups = null;
        }

        $new_groups = 0;
        //Get events nearest (or not) to you
        if (!is_null($user->latitude) && !is_null($user->longitude)) { //Should the user have location info
            $upcoming_events = Party::with('theGroup')->select(
                DB::raw(
                    '*, ( 6371 * acos( cos( radians(' . $user->latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $user->longitude . ') ) + sin( radians(' . $user->latitude . ') ) * sin( radians( latitude ) ) ) ) AS distance'
                )
            )
                ->having('distance', '<=', 40)
                ->whereDate('event_date', '>=', date('Y-m-d'))
                ->orderBy('event_date', 'ASC')
                ->orderBy('start', 'ASC')
                ->orderBy('distance', 'ASC')
                ->take(3)
                ->get();

            // Look for new nearby groups that we're not already a member of.  Eloquent is just getting in the way
            // here so do a raw query.
            $new_groups = DB::select(DB::raw("SELECT COUNT(*) AS count FROM groups 
        LEFT JOIN users_groups ON groups.idgroups = users_groups.group AND users_groups.user = " . intval(Auth::id()) . "
        WHERE users_groups.user IS NULL 
            AND created_at >= '" . date('Y-m-d', strtotime('1 month ago')) . "'  
            AND ( 6371 * acos( cos( radians(' . $user->latitude . ') ) * cos( radians( groups.latitude ) ) * cos( radians( groups.longitude ) - radians(' . $user->longitude . ') ) + sin( radians(' . $user->latitude . ') ) * sin( radians( groups.latitude ) ) ) ) < 40"))[0]->count;

        } else { //Else show them the latest three
            $upcoming_events = Party::with('theGroup')->
            whereDate('event_date', '>=', date('Y-m-d'))
                ->select('events.*')
                ->orderBy('event_date', 'ASC')
                ->take(3)
                ->get();
        }

        // Look for groups where user ID exists in pivot table.  We have to explicitly test on deleted_at because
        // the normal filtering out of soft deletes won't happen for joins.
        $your_groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->leftJoin('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups')
            ->select(['groups.idgroups', 'groups.name'])
            ->take(3)
            ->get();

        foreach ($your_groups as $group) {
            $group_image = $group->groupImage;
            if (is_object($group_image) && is_object($group_image->image)) {
                $group_image->image->path;
            }
        }

        return view(
            'dashboard.index',
            [
                'user' => $user,
                'groups_near_you' => $groupsNearYou,
                'upcoming_events' => $upcoming_events,
                'past_events' => $past_events,
                'topics' => $discourseService->getDiscussionTopics(),
                'your_groups' => $your_groups,
                'seeAllTopicsLink' => env('DISCOURSE_URL') . "/latest",
                'new_groups' => $new_groups,
            ]
        );
    }

    public function getHostDash()
    {
        return view('dashboard.host');
    }
}
