<?php

namespace App\Http\Controllers;

use App\Device;
use App\EventsUsers;
use App\Group;
use App\Helpers\CachingRssRetriever;
use App\Helpers\CachingWikiPageRetriever;
use App\Helpers\FixometerHelper;
use App\Party;
use App\User;
use App\UserGroups;
use App\UsersSkills;

use Auth;
use Cache;
use DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = User::getProfile(Auth::id());

        // Update language every time you go to the dashboard
        Auth::user()->update([
            'language' => session('locale'),
        ]);

        $in_group = ! empty(UserGroups::where('user', Auth::id())->get()->toArray());
        $in_event = ! empty(EventsUsers::where('user', Auth::id())->get()->toArray());

        //See whether user has any events
        if ($in_event) {
            $event_ids = EventsUsers::where('user', Auth::id())->pluck('event')->toArray();
        }

        //See whether user has any groups
        if ($in_group) {
            $group_ids = UserGroups::where('user', Auth::id())->pluck('group')->toArray();
        }

        //If users has events, let's see whether they have any past events
        if ($in_event) {
            $past_events = Party::whereIn('idevents', $event_ids)
                     ->whereDate('event_date', '<', date('Y-m-d'))
                     ->join('groups', 'events.group', '=', 'idGroups')
                     ->select('events.*', 'groups.name')
                     ->orderBy('events.event_date', 'desc')
                     ->take(3)
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

            if ( ! isset($inactive_groups) || empty($inactive_groups->toArray())) {
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
            $groupsNearYou = $user->groupsNearby(150, 3);
        }

        if ( ! isset($all_groups) || empty($all_groups->toArray())) {
            $all_groups = null;
        }

        //Get events nearest (or not) to you
        if ( ! is_null($user->latitude) && ! is_null($user->longitude)) { //Should the user have location info
            $upcoming_events = Party::with('theGroup')->select(DB::raw('*, ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( latitude ) ) ) ) AS distance'))
            ->having('distance', '<=', 40)
              ->whereDate('event_date', '>=', date('Y-m-d'))
                ->orderBy('event_date', 'ASC')
                  ->orderBy('start', 'ASC')
                    ->orderBy('distance', 'ASC')
                      ->take(3)
                        ->get();
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
            ->select([ 'groups.idgroups' , 'groups.name' ])
            ->take(3)
            ->get();

        $expanded = [];

        foreach ($your_groups as $group) {
            $group_image = $group->groupImage;
            if (is_object($group_image) && is_object($group_image->image)) {
                $group_image->image->path;
            }
        }

        return view('dashboard.index', [
            'user' => $user,
            'groupsNearYou' => $groupsNearYou,
            'upcoming_events' => $upcoming_events,
            'topics' => $this->getDiscourseHotTopics()['talk_hot_topics'],
            'your_groups' => $your_groups,
            'seeAllTopicsLink' => env('DISCOURSE_URL') . "/latest"
        ]);
    }

    public function getDiscourseHotTopics()
    {

        /**
         * Query Discourse API for current logged in user
         * This retrieves all categories from Discourse
         */
        if (Cache::has('talk_categories_'.Auth::user()->username)) {
            $talk_categories = Cache::get('talk_categories_'.Auth::user()->username);
        } else {
            $talk_categories = [];
            $talk_categories_json = FixometerHelper::discourseAPICall('site.json', [
                // 'offset' => '60',
                'api_username' => env('DISCOURSE_APIUSER'), // Uses default API user to retrieve all categories
            ], true);
            if (is_object($talk_categories_json) && isset($talk_categories_json->categories)) {
                foreach ($talk_categories_json->categories as $category) {
                    $talk_categories[$category->id] = $category;
                }
                Cache::put('talk_categories_'.Auth::user()->username, $talk_categories, 60 * 24);
            }
        }

        /**
         * Query Discourse API for current logged in user
         * This retrieves all hot topics from Discourse
         */
        if (Cache::has('talk_hot_topics_'.Auth::user()->username)) {
            $talk_hot_topics = Cache::get('talk_hot_topics_'.Auth::user()->username);
        } else {
            $talk_hot_topics = [];
            $talk_hot_topics_json = FixometerHelper::discourseAPICall('top/weekly.json', [
                // 'offset' => '60',
                'api_username' => Auth::user()->username,
            ], true);
            if (is_object($talk_hot_topics_json) && isset($talk_hot_topics_json->topic_list->topics)) {
                $talk_hot_topics = $talk_hot_topics_json->topic_list->topics;
                Cache::put('talk_hot_topics_'.Auth::user()->username, $talk_hot_topics, 60);
            }
        }

        return [
            'talk_categories' => $talk_categories,
            'talk_hot_topics' => $talk_hot_topics,
        ];
    }

    public function getHostDash()
    {
        return view('dashboard.host');
    }
}
