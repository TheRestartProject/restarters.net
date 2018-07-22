<?php

namespace App\Http\Controllers;

use App\Device;
use App\Group;
use App\GroupTags;
use App\GrouptagsGroups;
use App\Party;
use App\Invite;
use App\User;
use App\UserGroups;
use App\Helpers\FootprintRatioCalculator;
use Auth;
use App\Notifications\JoinGroup;
use App\Notifications\NewGroupMember;
use App\Notifications\ModerationGroup;
use DB;
use FixometerHelper;
use FixometerFile;
use Illuminate\Http\Request;
use Notification;


class GroupController extends Controller
{
  // public function __construct($model, $controller, $action){
  //     parent::__construct($model, $controller, $action);
  //
  //     $Auth = new Auth($url);
  //     if(!$Auth->isLoggedIn() && $action != 'stats'){
  //         header('Location: /user/login');
  //     }
  //     else {
  //
  //         $user = $Auth->getProfile();
  //         $this->user = $user;
  //         $this->set('user', $user);
  //         $this->set('header', true);
  //
  //
  //         if(FixometerHelper::hasRole($this->user, 'Host')){
  //             $User = new User;
  //             $this->set('profile', $User->profilePage($this->user->id));
  //         }
  //     }
  // }
  public function __construct(){

      $Device = new Device;
      $weights = $Device->getWeights();

      $this->TotalWeight = $weights[0]->total_weights;
      $this->TotalEmission = $weights[0]->total_footprints;
      $footprintRatioCalculator = new FootprintRatioCalculator();
      $this->EmissionRatio = $footprintRatioCalculator->calculateRatio();

  }

  public function index($all = false){

      if( $all ){

        //All groups only
        $your_groups = null;
        $groups_near_you = null;
        $groups = Group::orderBy('name', 'ASC')->paginate(env('PAGINATE'));
        $your_area = null;

        //Get all group tags
        $all_group_tags = GroupTags::all();

        return view('group.index', [
          'your_groups' => $your_groups,
          'groups_near_you' => $groups_near_you,
          'groups' => $groups,
          'your_area' => $your_area,
          'all' => $all,
          'all_group_tags' => $all_group_tags,
        ]);

      } else {

        $groups = null;

        //Get current logged in user
        $user = Auth::user();

        $your_area = $user->location;

        //Look for groups where user ID exists in pivot table
        $your_groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
                            ->where('users_groups.user', $user->id)
                              ->orderBy('name', 'ASC')
                                ->select('groups.*', 'users_groups.user')
                                  ->get();

        //Make sure we don't show the same groups in nearest to you
        $your_groups_uniques = $your_groups->pluck('idgroups')->toArray();

        //Assuming we have valid lat and long values, let's see what is nearest
        if( !is_null($user->latitude) && !is_null($user->longitude) ){
          $groups_near_you = Group::select(DB::raw('*, ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( latitude ) ) ) ) AS distance'))
                                  ->having("distance", "<=", 150)
                                    ->whereNotIn('idgroups', $your_groups_uniques)
                                      ->orderBy('distance', 'ASC')
                                        ->take(10)
                                          ->get();
        } else {
          $groups_near_you = null;
        }

      }

      return view('group.index', [
        'your_groups' => $your_groups,
        'groups_near_you' => $groups_near_you,
        'groups' => $groups,
        'your_area' => $your_area,
        'all' => $all,
      ]);

  }

  public function search(Request $request){

      //All groups only
      $your_groups = null;
      $groups_near_you = null;
      $groups = Group::orderBy('name', 'ASC');
      $your_area = null;

      if ($request->input('name') !== null) {
        $groups = $groups->where('name', 'like', '%'.$request->input('name').'%');
      }

      if ($request->input('location') !== null) {
        $groups = $groups->where('location', 'like', '%'.$request->input('location').'%');
      }

      if ($request->input('country') !== null) {
        //Don't store country for group???
      }

      if ($request->input('tags') !== null) {
        $groups = $groups->whereIn('idgroups', GrouptagsGroups::whereIn('group_tag', $request->input('tags'))->pluck('group'));
      }

      $groups = $groups->paginate(env('PAGINATE'));

      //Get all group tags
      $all_group_tags = GroupTags::all();

      return view('group.index', [
        'your_groups' => $your_groups,
        'groups_near_you' => $groups_near_you,
        'groups' => $groups,
        'your_area' => $your_area,
        'all' => true,
        'all_group_tags' => $all_group_tags,
        'name' => $request->input('name'),
        'location' => $request->input('location'),
        // 'selected_country' => $request->input('country'),
        'selected_tags' => $request->input('tags'),
      ]);

  }

  public function create(){

      $user = User::find(Auth::id());

      // Only administrators can add groups
      if( FixometerHelper::hasRole($user, 'Restarter') ){

            return redirect('/user/forbidden');

      } else {

          if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
              $error = array();
              $Group = new Group;

              // We got data! Elaborate. //NB:: Taken out frequency as it doesn't appear in the post data might be gmaps
              $name       =       $_POST['name'];
              $website    =       $_POST['website'];
              // $area       =       $_POST['area'];
              // $freq       =       $_POST['frequency'];
              $location   =       $_POST['location'];
              // $latitude   =       $_POST['latitude'];
              // $longitude  =       $_POST['longitude'];
              $text       =       $_POST['free_text'];




              if(empty($name)){
                  $error['name'] = 'Please input a name.';
              }
              // if(!empty($latitude) || !empty($longitude)) {
              //     // check that these values are floats.
              //     $check_lat = filter_var($latitude, FILTER_VALIDATE_FLOAT);
              //     $check_lon = filter_var($longitude, FILTER_VALIDATE_FLOAT);
              //
              //     if(!$check_lat || !$check_lon){
              //         $error['location'] = 'Coordinates must be in the correct format.';
              //     }
              //
              // }
              if (!empty($location)) {

                $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($location.',United Kingdom')."&key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE");
                $json = json_decode($json);

                if (is_object($json) && !empty($json->{'results'})) {
                    $latitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lat;
                    $longitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lng;
                }

              } else {
                $latitude = null;
                $longitude = null;
              }


              if(empty($error)) {
                  // No errors. We can proceed and create the User.
                  $data = array(  'name'          => $name,
                                  'website'       => $website,
                                  // 'area'          => $area,
                                  // 'frequency'     => $freq,
                                  'location'      => $location,
                                  'latitude'      => $latitude,
                                  'longitude'     => $longitude,
                                  'free_text'     => $text,
                                  );
                  $idGroup = $Group->create($data)->idgroups;

                  if( is_numeric($idGroup) && $idGroup !== false ){

                      $response['success'] = 'Group created correctly.';

                      if(isset($_FILES) && !empty($_FILES)){
                          $file = new FixometerFile;
                          $group_avatar = $file->upload('file', 'image', $idGroup, env('TBL_GROUPS'), false, true);
                      }

                      //Associate current logged in user as a host
                      UserGroups::create([
                        'user' => Auth::user()->id,
                        'group' => $idGroup,
                        'status' => 1,
                        'role' => 3,
                      ]);

                      if(env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {

                        $all_admins = User::where('role', 2)->get();

                        //Send Emails to Admins notifying event creation
                        $arr = [
                          'group_name' => Group::find($idGroup)->name,
                          'group_url' => url('/group/view/'.$idGroup),
                        ];

                        Notification::send($all_admins, new ModerationGroup($arr));

                        /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                        $Host = $Group->findHost($idGroup);

                        $custom_fields = array(
                                        array('key' => 'group_city',            'value' => $area),
                                        array('key' => 'group_host',            'value' => $Host->hostname),
                                        array('key' => 'group_website',         'value' => $website),
                                        array('key' => 'group_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' .$Host->path),
                                        array('key' => 'group_hash',            'value' => $idGroup),
                                        array('key' => 'group_avatar_url',      'value' => env('UPLOADS_URL') . 'mid_' . $group_avatar ),
                                        array('key' => 'group_latitude',        'value' => $data['latitude']),
                                        array('key' => 'group_longitude',       'value' => $data['longitude']),
                                        );


                        /** Start WP XML-RPC **/
                        $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                        $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);

                        $content = array(
                                        'post_type' => 'group',
                                        'custom_fields' => $custom_fields
                                        );

                        $wpid = $wpClient->newPost($data['name'], $text, $content);
                        $Group->update(array('wordpress_post_id' => $wpid), $idGroup);

                      }

                  }
                  else {
                      $response['danger'] = 'Group could <strong>not</strong> be created. Something went wrong with the database.';
                  }

              }
              else {
                  $response['danger'] = 'Group could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
              }


              // $this->set('response', $response);
              // $this->set('error', $error);
              // $this->set('udata', $_POST);

              if (!isset($response)) {
                $response = null;
              }

              if (!isset($error)) {
                $error = null;
              }

              if (!isset($_POST)) {
                $udata = null;
              } else {
                $udata = $_POST;
              }

              if( is_numeric($idGroup) && $idGroup !== false ){
                //NB: Dean I believe this is where this should go for sending the group moderation emails
                // $user = User::find($user_group->user);
                // try {
                //   $host = User::find(UserGroups::where('group', $group_id)->where('role', 3)->first()->user);
                // } catch (\Exception $e) {
                //   $host = null;
                // }
                //
                // if (!is_null($host)) {
                //   //Send Notification to Host
                //   $arr = [
                //     'group_name' => Group::find($group_id)->name,
                //     'group_url' => url('/group/view/'.$group_id),
                //   ];
                //
                //   Notification::send($host, new NewGroupMember($arr, $host));

                return redirect('/group/edit/'.$idGroup)->with('response', $response);
              } else {
                return view('group.create', [
                  'title' => 'New Group',
                  'gmaps' => true,
                  'response' => $response,
                  'error' => $error,
                  'udata' => $udata,
                ]);
              }

          }

          return view('group.create', [
            'title' => 'New Group',
            'gmaps' => true,
          ]);

      }
  }

  /** sync all parties to wordpress - CREATES PARTIES! **/
  public function sync(){
      /*
      $groups = $this->Group->findAll();

      foreach($groups as $i => $group) {
          $Host = $this->Group->findHost($group->id);
          $Logo = $this->Group->findOne($group->id);


          if(!empty($Logo->path)) {
              $logo =  UPLOADS_URL . 'mid_' . $Logo->path;
          }
          else {
              $logo = 'PLACEHOLDER';
          }

          if(!empty($Host->path)) {
              $hostpic =  UPLOADS_URL . 'mid_' . $Host->path;
          }
          else {
              $hostpic = 'PLACEHOLDER';
          }


          $custom_fields = array(
                          array('key' => 'group_city',            'value' => $group->area),
                          array('key' => 'group_host',            'value' => $Host->hostname),
                          array('key' => 'group_hostavatarurl',   'value' => $hostpic),
                          array('key' => 'group_hash',            'value' => $group->id),
                          array('key' => 'group_avatar_url',      'value' => $logo ),
                          );

          echo "Connecting ... ";
          $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
          $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);


          $content = array(
                      'post_type' => 'group',
                      'custom_fields' => $custom_fields
                      );

          $wpid = $wpClient->newPost($group->name, $group->free_text, $content);
          echo "<strong>Posted to WP</strong> ... ";
          $this->Group->update(array('wordpress_post_id' => $wpid), $group->id);
          echo "Updated Fixometer recordset with WPID: " . $wpid . "<br />";

      }
      */

  }

  public function view($groupid) {

      // $this->set('title', 'Host Dashboard');
      // $this->set('showbadges', true);
      // $this->set('charts', false);

      // $this->set('css', array('/components/perfect-scrollbar/css/perfect-scrollbar.min.css'));
      // $this->set('js', array('foot' => array('/components/perfect-scrollbar/js/min/perfect-scrollbar.jquery.min.js')));

      if(isset($_GET['action']) && isset($_GET['code'])){
          $actn = $_GET['action'];
          $code = $_GET['code'];

          switch($actn){
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
                  if($code == 200 ) { $response['success'] = 'Party deleted.'; }
                  elseif( $code == 403 ) { $response['danger'] = 'Couldn\'t delete the party!'; }
                  elseif( $code == 500 ) { $response['warning'] = 'The party has been deleted, but <strong>something went wrong while deleting it from WordPress</strong>. <br /> You\'ll need to do that manually!';  }
                  break;
          }

          // $this->set('response', $response);
      }

      $user = User::find(Auth::id());

      //Object Instances
      $Group  = new Group;
      $User   = new User;
      $Party  = new Party;
      $Device = new Device;
      $groups = $Group->ofThisUser($user->id);

      // get list of ids to check in if condition
      $gids = array();
      foreach($groups as $group){
        $gids[] = $group->idgroups;
      }

      if( ( isset($groupid) && is_numeric($groupid) ) || in_array($groupid, $gids) ) {

          //$group = (object) array_fill_keys( array('idgroups') , $groupid);
          $group = Group::where('idgroups', $groupid)->first();
          // $this->set('grouplist', $Group->findList());



      }
      else {
          $group = $groups[0];
          unset($groups[0]);
      }
      // $this->set('userGroups', $groups);

      $groupStats = $group->getGroupStats($this->EmissionRatio);
      $allPastEvents = Party::pastEvents()
                     ->with('devices.deviceCategory')
                     ->where('events.group', $group->idgroups)
                     ->get();


      // $this->set('pax', $participants);
      // $this->set('hours', $hours_volunteered);
      $weights = $Device->getWeights($group->idgroups);
      // $this->set('weights', $weights);

      $devices = $Device->ofThisGroup($group->idgroups);

      /*
      foreach($devices as $i => $device){

      }
      */

      // $this->set('need_attention', $need_attention);
      //
      // $this->set('group', $group);
      // $this->set('profile', $User->profilePage($this->user->id));
      //
      // $this->set('upcomingparties', $Party->findNextParties($group->idgroups));
      // $this->set('allparties', $allparties);
      //
      // $this->set('devices', $Device->ofThisGroup($group->idgroups));
      //
      //
      // $this->set('device_count_status', $Device->statusCount());
      // $this->set('group_device_count_status', $Device->statusCount($group->idgroups));

      // more stats...

      /** co2 counters **/
      $co2_years = $Device->countCO2ByYear($group->idgroups);
      // $this->set('year_data', $co2_years);
      $stats = array();
      foreach($co2_years as $year){
          $stats[$year->year] = $year->co2;
      }
      // $this->set('bar_chart_stats', array_reverse($stats, true));

      $waste_years = $Device->countWasteByYear($group->idgroups);

      //dbga($waste_years);

      // $this->set('waste_year_data', $waste_years);
      $wstats = array();
      foreach($waste_years as $year){
          $wstats[$year->year] = $year->waste;
      }
      // $this->set('waste_bar_chart_stats', array_reverse($wstats, true));


      // $co2Total = $Device->getWeights();
      $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));

      // $this->set('co2Total', $this->TotalEmission);
      // $this->set('co2ThisYear', $co2ThisYear[0]->co2);

      $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));

      // $this->set('wasteTotal', $this->TotalWeight);
      // $this->set('wasteThisYear', $wasteThisYear[0]->waste);


      $clusters = array();

      for($i = 1; $i <= 4; $i++) {
          $cluster = $Device->countByCluster($i, $group->idgroups);
          $total = 0;
          foreach($cluster as $state){
              $total += $state->counter;
          }
          $cluster['total'] = $total;
          $clusters['all'][$i] = $cluster;
      }


      for($y = date('Y', time()); $y>=2013; $y--){

          for($i = 1; $i <= 4; $i++) {
              //$cluster = $Device->countByCluster($i, $group->idgroups);
              $cluster = $Device->countByCluster($i, $group->idgroups, $y);

              $total = 0;
              foreach($cluster as $state){
                  $total += $state->counter;
              }
              $cluster['total'] = $total;
              $clusters[$y][$i] = $cluster;
          }
      }
      // $this->set('clusters', $clusters);

      // most/least stats for clusters
      $mostleast = array();
      for($i = 1; $i <= 4; $i++){
          $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i, $group->idgroups);
          $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i, $group->idgroups);
          $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i, $group->idgroups);

      }

      // $this->set('mostleast', $mostleast);
      //
      // $this->set('top', $Device->findMostSeen(1, null, $group->idgroups));

      if (!isset($response)) {
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
      $in_group = !empty(UserGroups::where('group', $groupid)
                          ->where('user', $user->id)
                            ->where(function ($query) {
                                $query->where('status', '1')
                                  ->orWhereNull('status');
                            })->first());

      $is_host_of_group = FixometerHelper::userHasEditGroupPermission($groupid, $user->id);

      $user_groups = UserGroups::where('user', Auth::user()->id)->count();
      $view_group = Group::find($groupid);

      return view('group.view', [ //host.index
        'title' => 'Host Dashboard',
        'showbadges' => true,
        'charts' => false,
        'response' => $response,
        'grouplist' => $Group->findList(),
        'userGroups' => $groups,
        'pax' => $groupStats['pax'],
        'hours' => $groupStats['hours'],
        'weights' => $weights,
        'group' => $group,
        'groupCo2' => $groupStats['co2'],
        'groupWaste' => $groupStats['waste'],
        'profile' => $User->getProfile($user->id),
        'upcomingparties' => $Party->findNextParties($group->idgroups),
        'allparties' => $allPastEvents,
        'devices' => $Device->ofThisGroup($group->idgroups),
        'device_count_status' => $Device->statusCount(),
        'group_device_count_status' => $Device->statusCount($group->idgroups),
        'year_data' => $co2_years,
        'bar_chart_stats' => array_reverse($stats, true),
        'waste_year_data' => $waste_years,
        'waste_bar_chart_stats' => array_reverse($wstats, true),
        'co2Total' => $Party->TotalEmission,
        'co2ThisYear' => $co2ThisYear[0]->co2,
        'wasteTotal' => $this->TotalWeight,
        'wasteThisYear' => $wasteThisYear[0]->waste,
        'clusters' => $clusters,
        'mostleast' => $mostleast,
        'top' => $Device->findMostSeen(1, null, $group->idgroups),
        'user' => $user,
        'upcoming_events' => $upcoming_events,
        'past_events' => $past_events,
        'EmissionRatio' => $Party->EmissionRatio,
        'in_group' => $in_group,
        'is_host_of_group' => $is_host_of_group,
        'user_groups' => $user_groups,
        'view_group' => $view_group,
      ]);

  }

  public function postSendInvite(Request $request) {

    $from_id = Auth::id();
    $group_name = $request->input('group_name');
    $group_id = $request->input('group_id');
    $emails = explode(',', str_replace(' ', '', $request->input('manual_invite_box')));
    $message = $request->input('message_to_restarters');

    if (!empty($emails)) {

      $users = User::whereIn('email', $emails)->get();

      $non_users = array_diff($emails, User::whereIn('email', $emails)->pluck('email')->toArray());
      $from = User::find($from_id);

      foreach ($users as $user) {

        $user_group = UserGroups::where('user', $user->id)->where('group', $group_id)->first();
        if (is_null($user_group) || $user_group->status != "1") {
          $hash = substr( bin2hex(openssl_random_pseudo_bytes(32)), 0, 24 );
          $url = url('/').'/group/accept-invite/'.$group_id.'/'.$hash;

          if (!is_null($user_group)) {
            $user_group->update([
              'status' => $hash,
            ]);
          } else {
            UserGroups::create([
              'user' => $user->id,
              'group' => $group_id,
              'status' => $hash,
              'role' => 4,
            ]);
          }

          Notification::send($user, new JoinGroup([
            'name' => $from->name,
            'group' => $group_name,
            'url' => $url,
            'message' => $message
          ], $user));

        } else {
          $not_sent[] = $user->email;
        }
      }

      if (!empty($non_users)) {

        foreach ($non_users as $non_user) {

          $hash = substr( bin2hex(openssl_random_pseudo_bytes(32)), 0, 24 );
          $url = url('/user/register/'.$hash);

          $invite = Invite::create(array(
            'record_id' => $group_id,
            'email' => $non_user,
            'hash' => $hash,
            'type' => 'group',
          ));

          Notification::send($invite, new JoinGroup([
            'name' => $from->name,
            'group' => $group_name,
            'url' => $url,
            'message' => $message
          ]));

        }

      }

      if (!isset($not_sent)) {
        return redirect()->back()->with('success', 'Invites sent!');
      } else {
        return redirect()->back()->with('warning', 'Invites sent - apart from these ('.rtrim(implode(', ', $not_sent), ', ').') who have already joined the group or have been sent an invite');
      }
      
    } else {
      return redirect()->back()->with('warning', 'You have not entered any emails!');
    }

  }

  public function confirmInvite($group_id, $hash) {

    $user_group = UserGroups::where('status', $hash)->where('group', $group_id)->first();

    if ( !empty($user_group) ) {

      UserGroups::where('status', $hash)->where('group', $group_id)->update([
        'status' => 1
      ]);

      $user = User::find($user_group->user);
      try {
        $host = User::find(UserGroups::where('group', $group_id)->where('role', 3)->first()->user);
      } catch (\Exception $e) {
        $host = null;
      }

      if (!is_null($host)) {
        //Send Notification to Host
        $arr = [
          'user_name' => $user->name,
          'group_name' => Group::find($group_id)->name,
          'group_url' => url('/group/view/'.$group_id),
          'preferences' => url('/profile/edit/'.$host->id),
        ];

        Notification::send($host, new NewGroupMember($arr, $host));
      }

      return redirect('/group/view/'.$user_group->group)->with('success', 'Excellent! You have joined the group');

    } else {
      return redirect('/group/view/'.$group_id)->with('warning', 'Something went wrong - this invite is invalid or has expired');
    }

  }


  public function edit($id) {

      $user = Auth::user();
      $Group = new Group;
      $File = new FixometerFile;

      $is_host_of_group = FixometerHelper::userHasEditGroupPermission($id, $user->id);
      if( !FixometerHelper::hasRole($user, 'Administrator') && !$is_host_of_group )
        return redirect('/user/forbidden');

      if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){

          $data = $_POST;
          // dd($data);

          // remove the extra "files" field that Summernote generates -
          unset($data['files']);
          unset($data['image']);

          if (!empty($data['location'])) {

            $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($data['location'].',United Kingdom')."&key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE");
            $json = json_decode($json);

            if (is_object($json) && !empty($json->{'results'})) {
                $latitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lat;
                $longitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lng;
            }

          } else {
            $latitude = null;
            $longitude = null;
          }

          //Validation
          if (empty($data['name'])) {
            return redirect()->back()->with('error', 'Group name must not be empty');
          }

          if (is_null($latitude) || is_null($longitude)) {
            return redirect()->back()->with('error', 'Invalid location - please try again!');
          }

          $update = array(
                          'name'          => $data['name'],
                          'website'       => $data['website'],
                          'free_text'     => $data['free_text'],
                          'location'      => $data['location'],
                          'latitude'      => $latitude,
                          'longitude'     => $longitude,
                          );

          $u = $Group->where('idgroups', $id)->update($update);


          if (!empty($_POST['group_tags'])) {

            $Group->find($id)->group_tags()->sync($_POST['group_tags']);

          }

          // echo "Updated---";
          if(!$u) {

              $response['danger'] = 'Something went wrong. Please check the data and try again.';
              echo $response['danger'];
          }
          else {
             // echo "Here now --- ";
              $response['success'] = 'Group updated!';

              if(isset($_FILES['image']) && !empty($_FILES['image']) && $_FILES['image']['error'] != 4){
                 // echo "uploading image ... ";
                  $existing_image = FixometerHelper::hasImage($id, 'groups', true);
                  if(count($existing_image) > 0){
                      $Group->removeImage($id, $existing_image[0]);
                  }
                  $file = new FixometerFile;
                  $group_avatar = $file->upload('image', 'image', $id, env('TBL_GROUPS'), false, true);
                  $group_avatar = env('UPLOADS_URL') . 'mid_' . $group_avatar ;
              }
              else {
                  $existing_image = FixometerHelper::hasImage($id, 'groups', true);
                  if( count($existing_image) > 0 ) {
                      $group_avatar = env('UPLOADS_URL') . 'mid_' . $existing_image[0]->path;
                  }
                  else {
                      $group_avatar = 'null';
                  }
              }

              if(false/*env('APP_ENV') != 'development' && env('APP_ENV') != 'local'*/) {

                 /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                $Host = $Group->findHost($id);

                $custom_fields = array(
                                    array('key' => 'group_city',            'value' => $data['area']),
                                    array('key' => 'group_host',            'value' => $Host->hostname),
                                    array('key' => 'group_website',         'value' => $data['website']),
                                    array('key' => 'group_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' . $Host->path),
                                    array('key' => 'group_hash',            'value' => $id),
                                    array('key' => 'group_avatar_url',      'value' => $group_avatar ),
                                    array('key' => 'group_latitude',        'value' => $data['latitude']),
                                    array('key' => 'group_longitude',       'value' => $data['longitude']),
                                );


                /** Start WP XML-RPC **/
                $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                $content = array(
                                'post_type' => 'group',
                                'post_title' => $data['name'],
                                'post_content' => $data['free_text'],
                                'custom_fields' => $custom_fields
                                );


                //Check for WP existence in DB
                $theGroup = $Group->findOne($id);
                if(!empty($theGroup->wordpress_post_id)){

                    // we need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                    $thePost = $wpClient->getPost($theGroup->wordpress_post_id);

                    foreach( $thePost['custom_fields'] as $i => $field ){
                        foreach( $custom_fields as $k => $set_field){
                            if($field['key'] == $set_field['key']){
                                $custom_fields[$k]['id'] = $field['id'];
                            }
                        }
                    }

                    $content['custom_fields'] = $custom_fields;
                    $wpClient->editPost($theGroup->wordpress_post_id, $content);
                }
                else {
                    $wpid = $wpClient->newPost($data['name'], $data['free_text'], $content);
                    $this->Group->update(array('wordpress_post_id' => $wpid), $id);
                }

              }

              if(FixometerHelper::hasRole($user, 'Host')){
              //    header('Location: /host?action=gu&code=200');
              }
          }

          // $this->set('response', $response);
      }
      // $this->set('gmaps', true);
      // $this->set('js', array( 'head' => array( '/ext/geocoder.js')));

      $group = $Group->findOne($id);
      // $this->set('title', 'Edit Group ' . $Group->name );
      // $this->set('formdata', $Group);

      if (!isset($response)) {
        $response = null;
      }

      $images = $File->findImages(env('TBL_GROUPS'), $id);

      if (!isset($images)) {
        $images = null;
      }

      $tags = GroupTags::all();
      $group_tags = GrouptagsGroups::where('group', $id)->pluck('group_tag')->toArray();

      return view('group.edit-group', [
        'response' => $response,
        'gmaps' => true,
        'title' => 'Edit Group ' . $group->name,
        'formdata' => $group,
        'user' => $user,
        'images' => $images,
        'tags' => $tags,
        'group_tags' => $group_tags,
      ]);

  }

  public function delete($id){
      if(FixometerHelper::hasRole($this->user, 'Administrator')){
          $r = $this->Group->delete($id);
          if(!$r){
              $response = 'd:err';
          }
          else {
              $response = 'd:ok';
          }
          header('Location: /group/index/' . $response);
      }
      else {
          header('Location: /user/forbidden');
      }
  }

  public function deleteImage($group_id, $id, $path){

      $user = Auth::user();

      $is_host_of_group = FixometerHelper::userHasEditGroupPermission($group_id, $user->id);
      if( FixometerHelper::hasRole($user, 'Administrator') || $is_host_of_group ){

          $Image = new FixometerFile;
          $Image->deleteImage($id, $path);

          return redirect()->back()->with('message', 'Thank you, the image has been deleted');

      }

      return redirect()->back()->with('message', 'Sorry, but the image can\'t be deleted');

  }

  public static function stats($id, $format = 'row')
  {
      $Device = new Device;

      $footprintRatioCalculator = new FootprintRatioCalculator();
      $emissionRatio = $footprintRatioCalculator->calculateRatio();

      $group = Group::where('idgroups', $id)->first();
      $groupStats = $group->getGroupStats($emissionRatio);

      $groupStats['format'] = $format;

      return view('group.stats', $groupStats);
  }

  public function getJoinGroup($group_id) {

    $user_id = Auth::id();
    $not_in_group = UserGroups::where('group', $group_id)
                                  ->where('user', $user_id)
                                    ->where('status', '!=', 1)
                                      ->first();

    if ( empty($not_in_group) ) {
      try {
        $user_group = UserGroups::updateOrCreate([
          'user' => $user_id,
          'group' => $group_id,
        ], [
          'status' => 1,
          'role' => 4,
        ]);

        $response['success'] = 'Thanks for joining, you are now part of this group!';

        return redirect()->back()->with('response', $response);

      } catch (\Exception $e) {
        $response['danger'] = 'Failed to join this group';

        return redirect()->back()->with('response', $response);
      }
    } else {
      $response['warning'] = 'You are already part of this group';

      return redirect()->back()->with('response', $response);
    }

  }

  public function imageUpload(Request $request, $id) {

    try {
      if(isset($_FILES) && !empty($_FILES)){
          $existing_image = FixometerHelper::hasImage($id, 'groups', true);
          if(count($existing_image) > 0){
              $Group->removeImage($id, $existing_image[0]);
          }
          $file = new FixometerFile;
          $file->upload('file', 'image', $id, env('TBL_GROUPS'), false, true, true);
      }

      return "success - image uploaded";
    } catch (\Exception $e) {
      return "fail - image could not be uploaded";
    }
  }

  public function ajaxDeleteImage($group_id, $id, $path){

      $user = Auth::user();

      $is_host_of_group = FixometerHelper::userHasEditGroupPermission($group_id, $user->id);
      if(FixometerHelper::hasRole($user, 'Administrator') || $is_host_of_group ){

          $Image = new FixometerFile;
          $Image->deleteImage($id, $path);

          return 'Thank you, the image has been deleted';

      }

      return 'Sorry, but the image can\'t be deleted';

  }

  // public function test() {
  //   $g = new Group;
  //   dd($g->findOne('1'));
  // }
}
