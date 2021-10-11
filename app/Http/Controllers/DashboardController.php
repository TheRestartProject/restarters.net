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

        // Look for new nearby groups that we're not already a member of. Include ones where we have been
        // invited.
        //
        // Eloquent is just getting in the way here so do a raw query.
        $new_groups = DB::select(DB::raw("SELECT COUNT(*) AS count FROM groups 
        LEFT JOIN users_groups ON groups.idgroups = users_groups.group AND users_groups.user = " . intval(Auth::id()) . "
        WHERE (users_groups.user IS NULL OR users_groups.status != 1) 
            AND created_at >= '" . date('Y-m-d', strtotime('1 month ago')) . "'  
            AND ( 6371 * acos( cos( radians(' . $user->latitude . ') ) * cos( radians( groups.latitude ) ) * cos( radians( groups.longitude ) - radians(' . $user->longitude . ') ) + sin( radians(' . $user->latitude . ') ) * sin( radians( groups.latitude ) ) ) ) < 40"))[0]->count;

        // Look for upcoming events.  Only include events for groups we have joined, not just been invited to.
        $upcoming_events = Party::upcomingEvents()->where('users_groups.user', Auth::user()->id)
            ->where('users_groups.status', 1)
            ->orderBy('event_date', 'ASC')
            ->get();
        $expanded_events = [];

        foreach ($upcoming_events as $event) {
            $thisone = $event->getAttributes();
            $thisone['the_group'] = \App\Group::find($event->group);
            $expanded_events[] = $thisone;
        }

        $upcoming_events = $expanded_events;

        // Look for groups of which we are a member.  We have to explicitly test on deleted_at because
        // the normal filtering out of soft deletes won't happen for joins.
        $your_groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->leftJoin('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->whereNull('users_groups.status')
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
