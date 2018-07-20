<?php

namespace App\Http\Controllers;

use App\User;
use App\Party;
use App\Group;
use App\UserGroups;
use App\UsersSkills;
use App\EventsUsers;
use App\Helpers\FixometerHelper;
use App\Helpers\CachingRssRetriever;
use App\Helpers\CachingWikiPageRetriever;
use App\Device;

use Auth;
use DB;

class DashboardController extends Controller
{
  // public function __construct($model, $controller, $action){
  //     parent::__construct($model, $controller, $action);
  //
  //     $Auth = new Auth($url);
  //     if(!$Auth->isLoggedIn()){
  //         header('Location: /user/login');
  //     }
  //     else {
  //
  //         $user = $Auth->getProfile();
  //         $this->user = $user;
  //         $this->set('user', $user);
  //         $this->set('header', true);
  //     }
  // }


  public function index() {

      $user = User::getProfile(Auth::id());

      $in_group = !empty(UserGroups::where('user', Auth::id())->get()->toArray());
      $has_skills = !empty(UsersSkills::where('user', Auth::id())->get()->toArray());
      $in_event = !empty(EventsUsers::where('user', Auth::id())->get()->toArray());

      if (!is_null($user->idimages) && !is_null($user->path)) {
        $has_profile_pic = true;
      } else {
        $has_profile_pic = false;
      }

      //See whether user has any events
      if ($in_event)
        $event_ids = EventsUsers::where('user', Auth::id())->pluck('event')->toArray();

      //See whether user has any groups
      if ($in_group)
        $group_ids = UserGroups::where('user', Auth::id())->pluck('group')->toArray();

      //If users has events, let's see whether they have any past events
      if ($in_event) {
        $past_events = Party::whereIn('idevents', $event_ids)
                                ->whereDate('event_date', '<', date('Y-m-d'))
                                  ->join('groups', 'events.group', '=', 'idGroups')
                                    ->select('events.*', 'groups.name')
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

        $outdated_groups = Group::whereIn('idgroups', $group_ids)
                                ->whereDate('updated_at', '<=', date('Y-m-d', strtotime("-3 Months")) )
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

      if ( $in_group ) {
        $all_groups = Group::whereIn('idgroups', $group_ids)->get();
      }

      if (!isset($all_groups) || empty($all_groups->toArray())) {
        $all_groups = null;
      }

      //Get events nearest (or not) to you
      if (!is_null($user->latitude) && !is_null($user->longitude) ) { //Should the user have location info

        $upcoming_events = Party::select(DB::raw('*, ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( latitude ) ) ) ) AS distance'))
            ->having("distance", "<=", 40)
              ->whereDate('event_date', '>=', date('Y-m-d'))
                ->orderBy('event_date', 'ASC')
                  ->orderBy('start', 'ASC')
                    ->orderBy('distance', 'ASC')
                      ->take(3)
                        ->get();

      } else { //Else show them the latest three

        $upcoming_events = Party::whereDate('event_date', '>=', date('Y-m-d'))
                                  ->select('events.*')
                                    ->orderBy('event_date', 'ASC')
                                      ->take(3)
                                        ->get();

      }

      $rssRetriever = new CachingRssRetriever('https://therestartproject.org/feed');
      $news_feed = $rssRetriever->getRSSFeed(3);

      $wikiPagesRetriever = new CachingWikiPageRetriever(env('WIKI_URL') . '/api.php');
      $wiki_pages = $wikiPagesRetriever->getRandomWikiPages(5);

      //Show onboarding modals on first login
      if ($user->number_of_logins == 1) {
        $onboarding = true;
      } else {
        $onboarding = false;
      }

      $devices_gateway = new Device;
      $impact_stats = $devices_gateway->getWeights();

      return view('dashboard.index', [
        'gmaps' => true,
        'user' => $user,
        'header' => true,
        'in_group' => $in_group,
        'has_skills' => $has_skills,
        'in_event' => $in_event,
        'has_profile_pic' => $has_profile_pic,
        'past_events' => $past_events,
        'upcoming_events' => $upcoming_events,
        'outdated_groups' => $outdated_groups,
        'inactive_groups' => $inactive_groups,
        'news_feed' => $news_feed,
        'all_groups' => $all_groups,
        'onboarding' => $onboarding,
        'impact_stats' => $impact_stats,
        'wiki_pages' => $wiki_pages
      ]);

      /*
      $this->set('title', 'Dashboard');
      $this->set('charts', true);

      $Parties    = new Party;
      $Devices    = new Device;
      $Groups     = new Group;


      $this->set('upcomingParties', $Parties->findNextParties());

      $devicesByYear = array();
      for( $i = 1; $i < 4; $i++ ){

          $devices = $Devices->getByYears($i);
          $deviceList = array();
          foreach( $devices as $listed ) {
              $deviceList[$listed->event_year] = $listed->total_devices;
          }
          $devicesByYear[$i] = $deviceList;

      }
      $this->set('devicesByYear', $devicesByYear);
      */
  }


  public function getHostDash(){
    return view('dashboard.host');
  }
}
