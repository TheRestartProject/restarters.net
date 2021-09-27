<?php

namespace App\Http\Controllers;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\Helpers\CachingRssRetriever;
use App\Helpers\CachingWikiPageRetriever;
use App\Helpers\Fixometer;
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

        $in_group = ! empty(UserGroups::where('user', Auth::id())->get()->toArray());
        $in_event = ! empty(EventsUsers::where('user', Auth::id())->get()->toArray());

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
        if (Fixometer::hasRole($user, 'Host') && $in_group) {
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

            if (! isset($inactive_groups) || empty($inactive_groups->toArray())) {
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

            if ($groups) {
                foreach ($groups as $group) {
                    $group_image = $group->groupImage;
                    if (is_object($group_image) && is_object($group_image->image)) {
                        $group_image->image->path;
                    }

                    $groupsNearYou[] = $group;
                }
            }
        }

        if (! isset($all_groups) || empty($all_groups->toArray())) {
            $all_groups = null;
        }

        $new_groups = 0;

        $upcoming_events = Party::upcomingEvents()->where('users_groups.user', Auth::user()->id)
            ->orderBy('event_date', 'ASC')
            ->get();
        $expanded_events = [];

        foreach ($upcoming_events as $event) {
            $thisone = $event->getAttributes();
            $thisone['the_group'] = \App\Group::find($event->group);
            $expanded_events[] = $thisone;
        }

        $upcoming_events = $expanded_events;

        // Look for groups where user ID exists in pivot table.  We have to explicitly test on deleted_at because
        // the normal filtering out of soft deletes won't happen for joins.
        $your_groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->leftJoin('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups', 'groups.name')
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
                'seeAllTopicsLink' => env('DISCOURSE_URL').'/latest',
                'new_groups' => $new_groups,
            ]
        );
    }

    public function getHostDash()
    {
        return view('dashboard.host');
    }
}
