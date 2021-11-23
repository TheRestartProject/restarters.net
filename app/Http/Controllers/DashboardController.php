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
            $groupsNearYou = $user->groupsNearby(2);
        }

        if (! isset($all_groups) || empty($all_groups->toArray())) {
            $all_groups = null;
        }

        $new_groups = [];

        //Get events nearest (or not) to you
        if (! is_null($user->latitude) && ! is_null($user->longitude)) { //Should the user have location info
            $upcoming_events = Party::with('theGroup')->select(
                DB::raw(
                    '*, ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( latitude ) ) ) ) AS distance'
                )
            )
                ->having('distance', '<=', User::NEARBY_KM)
                ->whereDate('event_date', '>=', date('Y-m-d'))
                ->orderBy('event_date', 'ASC')
                ->orderBy('start', 'ASC')
                ->orderBy('distance', 'ASC')
                ->take(3)
                ->get();

            // Look for new nearby groups that we're not already a member of.  Eloquent is just getting in the way
            // here so do a raw query.
            $new_groups = $user->groupsNearby(3, '1 month ago');
        } else { //Else show them the latest three
            $upcoming_events = Party::with('theGroup')->
            whereDate('event_date', '>=', date('Y-m-d'))
                ->select('events.*')
                ->orderBy('event_date', 'ASC')
                ->take(3)
                ->get();
        }

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
