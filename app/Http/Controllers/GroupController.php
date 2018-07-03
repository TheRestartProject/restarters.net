<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Group;
use App\Party;
use App\Device;
use App\UserGroups;
use App\GroupTags;
use App\GrouptagsGroups;

use Notification;
use App\Notifications\JoinGroup;

use Auth;
use FixometerHelper;
use FixometerFile;

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
      $this->EmissionRatio = $this->TotalEmission / $this->TotalWeight;

  }

  public function index($response = null){

      // $this->set('title', 'Groups');
      // $this->set('list', $this->Group->findAll());

      // if(!is_null($response)){
      //     $this->set('response', $response);
      // }

      // $Group = new Group;

      $groups = Group::orderBy('name', 'ASC')
          ->paginate(env('PAGINATE'));

      return view('group.index', [
        // 'title' => 'Groups',
        // 'list' => $Group->findAll(),
        'groups' => $groups,
        //'response' => $response,
      ]);

  }

  public function create(){

      $user = User::find(Auth::id());

      // Administrators can add Groups.
      if(FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::hasRole($user, 'Host')){
          // $this->set('title', 'New Group');
          // $this->set('gmaps', true);
          // $this->set('js',
          //             array('head' => array(
          //                             '/ext/geocoder.js'
          //             )));

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

                      if(env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {

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
      else {
          return redirect('/user/forbidden');
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
          $group = $Group->findOne($groupid);
          // $this->set('grouplist', $Group->findList());



      }
      else {
          $group = $groups[0];
          unset($groups[0]);
      }
      // $this->set('userGroups', $groups);

      $allparties = $Party->ofThisGroup($group->idgroups, true, true);

      $participants = 0;
      $hours_volunteered = 0;

      $need_attention = 0;
      foreach($allparties as $i => $party){
          if($party->device_count == 0){
              $need_attention++;
          }

          $party->co2 = 0;
          $party->fixed_devices = 0;
          $party->repairable_devices = 0;
          $party->dead_devices = 0;

          $participants += $party->pax;
          $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);

          foreach($party->devices as $device){
              if($device->repair_status == env('DEVICE_FIXED')){
                  $party->co2 += (!empty((float)$device->estimate) && $device->category == 46 ? ((float)$device->estimate * $this->EmissionRatio) : (float)$device->footprint);
              }

              switch($device->repair_status){
                  case 1:
                      $party->fixed_devices++;
                      break;
                  case 2:
                      $party->repairable_devices++;
                      break;
                  case 3:
                      $party->dead_devices++;
                      break;
              }
          }

          $party->co2 = number_format(round($party->co2 * $Device->displacement), 0, '.' , ',');
      }
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
                            ->first());

      $is_host_of_group = !empty(UserGroups::where('group', $groupid)
                                  ->where('user', $user->id)
                                    ->where('role', 3)
                                      ->first());

      $user_groups = UserGroups::where('user', Auth::user()->id)->count();


      return view('group.view', [ //host.index
        'title' => 'Host Dashboard',
        'showbadges' => true,
        'charts' => false,
        'response' => $response,
        'grouplist' => $Group->findList(),
        'userGroups' => $groups,
        'pax' => $participants,
        'hours' => $hours_volunteered,
        'weights' => $weights,
        'need_attention' => $need_attention,
        'group' => $group,
        'profile' => $User->getProfile($user->id),
        'upcomingparties' => $Party->findNextParties($group->idgroups),
        'allparties' => $allparties,
        'devices' => $Device->ofThisGroup($group->idgroups),
        'device_count_status' => $Device->statusCount(),
        'group_device_count_status' => $Device->statusCount($group->idgroups),
        'year_data' => $co2_years,
        'bar_chart_stats' => array_reverse($stats, true),
        'waste_year_data' => $waste_years,
        'waste_bar_chart_stats' => array_reverse($wstats, true),
        'co2Total' => $this->TotalEmission,
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
      // if (isset($invite_group) && $invite_group == 1) {
      //   $group_user_ids = UserGroups::where('group', Party::find($event_id)->group)->pluck('user')->toArray();
      //   $group_users = User::whereIn('id', $group_user_ids)->get();
      //
      //   $users = $users->merge($group_users);
      // }
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

          $arr = array(
            'name' => $from->name,
            'group' => $group_name,
            'url' => $url,
            'message' => $message,
          );
          Notification::send($user, new JoinGroup($arr));
        } else {
          $not_sent[] = $user->email;
        }
      }

      if (!empty($non_users)) {
        foreach ($non_users as $non_user) {
          $hash = substr( bin2hex(openssl_random_pseudo_bytes(32)), 0, 24 );
          $url = url('/user/register/'.$hash);

          $invite = Invite::create(array(
            'event_id' => $group_id,
            'email' => $non_user,
            'hash' => $hash,
          ));

          $arr = array(
            'name' => $from->name,
            'group' => $group_name,
            'url' => $url,
            'message' => $message,
          );
          Notification::send($invite, new JoinGroup($arr));
        }
      }


      if (!isset($not_sent)) {
        return redirect()->back()->with('success', 'Invites Sent!');
      } else {
        return redirect()->back()->with('warning', 'Invites Sent - apart from these ('.implode(',', $not_sent).') who were already part of the group');
      }
    } else {
      return redirect()->back()->with('warning', 'You have not entered any emails!');
    }

  }

  public function confirmInvite($group_id, $hash, $register_id = null) {

    try {
      $user_group = UserGroups::where('status', $hash)->where('group', $group_id)->first();
      $user_group->status = 1;

      if (is_null($register_id)) {
        $user_group->save();
        return redirect('/group/view/'.$user_group->group)->with('success', 'Excellent! You have joined the group');
      } else {
        $user_group->user = $register_id;
        $user_group->save();
      }
    } catch (\Exception $e) {
      return false;
    }

  }


  public function edit($id) {

      $user = Auth::user();
      $Group = new Group;
      $File = new FixometerFile;

      if( FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::hasRole($user, 'Host') ){

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

                  // dbga($_FILES);

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

                  if(env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {

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
      }
      // $this->set('gmaps', true);
      // $this->set('js', array( 'head' => array( '/ext/geocoder.js')));

      $group = $Group->findOne($id);
      // $this->set('title', 'Edit Group ' . $Group->name );
      // $this->set('formdata', $Group);

      if (!isset($response)) {
        $response = null;
      }

      $images = $File->findImages(env('TBL_EVENTS'), $id);

      if (!isset($images)) {
        $images = null;
      }

      $tags = GroupTags::all();

      return view('group.edit-group', [
        'response' => $response,
        'gmaps' => true,
        'title' => 'Edit Group ' . $group->name,
        'formdata' => $group,
        'user' => $user,
        'images' => $images,
        'tags' => $tags,
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

  public static function stats($id, $format = 'row'){

      $Party = new Party;
      $Device = new Device;


      $weights = $Device->getWeights();
      $TotalWeight = $weights[0]->total_weights;
      $TotalEmission = $weights[0]->total_footprints;
      $EmissionRatio = $TotalEmission / $TotalWeight;


      $allparties = $Party->ofThisGroup($id, true, true);

      $participants = 0;
      $hours_volunteered = 0;
      $co2 = 0;
      $waste = 0;

      foreach($allparties as $i => $party){
          $partyco2 = 0;
          $participants += $party->pax;
          $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);

          foreach($party->devices as $device){
              if($device->repair_status == env('DEVICE_FIXED')){
              //  echo (!empty($device->estimate) && $device->category == 46 ? ( " Adding ESTIMATED: " . $device->estimate * $EmissionRatio . " - COEFF: " . $EmissionRatio . "<br />") : 'Adding ' . $device->footprint . '<br />');
                  $partyco2 +=     (!empty($device->estimate) && $device->category == 46 ? (intval($device->estimate) * $EmissionRatio) : $device->footprint);
                  $waste +=   (!empty($device->estimate) && $device->category == 46 ? intval($device->estimate) : $device->weight);
              }

          }
          $partyco2 =  intval(number_format(round($partyco2 * $Device->displacement), 0, '.' , ','));
          $co2 += $partyco2;

      }

      $waste = number_format(round($waste), 0, '.', ',');

      // $this->set('pax', $participants);
      // $this->set('hours', $hours_volunteered);
      // $this->set('parties', count($allparties));
      // $this->set('co2', $co2);
      // $this->set('waste', $waste);
      // $this->set('format', $format);

      return view('group.stats', [
        'pax' => $participants,
        'hours' => $hours_volunteered,
        'parties' => count($allparties),
        'co2' => $co2,
        'waste' => $waste,
        'format' => $format,
      ]);

  }

  public function getJoinGroup($group_id) {
    $user_id = Auth::id();

    $not_in_group = empty(UserGroups::where('group', $group_id)->where('user', $user_id)->first());

    if ($not_in_group) {
      try {
        $user_group = UserGroups::create([
          'user' => $user_id,
          'group' => $group_id,
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

  // public function test() {
  //   $g = new Group;
  //   dd($g->findOne('1'));
  // }
}
