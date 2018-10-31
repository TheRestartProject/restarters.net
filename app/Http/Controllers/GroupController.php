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
use App\Notifications\AdminModerationGroup;
use App\Notifications\NewGroupWithinRadius;
use DB;
use FixometerHelper;
use FixometerFile;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Notification;
use App\Events\ApproveGroup;
use App\Events\EditGroup;

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

          $idGroup = Group::find($idGroup);
          $lat1 = $idGroup->latitude;
          $lon1 = $idGroup->longitude;

          $idGroup = $idGroup->idgroups;

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

          // Notify relevant users
          $notify_users = FixometerHelper::usersWhoHavePreference('admin-moderate-group');
          Notification::send($notify_users, new AdminModerationGroup([
            'group_name' => Group::find($idGroup)->name,
            'group_url' => url('/group/edit/'.$idGroup),
          ]));

          // -------------------------------------------------- NOTIFY USERS OF NEW GROUP WITHIN 25 MILES ------------------------------------------------- //
          // Get all Users
          $users = User::whereNotNull('location')->get();
          foreach ( $users as $user ) {

            $lat2 = $user->latitude;
            $lon2 = $user->longitude;

            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $miles * 0.8684;

            //If calculated distance is less than 25 for the user then send notification...
            if($miles <= 25){
              $arr = [
                'group_name' => $name,
                'group_url' => url('/group/view/'.$idGroup),
              ];
              Notification::send($user, new NewGroupWithinRadius($arr));

            }
          }
          // -------------------------------------------------- END NOTIFY USERS OF NEW GROUP WITHIN 25 MILES -------------------------------------------------- //

        }
        else {
          $response['danger'] = 'Group could <strong>not</strong> be created. Something went wrong with the database.';
        }

      }
      else {
        $response['danger'] = 'Group could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
      }

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

    $hasPendingInvite = !empty(UserGroups::where('group', $groupid)
    ->where('user', $user->id)
    ->where(function ($query) {
      $query->where('status', '<>', '1')
      ->whereNotNull('status');
    })->first());

    return view('group.view', [ //host.index
      'title' => 'Host Dashboard',
      'has_pending_invite' => $hasPendingInvite,
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
    if (is_null($user_group) || $user_group->status != "1") {
      $hash = substr( bin2hex(openssl_random_pseudo_bytes(32)), 0, 24 );
      $url = url('/').'/group/accept-invite/'.$group_id.'/'.$hash;

      // already been invited once, set a new invite hash
      if (!is_null($user_group)) {
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
          'message' => $message
        ], $user));
      } else {
        $not_sent[] = $user->email;
      }

    } else { // already a confirmed member of the group or been sent an invite
      $not_sent[] = $user->email;
    }
  }

  // users not on the platform
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
    return redirect()->back()->with('warning', 'Invites sent - apart from these ('.rtrim(implode(', ', $not_sent), ', ').') who have already joined the group, have already been sent an invite, or have not opted in to receive emails');
  }
}

public function confirmInvite($group_id, $hash)
{
  // Find user/group relationship based on the invitation hash.
  $user_group = UserGroups::where('status', $hash)->where('group', $group_id)->first();
  if ( empty($user_group) )
    return redirect('/group/view/'.$group_id)->with('warning', 'Something went wrong - this invite is invalid or has expired');

  // Set user as confirmed member of group.
  UserGroups::where('status', $hash)->where('group', $group_id)->update([
    'status' => 1
  ]);

  // Send emails to hosts of group to let them know.
  // (only those that have opted in to receiving emails).
  $user = User::find($user_group->user);

  $group_hosts = User::join('users_groups', 'users_groups.user', '=', 'users.id')
                        ->where('users_groups.group', $group_id)
                          ->where('users_groups.role', 3)
                            ->where('users.invites', 1)
                              ->select('users.*')
                                ->get();

  if ( !empty($group_hosts) ) {

    Notification::send($group_hosts, new NewGroupMember([
      'user_name' => $user->name,
      'group_name' => Group::find($group_id)->name,
      'group_url' => url('/group/view/'.$group_id),
    ]));

  }

  return redirect('/group/view/'.$user_group->group)->with('success', 'Excellent! You have joined the group');
}


public function edit($id)
{
  $user = Auth::user();
  $Group = new Group;
  $File = new FixometerFile;

  $is_host_of_group = FixometerHelper::userHasEditGroupPermission($id, $user->id);
  if (!FixometerHelper::hasRole($user, 'Administrator') && !$is_host_of_group)
  return redirect('/user/forbidden');

  if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {

    $data = $_POST;

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

    // $u = $Group->where('idgroups', $id)->update($update);
    $u = Group::findOrFail($id)->update($update);

    // Yet to be used
    // event(new ApproveGroup(Group::findOrFail($id)));
    // event(new EditGroup(Group::findOrFail($id)));


    if (!empty($_POST['group_tags'])) {

      $Group->find($id)->group_tags()->sync($_POST['group_tags']);

    }

    if(!$u) {
      $response['danger'] = 'Something went wrong. Please check the data and try again.';
      echo $response['danger'];
    }
    else {
      $response['success'] = 'Group updated!';

      if(isset($_FILES['file']) && !empty($_FILES['file']) && $_FILES['file']['error'] != 4){
        $existing_image = FixometerHelper::hasImage($id, 'groups', true);
        if(count($existing_image) > 0){
          //$Group = new Group;
          //$Group->removeImage($id, $existing_image[0]);
        }
        $file = new FixometerFile;
        $group_avatar = $file->upload('file', 'image', $id, env('TBL_GROUPS'), false, true);
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

      $shouldUpdateWordpress = env('APP_ENV') != 'development' && env('APP_ENV') != 'local';
      if ($shouldUpdateWordpress) {
        try {
          $this->updateGroupInWordpress($id, $data, $group_avatar, $latitude, $longitude);
        } catch (\Exception $ex) {
          $reponse['success'] = 'error pushing to wp';
          report($ex);
        }
      }
    }
  }

  $group = $Group->findOne($id);

  if (!isset($response)) {
    $response = null;
  }

  $images = $File->findImages(env('TBL_GROUPS'), $id);

  if (!isset($images)) {
    $images = null;
  }

  $tags = GroupTags::all();
  $group_tags = GrouptagsGroups::where('group', $id)->pluck('group_tag')->toArray();

  compact($audits = $Group->findOrFail($id)->audits);

  return view('group.edit-group', [
    'response' => $response,
    'gmaps' => true,
    'title' => 'Edit Group ' . $group->name,
    'formdata' => $group,
    'user' => $user,
    'images' => $images,
    'tags' => $tags,
    'group_tags' => $group_tags,
    'audits' => $audits,
  ]);
}


/** Not currently in use.
public function createGroupInWordpress()
{
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


$wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
$wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);

$content = array(
'post_type' => 'group',
'custom_fields' => $custom_fields
);

$wpid = $wpClient->newPost($data['name'], $text, $content);
$Group->update(array('wordpress_post_id' => $wpid), $idGroup);
}
*/

public function updateGroupInWordpress($id, $data, $group_avatar, $latitude, $longitude)
{
  // TODO: host.  Groups don't just have one host.  Is host
  // displayed on the front-end anywhere?
  // TODO: receiving area field from posted data.  It's currently not in the interface.
  $group = Group::where('idgroups', $id)->first();

  $custom_fields = array(
    array('key' => 'group_city',            'value' => $group->area),
    //                                    array('key' => 'group_host',            'value' => $Host->hostname),
    array('key' => 'group_website',         'value' => $data['website']),
    //array('key' => 'group_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' . $Host->path),
    array('key' => 'group_hash',            'value' => $id),
    array('key' => 'group_avatar_url',      'value' => $group_avatar ),
    array('key' => 'group_latitude',        'value' => $latitude),
    array('key' => 'group_longitude',       'value' => $longitude),
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


  if(!empty($group->wordpress_post_id)) {
    // We need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
    $existingPost = $wpClient->getPost($group->wordpress_post_id);

    foreach ($existingPost['custom_fields'] as $i => $field) {
      foreach ($custom_fields as $k => $set_field) {
        if($field['key'] == $set_field['key']) {
          $custom_fields[$k]['id'] = $field['id'];
        }
      }
    }

    $content['custom_fields'] = $custom_fields;
    $wpClient->editPost($group->wordpress_post_id, $content);
  }
  else {
    $wpid = $wpClient->newPost($data['name'], $data['free_text'], $content);
    $group->wordpress_post_id = $wpid;
    $group->save();
  }
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
  $alreadyInGroup = UserGroups::where('group', $group_id)
  ->where('user', $user_id)
  ->where('status', 1)
  ->exists();

  if ($alreadyInGroup) {
    $response['warning'] = 'You are already part of this group';

    return redirect()->back()->with('response', $response);
  };

  try {
    $user_group = UserGroups::updateOrCreate([
      'user' => $user_id,
      'group' => $group_id,
    ], [
      'status' => 1,
      'role' => 4,
    ]);


      // A new User has joined your group
      $group = Group::find($group_id);
      $groupHostLinks = UserGroups::where('group', $group->idgroups)->where('role', 3)->get();

      foreach ($groupHostLinks as $groupHostLink) {
        $host = User::where('id', $groupHostLink->user)->first();
        if ($host->invites == 1) {
          $arr = [
            'user_name' => Auth::user()->name,
            'group_name' => $group->name,
            'group_url' => url('/group/view/'.$group->idgroups),
            'preferences' => url('/profile/edit/'.$host->id),
          ];
          Notification::send($host, new NewGroupMember($arr, $host));
        }
      }


    $response['success'] = 'Thanks for joining, you are now part of this group!';

    return redirect()->back()->with('response', $response);

  } catch (\Exception $e) {
    $response['danger'] = 'Failed to join this group';

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

public function getMakeHost($group_id, $user_id, Request $request) {

  //Has current logged in user got permission to add host
  if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userIsHostOfGroup($group_id, Auth::id()) ) || FixometerHelper::hasRole(Auth::user(), 'Administrator') ) {

    // Retrieve user
    $user = User::find($user_id);

    // Let's make the user a host
    $volunteer = UserGroups::where('user', $user_id)
    ->where('group', $group_id)
    ->update([
      'role' => 3
    ]);

    // Update user's role as well, but don't demote admins!
    $update_role = User::where('id', $user_id)
    ->where('role', '>', 3)
    ->update([
      'role' => 3
    ]);

    return redirect()->back()->with('success', 'We have made '.$user->name.' a host for this group');

  }

  return redirect()->back()->with('warning', 'Sorry, you do not have permission to do this');

}

public function getRemoveVolunteer($group_id, $user_id, Request $request) {

  //Has current logged in user got permission to remove volunteer
  if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userIsHostOfGroup($group_id, Auth::id()) ) || FixometerHelper::hasRole(Auth::user(), 'Administrator') ) {

    // Retrieve user
    $user = User::find($user_id);

    //Let's delete the user
    $delete_user = UserGroups::where('user', $user_id)->where('group', $group_id)->delete();
    if( $delete_user == 1 ){

      return redirect()->back()->with('success', 'We have removed '.$user->name.' from this group');

    } else {

      return redirect()->back()->with('warning', 'We are unable to remove '.$user->name.' from this group');

    }

  } else {

    return redirect()->back()->with('warning', 'Sorry, you do not have permission to do this');

  }

}

    public function volunteersNearby($groupid)
    {
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
      if( !is_null($group->latitude) && !is_null($group->longitude) ){
          $restarters_nearby = User::select(DB::raw('*, ( 6371 * acos( cos( radians('.$group->latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$group->longitude.') ) + sin( radians('.$group->latitude.') ) * sin( radians( latitude ) ) ) ) AS distance'))
                           ->having("distance", "<=", 20)
                           ->orderBy('name', 'ASC')
                           ->get();
          foreach ($restarters_nearby as $restarter) {
              $membership = UserGroups::where('user', $restarter->id)->where('group', $groupid)->first();
              $restarter->notAMember = $membership == null;
              $restarter->hasPendingInvite = !empty(UserGroups::where('group', $groupid)
                                         ->where('user', $restarter->id)
                                         ->where(function ($query) {
                                             $query->where('status', '<>', '1')
                                                 ->whereNotNull('status');
                                         })->first());
          }
      } else {
          $restarters_nearby = null;
      }

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

      $hasPendingInvite = !empty(UserGroups::where('group', $groupid)
                                             ->where('user', $user->id)
                                             ->where(function ($query) {
                                                 $query->where('status', '<>', '1')
                                                       ->whereNotNull('status');
                                             })->first());

      return view('group.nearby', [ //host.index
        'title' => 'Host Dashboard',
        'has_pending_invite' => $hasPendingInvite,
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
        'group_id' => $groupid,
        'restarters_nearby' => $restarters_nearby
      ]);
    }

    public function inviteNearbyRestarter($groupId, $userId)
    {
        $user_group = UserGroups::where('user', $userId)->where('group', $groupId)->first();
        $user = User::where('id', $userId)->first();

        // not already a confirmed member of the group
        if (is_null($user_group) || $user_group->status != "1") {
            $hash = substr( bin2hex(openssl_random_pseudo_bytes(32)), 0, 24 );
            $url = url('/').'/group/accept-invite/'.$groupId.'/'.$hash;

            // already been invited once, set a new invite hash
            if (!is_null($user_group)) {
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
                        'message' => null
                    ], $user));
                } else {
                    $not_sent[] = $user->email;
                }
            } catch (\Exception $ex) {
                Log::error("An error occurred while sending invitation to nearby Restarter:" . $ex->getMessage());
            }

        } else { // already a confirmed member of the group or been sent an invite
            $not_sent[] = $user->email;
        }

        return redirect('/group/nearby/' . $groupId)->with('success', $user->name . ' has been invited');
    }

}
