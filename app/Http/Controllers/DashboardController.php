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

        $new_groups = [];

        if (! is_null($user->latitude) && ! is_null($user->longitude)) {
            // Look for new nearby groups that we're not already a member of.  Eloquent is just getting in the way
            // here so do a raw query.
            $new_groups = $user->groupsNearby(3, "1 month ago");
        }

        $expanded_events = [];

        // Look for any upcoming events for groups we have joined (not just been invited to).
        $upcoming_events = Party::upcomingEvents()->where('users_groups.user', Auth::user()->id)
            ->where('users_groups.status', 1)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('event_date', 'ASC')
            ->get();

        foreach ($upcoming_events as $event) {
            $thisone = $event->getAttributes();
            $thisone['the_group'] = \App\Group::find($event->group);
            $expanded_events[] = $thisone;
        }

        $upcoming_events = $expanded_events;

        // We want the users own groups.  Look for groups where user ID exists in pivot table.  We have to explicitly
        // test on deleted_at because the normal filtering out of soft deletes won't happen for joins.
        $your_groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->leftJoin('events', 'events.group', '=', 'groups.idgroups')
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->orderBy('groups.name', 'ASC')
            ->groupBy('groups.idgroups', 'groups.name')
            ->select(['groups.idgroups', 'groups.name'])
            ->take(3)
            ->get();

        if ($your_groups) {
            // We have some groups - return them.
            foreach ($your_groups as $group) {
                $group_image = $group->groupImage;
                if (is_object($group_image) && is_object($group_image->image)) {
                    $group_image->image->path;
                }
            }

            // We don't need to show any nearby groups.
            $groupsNearYou = null;
        } else {
            // We don't have any groups.  Find nearby ones to show if we can.
            $groupsNearYou = $user->groupsNearby(2);
        }

        return view(
            'dashboard.index',
            [
                'user' => $user,
                'groups_near_you' => $groupsNearYou,
                'upcoming_events' => $upcoming_events,
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
