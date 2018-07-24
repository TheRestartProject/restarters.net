<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Category;
use App\Cluster;
use App\Device;
use App\EventsUsers;
use App\Group;
use App\Host;
use App\Invite;
use App\Party;
use App\Session;
use App\User;
use App\UserGroups;
use Auth;
use App\Helpers\FootprintRatioCalculator;
use App\Notifications\JoinEvent;
use App\Notifications\JoinGroup;
use App\Notifications\RSVPEvent;
use App\Notifications\ModerationEvent;
use App\Notifications\EventDevices;
use App\Notifications\EventRepairs;
use DateTime;
use FixometerFile;
use FixometerHelper;
use Illuminate\Http\Request;
use Notification;

class PartyController extends Controller {

  // protected $hostParties = array();
  // protected $permissionsChecker;

  public $TotalWeight;
  public $TotalEmission;
  public $EmissionRatio;

  public function __construct(){ //($model, $controller, $action)

      // parent::__construct($model, $controller, $action);
      //
      // $Auth = new Auth($url);
      // if(!$Auth->isLoggedIn() && $action != 'stats'){
      //     header('Location: /user/login');
      // }
      //
      // $user = $Auth->getProfile();
      // $this->user = $user;
      // $this->set('user', $user);
      // $this->set('header', true);
      //
      // if (hasRole($this->user, 'Host'))
      // {
      //     $Group = new Group;
      //     $group = $Group->ofThisUser($this->user->id);
      //     $this->set('usergroup', $group[0]);
      //     $parties = $this->Party->ofThisGroup($group[0]->idgroups);
      //
      //     foreach($parties as $party){
      //         $this->hostParties[] = $party->idevents;
      //     }
      //     $User = new User;
      //     $this->set('profile', $User->profilePage($this->user->id));

      $Device = new Device;
      $weights = $Device->getWeights();

      $this->TotalWeight = $weights[0]->total_weights;
      $this->TotalEmission = $weights[0]->total_footprints;

      $footprintRatioCalculator = new FootprintRatioCalculator();
      $this->EmissionRatio = $footprintRatioCalculator->calculateRatio();
      // }
      //
      // $this->permissionsChecker = new PermissionsChecker($this->user, $this->hostParties);
  }

  public function index($group_id = null)
  {

      if( FixometerHelper::hasRole(Auth::user(), 'Administrator') ){
        $moderate_events = Party::RequiresModeration()->get();
      } else {
        $moderate_events = null;
      }

      //Use this view for showing group only upcoming and past events
      if( !is_null($group_id) ){

        $upcoming_events = Party::upcomingEvents()
                              ->where('events.group', $group_id)
                                  ->get();

        $past_events = Party::pastEvents()
                              ->where('events.group', $group_id)
                                  ->paginate(env('PAGINATE'));

        $group = Group::find($group_id);

      } else {

        $upcoming_events = Party::upcomingEvents()
                              ->where('users_groups.user', Auth::user()->id)
                                ->take(5)
                                  ->get();

        $past_events = Party::pastEvents()->paginate(env('PAGINATE'));

        $group = null;

      }

      //Looks to see whether user has a group already, if they do, they can create events
      $user_groups = UserGroups::where('user', Auth::user()->id)->count();

      return view('events.index', [
        'upcoming_events'  => $upcoming_events,
        'past_events'      => $past_events,
        'moderate_events'  => $moderate_events,
        'user_groups'      => $user_groups,
        'EmissionRatio'    => $this->EmissionRatio,
        'group'            => $group,
      ]);

  }

  public function allUpcoming()
  {

      $all_upcoming_events = Party::allUpcomingEvents()->paginate(env('PAGINATE'));

      return view('events.all', [
        'upcoming_events'  => $all_upcoming_events,
      ]);

  }

  public function create()
  {
      $user = Auth::user();

      if( FixometerHelper::hasRole(Auth::user(), 'Restarter') )
        return redirect('/user/forbidden');

      $Groups = new Group;
      $Party = new Party;

      // $this->set('grouplist', $Groups->findList());
      //
      // $this->set('title', 'New Party');
      // $this->set('gmaps', true);
      // $this->set('js',
      //             array('head' => array(
      //                             '/ext/geocoder.js'
      //             )));
      //
      // $this->set('group_list', $Groups->findAll());

      if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {


          $error = array();

          // Add SuperHero Restarter!
          // $_POST['users'][] = 29;
          // if(empty($_POST['volunteers'])) {
          //     $volunteers = count($_POST['users']);
          // }
          // else {
          //     $volunteers = $_POST['volunteers'];
          // }

          if (!empty($_POST['location'])) {

            $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($_POST['location'].',United Kingdom')."&key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE");
            $json = json_decode($json);

            if (is_object($json) && !empty($json->{'results'})) {
                $latitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lat;
                $longitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lng;
            }

          } else {
            $latitude = null;
            $longitude = null;
          }

          // We got data! Elaborate.
          $event_date =       $_POST['event_date'];
          $start      =       $_POST['start'];
          $end        =       $_POST['end'];
          $pax        =       0;
          $free_text  =       $_POST['free_text'];
          $venue      =       $_POST['venue'];
          $location   =       $_POST['location'];
          // $latitude   =       $latitude;
          // $longitude  =       $longitude;
          $group      =       intval($_POST['group']);


          // saving this for wordpress
          $wp_date = $event_date;

          // formatting dates for the DB
          $event_date = date('Y-m-d', strtotime($event_date));

          if(!FixometerHelper::verify($event_date)){
              $error['event_date'] = 'We must have a starting date and time.';
          }
          if(!FixometerHelper::verify($start)){
              $error['name'] = 'We must have a starting date and time.';
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


          if(empty($error)) {

              $startTime = $event_date . ' ' . $start;
              $endTime = $event_date . ' ' . $end;

              $dtStart = new DateTime($startTime);
              $dtDiff = $dtStart->diff(new DateTime($endTime));

              $hours = $dtDiff->h;

              // No errors. We can proceed and create the Party.
              $data = array(
                              'event_date'    => $event_date,
                              'start'         => $start,
                              'end'           => $end,
                              'pax'           => $pax,
                              'free_text'     => $free_text,
                              'venue'         => $venue,
                              'location'      => $location,
                              'latitude'      => $latitude,
                              'longitude'     => $longitude,
                              'group'         => $group,
                              'hours'         => $hours,
                              // 'volunteers'    => $volunteers,
                              'created_at'    => date('Y-m-d H:i:s')
                            );
              $idParty = $Party->insertGetId($data);



              if($idParty){

                  /** check and create User List **/
                  $_POST['users'][] = 29;
                  if(isset($_POST['users']) && !empty($_POST['users'])){
                      $users = $_POST['users'];
                      $Party->createUserList($idParty, $users);
                  }

                  EventsUsers::create([
                    'event' => $idParty,
                    'user' => $user->id,
                    'status' => 1,
                    'role' => 3,
                  ]);

                  Party::find($idParty)->increment('volunteers');

                  if(env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {
                    $all_admins = User::where('role', 2)->get();

                    //Send Emails to Admins notifying event creation
                    $arr = [
                      'event_venue' => Party::find($idParty)->venue,
                      'event_url' => url('/party/view/'.$idParty),
                    ];

                    Notification::send($all_admins, new ModerationEvent($arr));
                  }


                  /** let's create the image attachment! **/
                  // if(isset($_FILES) && !empty($_FILES)){
                  //     $file = new FixometerFile;
                  //     $file->upload('file', 'image', $idParty, env('TBL_EVENTS'));
                  // }
                  if(isset($_FILES) && !empty($_FILES)){
                      if(is_array($_FILES['file']['name'])) {
                          $File = new FixometerFile;
                          $files = FixometerHelper::rearrange($_FILES['file']);
                          foreach($files as $upload){
                              $File->upload($upload, 'image', $idParty, env('TBL_EVENTS'));
                          }
                      }
                      else { }
                  }

                  if(FixometerHelper::hasRole($user, 'Host')){

                      // $this->sendCreationNotificationEmail($venue, $location, $event_date, $start, $end, $group);
                      // header('Location: /host?action=pc&code=200');

                  } else if(FixometerHelper::hasRole($user, 'Administrator')){
                    // header('Location: /admin?action=pc&code=200');
                  }
               }
              else {
                  $response['danger'] = 'Party could <strong>not</strong> be created. Something went wrong with the database.';
              }

          }
          else {
              $response['danger'] = 'Party could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
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

          if(is_numeric($idParty)){

            return redirect('/party/edit/'.$idParty)->with('response', $response);

          } else {

            return view('events.create', [ //party.create
              'title' => 'New Party',
              'grouplist' => $Groups->findList(),
              'gmaps' => true,
              'group_list' => $Groups->findAll(),
              'response' => $response,
              'error' => $error,
              'udata' => $_POST,
              'user' => $user,
              'user_groups' => $user_groups,
            ]);
          }
      }

      $groups_user_is_host_of = UserGroups::where('user', Auth::user()->id)
                              ->where('role', 3)
                              ->pluck('group')
                              ->toArray();

      return view('events.create', [ //party.create
        'title' => 'New Party',
        'grouplist' => $Groups->findList(),
        'gmaps' => true,
        'group_list' => $Groups->findAll(),
        'user' => $user,
        'user_groups' => $groups_user_is_host_of,
      ]);

  }

  public function sendCreationNotificationEmail($venue, $location, $event_date, $start, $end, $group_id){
      $Groups = new Group;

      $group = $Groups->findOne($group_id);
      $group_name = $group->name;

      $hostname = Auth::user()->name;

      // send email to Admin
      $message = "<p>Hi,</p>" .
      "<p>This is an automatic email to let you know that <strong>". $hostname . " </strong>has created a party on the <strong>" . APPNAME . "</strong>.</p>" .
      "<p><strong>Group Name:</strong> ". $group_name ." <p>" .
      "<p><strong>Party Name:</strong> ". $venue ." </p>" .
      "<p><strong>Party Location:</strong> " . $location ." </p>" .
      "<p><strong>Party Date:</strong> ". $event_date ." </p>" .
      "<p><strong>Party Start Time:</strong> ". $start ." </p>" .
      "<p><strong>Party End Time:</strong> ". $end ." </p>" ;

      $subject = env('APP_NAME') . ": Party created by the host : " . $hostname . " ";
      $headers = "From: " . env('APP_EMAIL') . "\r\n";
      $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
      $email= env('NOTIFICATION_EMAIL');
      $sender = mail($email, $subject, $message, $headers);
  }

  /** sync all parties to wordpress - CREATES PARTIES! **/
  public function sync(){
      /* $parties = $this->Party->findAll();
      $Groups = new Group;
      foreach($parties as $i => $party) {
          $Host = $Groups->findHost($party->group_id);
          $custom_fields = array(
                  array('key' => 'party_host',            'value' => $Host->hostname),
                  array('key' => 'party_hostavatarurl',   'value' => UPLOADS_URL . 'mid_' .$Host->path),
                  array('key' => 'party_grouphash',       'value' => $party->group_id),
                  array('key' => 'party_location',        'value' => $party->location),
                  array('key' => 'party_time',            'value' => $party->start . ' - ' . $party->end),
                  array('key' => 'party_date',            'value' => $party->event_date),
                  array('key' => 'party_timestamp',       'value' => $party->event_timestamp),
                  array('key' => 'party_stats',           'value' => $party->id),
                  array('key' => 'party_lat',             'value' => $party->latitude),
                  array('key' => 'party_lon',             'value' => $party->longitude)
          );
          echo "Connecting ... ";
          $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
          $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));


          $content = array(
                      'post_type' => 'party',
                      'custom_fields' => $custom_fields
                      );

          $wpid = $wpClient->newPost($party->location, $party->free_text, $content);
          echo "<strong>Posted to WP</strong> ... ";
          $this->Party->update(array('wordpress_post_id' => $wpid), $party->id);
          echo "Updated Fixometer recordset with WPID: " . $wpid . "<br />";
      }
      */
  }

  public function edit($id, Request $request) {
      $user = Auth::user();

      if (!FixometerHelper::userHasEditPartyPermission($id, $user->id))
          return redirect('/user/forbidden');

      $Groups = new Group;
      $File = new FixometerFile;
      $Party = new Party;
      $Device = new Device;

      $co2Total = $Device->getWeights();
      $device_count_status = $Device->statusCount();

      $groups_user_is_host_of = UserGroups::where('user', Auth::user()->id)
                              ->where('role', 3)
                              ->pluck('group')
                              ->toArray();

      $images = $File->findImages(env('TBL_EVENTS'), $id);

      if (!isset($images)) {
        $images = null;
      }

      if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
          $id = $_POST['id'];
          $data = $_POST;
          unset($data['files']);
          unset($data['file']);
          unset($data['users']);
          unset($data['id']);

          // Add SuperHero Restarter!
          // $_POST['users'][] = 29;
          // if(empty($data['volunteers'])) {
          //     $data['volunteers'] = count($_POST['users']);
          // }

          // saving this for WP
          $wp_date = $data['event_date'];

          // formatting dates for the DB
          $data['event_date'] = FixometerHelper::dbDateNoTime($data['event_date']);
          $timestamp = strtotime($data['event_date']);

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
            'event_date'  => $data['event_date'],
            'start'       => $data['start'],
            'end'         => $data['end'],
            'free_text'   => $data['free_text'],
            // 'pax'         => $data['pax'],
            // 'volunteers'  => $data['volunteers'],
            'group'       => $data['group'],
            'venue'       => $data['venue'],
            'location'    => $data['location'],
            'latitude'    => $latitude,
            'longitude'   => $longitude,
          );

          $u = $Party->where('idevents', $id)->update($update);

          if(!$u) {
              $response['danger'] = 'Something went wrong. Please check the data and try again.';
          }
          else {
              $response['success'] = 'Event details updated <a href="/party/view/'.$id.'" class="btn btn-success">View event</a>';

              $theParty = $Party->findThis($id)[0];

              if( ( env('APP_ENV') == 'development' || env('APP_ENV') == 'local' ) && isset($data['moderate']) && $data['moderate'] == 'approve' ) { //For testing purposes

                $Party->where('idevents', $id)->update(['wordpress_post_id' => 99999]);

              } elseif( ( env('APP_ENV') != 'development' && env('APP_ENV') != 'local' ) && isset($data['moderate']) && $data['moderate'] == 'approve' ) {

                  // if(env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {
                      /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                  //$Host = $Groups->findHost($group);

                      $custom_fields = array(
                                      // array('key' => 'party_host',            'value' => $Host->hostname),
                                      // array('key' => 'party_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' .$Host->path),
                                   array('key' => 'party_grouphash',       'value' => $data['group']),
                                   array('key' => 'party_venue',           'value' => $data['venue']),
                                   array('key' => 'party_location',        'value' => $data['location']),
                                   array('key' => 'party_time',            'value' => $data['start'] . ' - ' . $data['end']),
                                   array('key' => 'party_date',            'value' => $wp_date),
                                   array('key' => 'party_timestamp',       'value' => $timestamp),
                                   array('key' => 'party_timestamp_end',   'value' => $theParty->event_end_timestamp),
                                   array('key' => 'party_stats',           'value' => $id),
                                   array('key' => 'party_lat',             'value' => $latitude),
                                   array('key' => 'party_lon',             'value' => $longitude)
                        );


                      /** Start WP XML-RPC **/
                      $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                      $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));


                      $content = array(
                                      'post_type' => 'party',
                                      'custom_fields' => $custom_fields
                                      );
                      $party_name = !empty($data['venue']) ? $data['venue'] : $data['location'];
                      $wpid = $wpClient->newPost($party_name, $data['free_text'], $content);

                      $theParty = Party::find($id);
                      $theParty->wordpress_post_id = $wpid;
                      $theParty->save();
                  // }


              } elseif( ( env('APP_ENV') != 'development' && env('APP_ENV') != 'local' ) && !empty($theParty->wordpress_post_id)){
                  $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                  $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                  /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                  // $theParty = $Party->findThis($id);
                  //$Host = $Groups->findHost($data['group']);
                  $custom_fields = array(
                                  //array('key' => 'party_host',            'value' => $Host->hostname),
                                  //array('key' => 'party_hostavatarurl',   'value' => env('UPLOADS_URL') . 'mid_' . $Host->path),
                                  array('key' => 'party_grouphash',       'value' => $data['group']),
                                  array('key' => 'party_venue',           'value' => $data['venue']),
                                  array('key' => 'party_location',        'value' => $data['location']),
                                  array('key' => 'party_time',            'value' => $data['start'] . ' - ' . $data['end']),
                                  array('key' => 'party_date',            'value' => $wp_date),
                                  array('key' => 'party_timestamp',       'value' => $theParty->event_timestamp),
                                  array('key' => 'party_timestamp_end',   'value' => $theParty->event_end_timestamp),
                                  array('key' => 'party_stats',           'value' => $id),
                                  array('key' => 'party_lat',             'value' => $latitude),
                                  array('key' => 'party_lon',             'value' => $longitude)
                                  );


                  $content = array(
                                  'post_type' => 'party',
                                  'post_title' => !empty($data['venue']) ? $data['venue'] : $data['location'],
                                  'post_content' => $data['free_text'],
                                  'custom_fields' => $custom_fields
                                  );


                // we need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                $thePost = $wpClient->getPost($theParty->wordpress_post_id);

                foreach( $thePost['custom_fields'] as $i => $field ){
                    foreach( $custom_fields as $k => $set_field){
                        if($field['key'] == $set_field['key']){
                            $custom_fields[$k]['id'] = $field['id'];
                        }
                    }
                }

                $content['custom_fields'] = $custom_fields;
                $wpClient->editPost($theParty->wordpress_post_id, $content);
              }

              if(isset($_POST['users']) && !empty($_POST['users'])){
                  $users = $_POST['users'];
                  $Party->createUserList($id, $users);
              }


              /** let's create the image attachment! **/
              // if(isset($_FILES) && !empty($_FILES)){
              //     if(is_array($_FILES['file']['name'])) {
              //         $files = FixometerHelper::rearrange($_FILES['file']);
              //         foreach($files as $upload){
              //             $File->upload($upload, 'image', $id, env('TBL_EVENTS'));
              //         }
              //     }
              //     else { }
              // }
          }
          if(FixometerHelper::hasRole($user, 'Host')){
              header('Location: /host?action=pe&code=200');
          }
          // $this->set('response', $response);

          if (!isset($images)) {
            $images = null;
          }

          if (!isset($remotePost)) {
            $remotePost = null;
          }

          $party = $Party->findThis($id)[0];

          return view('events.edit', [ //party.edit
            'response' => $response,
            'gmaps' => true,
            'images' => $images,
            'title' => 'Edit Party',
            'group_list' => $Groups->findAll(),
            'formdata' => $party,
            'remotePost' => $remotePost,
            'grouplist' => $Groups->findList(),
            'user' => Auth::user(),
            'co2Total' => $co2Total[0]->total_footprints,
            'wasteTotal' => $co2Total[0]->total_weights,
            'device_count_status' => $device_count_status,
            'user_groups' => $groups_user_is_host_of,
            'images' => $images,
          ]);
      }

      $images = $File->findImages(env('TBL_EVENTS'), $id);//NB: File facade can't find findImages may need to add

      if (!isset($images)) {
        $images = null;
      }

      $party = $Party->findThis($id)[0];
      // $this->set('images', $images);
      // $this->set('title', 'Edit Party');
      // $this->set('group_list', $Groups->findAll());
      // $this->set('formdata', $Party);

      // $remotePost = $wpClient->getPost($Party->wordpress_post_id);//NB: Add back in when wordpress stuff is fixed
      $remotePost = null;

      // $this->set('remotePost', $remotePost);
      //
      // $this->set('grouplist', $Groups->findList());

      return view('events.edit', [ //party.edit
        'gmaps' => true,
        'images' => $images,
        'title' => 'Edit Party',
        'group_list' => $Groups->findAll(),
        'formdata' => $party,
        'remotePost' => $remotePost,
        'grouplist' => $Groups->findList(),
        'user' => Auth::user(),
        'co2Total' => $co2Total[0]->total_footprints,
        'wasteTotal' => $co2Total[0]->total_weights,
        'device_count_status' => $device_count_status,
        'user_groups' => $groups_user_is_host_of,
      ]);
  }

  public function view($id) {

      $File = new FixometerFile;
      $Party = new Party;
      $event = Party::find($id);

      //Event details
      $images = $File->findImages(env('TBL_EVENTS'), $id);
      $party = $Party->findThis($id, true)[0];
      $hosts = EventsUsers::where('event', $id)->where('role', 3)->where('status', 1)->get();

      if( Auth::check() ){
        $is_attending = EventsUsers::where('event', $id)->where('user', Auth::user()->id)->first();
      } else {
        $is_attending = null;
      }

      //Info for attendance tabs
      $attendees = EventsUsers::where('event', $id)->where('status', 1);
      $attended = clone $attendees->get();

      if( count($attended) > 5 && $event->hasFinished() && !Auth::guest() && !FixometerHelper::hasRole(Auth::user(), 'Restarter') ){
        $attended_summary = clone $attendees->take(5)->get();
      } else {
        $attended_summary = clone $attendees->take(6)->get();
      }

      $invites = EventsUsers::where('event', $id)->where('status', '!=', 1);
      $invited = clone $invites->get();

      if( count($invited) > 5 && !$event->hasFinished() && !Auth::guest() && !FixometerHelper::hasRole(Auth::user(), 'Restarter') ){
        $invited_summary = clone $invites->take(5)->get();
      } else {
        $invited_summary = clone $invites->take(6)->get();
      }

      //Useful for add/edit device
      $brands = Brands::all();
      //$categories = Category::all();
      $clusters = Cluster::all();

      $device_images = [];

      //Get Device Images
      foreach ($event->devices as $device) {
        $device_images[$device->iddevices] = $File->findImages(env('TBL_DEVICES'), $device->iddevices);
      }

      //Retrieve group volunteers
      if( $event->hasFinished() && !Auth::guest() ){
          $group_volunteers = $this->getGroupEmails($id, true);
      } else {
          $group_volunteers = null;
      }

      return view('events.view', [
        'gmaps' => true,
        'images' => $images,
        'event' => $event,
        'stats' => $event->getEventStats($this->EmissionRatio),
        'formdata' => $party,
        'attended_summary' => $attended_summary,
        'attended' => $attended,
        'invited_summary'  => $invited_summary,
        'invited'  => $invited,
        'hosts' => $hosts,
        'is_attending' => $is_attending,
        'brands' => $brands,
        'clusters' => $clusters,
        'device_images' => $device_images,
        'group_volunteers' => $group_volunteers,
      ]);

  }

  public function getJoinEvent($event_id) {

    $user_id = Auth::id();
    $not_in_event = EventsUsers::where('event', $event_id)
                                  ->where('user', $user_id)
                                    ->where('status', '!=', 1)
                                      ->first();


    if ( empty($not_in_event) ) {

      try {

        $user_event = EventsUsers::updateOrCreate([
          'user' => $user_id,
          'event' => $event_id,
        ], [
          'status' => 1,
          'role' => 4,
        ]);

        Party::find($event_id)->increment('volunteers');

        $response['success'] = 'Thank you for your RSVP, we look forward to seeing you at the event';

        return redirect()->back()->with('response', $response);

      } catch (\Exception $e) {
        $response['danger'] = 'Failed to join this event';
        return redirect()->back()->with('response', $response);
      }
    } else {
      $response['warning'] = 'You are already part of this event';
      return redirect()->back()->with('response', $response);
    }

  }

  public function manage($id){

      $user = User::find(Auth::id());

      if( !FixometerHelper::hasRole($user, 'Host') && !FixometerHelper::hasRole($user, 'Administrator')){

          return redirect('/user/forbidden');

      } else {

          // $this->set('js',
          //             array('foot' => array(
          //                             '/components/jquery.floatThead/dist/jquery.floatThead.min.js'
          //             )));

          $Device     = new Device;
          $Category   = new Category;
          $User       = new User;
          $Group      = new Group;
          $Party      = new Party;

          // $this->set('grouplist', $Group->findList());

          if(isset($_POST) && !empty($_POST) && is_numeric($_POST['idparty']) && ($_POST['idparty'] > 0) ) {
              $response = null;

              $partydata = $_POST['party'];
              $idparty = $_POST['idparty'];
              $Party->update($partydata, $idparty);

              if(isset($_POST['device'])){
                  $devices = $_POST['device'];

                  // Rearrange files to more friendly Array
                  if(isset($_FILES) && !empty($_FILES)){
                    $files = reflow($_FILES['device']);
                    $File = new FixometerFile;
                  }
                  //dbga($files);
                  foreach ($devices as $i => $device){

                      //dbga($device);
                      $error = false;
                      $device['event'] = $id;
                      $method = null;

                      if(isset($device['id']) && is_numeric($device['id'])){
                          $method = 'update';
                          $iddevice = $device['id'];
                          unset($device['id']);
                      }

                      if(!isset($device['category']) || empty($device['category'])){
                          $response['danger'] = 'Category needed! (device # ' . $i . ')';
                          $error = true;
                      }

                      if(!isset($device['repaired_by']) || empty($device['repaired_by'])){
                          $device['repaired_by'] = 29;
                      }

                      if($method == 'update'){
                          //echo "updating---";
                          $Device->update($device, $iddevice);
                          if (FixometerHelper::featureIsEnabled(env('FEATURE__DEVICE_PHOTOS'))) {
                            if($files[$i]['error'] == 0){
                              $File->simpleUpload($files[$i], 'device', $iddevice, 'Device S/N Image');
                            }
                          }
                      }

                      else {
                          //echo "creating---";
                          $device['category_creation'] = $device['category'];
                          $iddevice = $Device->create($device);
                          if (FixometerHelper::featureIsEnabled(env('FEATURE__DEVICE_PHOTOS'))) {
                            if($files[$i]['error'] == 0){
                              $File->simpleUpload($files[$i], 'device', $iddevice, 'Device S/N Image');
                            }
                          }
                      }

                      $response['success'] = 'Party info updated!';
                  }
              }


              if(env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {
                  /** WP Sync **/
                  $party = $Party->findThis($idparty, true);

                  $Groups = new Group;
                  $partygroup = $party->group;
                  $Host = $Groups->findHost($party->group);

                  $custom_fields = array(
                                      //array('key' => 'party_host',            'value' => $Host->hostname),
                                      //array('key' => 'party_hostavatarurl',   'value' => UPLOADS_URL . 'mid_' . $Host->path),
                                      array('key' => 'party_grouphash',       'value' => $party->group),
                                      array('key' => 'party_location',        'value' => $party->location),
                                      array('key' => 'party_time',            'value' => substr($party->start, 0, -3) . ' - ' . substr($party->end, 0, -3)),
                                      array('key' => 'party_date',            'value' => date('d/m/Y', $party->event_date)),
                                      array('key' => 'party_timestamp',       'value' => $party->event_timestamp),
                                      array('key' => 'party_timestamp_end',   'value' => $party->event_end_timestamp),
                                      array('key' => 'party_stats',           'value' => $idparty),
                                      array('key' => 'party_lat',             'value' => $party->latitude),
                                      array('key' => 'party_lon',             'value' => $party->longitude)

                                  );


                  /** Start WP XML-RPC **/
                  $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                  $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));



                  $text = (empty($party->free_text) ? '...' : $party->free_text);
                  $content = array(
                                  'post_type' => 'party',
                                  'post_title' => $party->location,
                                  'post_content' => $text,
                                  'custom_fields' => $custom_fields
                                  );


                  // Check for WP existence in DB
                  // $theParty = $this->Party->findOne($idparty);
                  if(!empty($party->wordpress_post_id)){
                      // echo "WP id present (" . $party->wordpress_post_id . ")! Editing...<br />";
                      // we need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                      $thePost = $wpClient->getPost($party->wordpress_post_id);



                      foreach( $thePost['custom_fields'] as $i => $field ){
                          foreach( $custom_fields as $k => $set_field){
                              if($field['key'] == $set_field['key']){
                                  $custom_fields[$k]['id'] = $field['id'];
                              }
                          }
                      }

                      $content['custom_fields'] = $custom_fields;
                      $wpClient->editPost($party->wordpress_post_id, $content);
                  }
                  else {
                      $returnId = $wpClient->newPost($Host->groupname, $text, $content);
                      $this->Party->update(array('wordpress_post_id' => $returnId), $idparty);
                  }

                  unset($party);
              }
              /** EOF WP Sync **/
              /*
              if($error == false){
                  // If is Admin, redir to host + group id
                  if(hasRole($this->user, 'Administrator')){
                      header('Location: /group/view/' . $partygroup);
                  }
                  else {
                      header('Location: /host');
                  }
              }
              else {
                  //echo "No.";

              }
              */
            // $this->set('response', $response);
          }


          $party      = $Party->findThis($id, true);
          $categories = $Category->listed();
          $restarters = $User->find(array('idroles' => 4));

          $party = $party[0];

          $party->co2 = 0;
          $party->ewaste = 0;
          $party->fixed_devices = 0;
          $party->repairable_devices = 0;
          $party->dead_devices = 0;

          if(!empty($party->devices)){
              foreach($party->devices as $device){

                  if ($device->isFixed()) {
                      $party->co2     += $device->co2Diverted($this->EmissionRatio, $Device->displacement);
                      $party->ewaste  += $device->ewasteDiverted();
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
          }

          $party->co2 = number_format(round($party->co2), 0, '.', ',');

          // $this->set('party', $party);
          // $this->set('devices', $party->devices);
          // $this->set('categories', $categories);
          // $this->set('restarters', $restarters);

          if (!isset($response)) {
            $response = null;
          }

          return view('party.manage', [
            'grouplist' => $Group->findList(),
            'response' => $response,
            'party' => $party,
            'devices' => $party->devices,
            'categories' => $categories,
            'restarters' => $restarters,
            'user' => $user,
          ]);
      }
  }


  public function delete($id){
      if(FixometerHelper::hasRole($this->user, 'Administrator') || (hasRole($this->user, 'Host') && in_array($id, $this->hostParties))){
          // fetch the postID in WP to delete it later
          $party = $this->Party->findOne($id);
          $wpId = $party->wordpress_post_id;

          $usersDelete = $this->Party->deleteUserList($id);
          $r = $this->Party->delete($id);

          if(!$r){
              $response = 'action=de&code=403';
          }
          else {
              if( !is_null($wpId) && is_numeric($wpId) ) {
                  // delete from WordPress
                  /** Start WP XML-RPC **/
                  $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                  $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                  $deletion = $wpClient->deletePost($wpId);
                  if(!$wpId){
                      $response = 'action=de&code=500';
                  }
                  else {
                      $response = 'action=de&code=200';
                  }
              }
              else {
                  $response = 'action=de&code=200';
              }

          }

          if(FixometerHelper::hasRole($this->user, 'Host')){
              header('Location: /host?' . $response);
          }
          else {
              header('Location: /party?' . $response);
          }

      }

      else {
          header('Location: /user/forbidden');
      }
  }


  public static function stats($id, $class = null){
      $Device = new Device;

      $footprintRatioCalculator = new FootprintRatioCalculator();
      $emissionRatio = $footprintRatioCalculator->calculateRatio();


      // $this->set('framed', true);
      $event = Party::where('idevents', $id)->first();

      $eventStats = $event->getEventStats($emissionRatio);

      $eventStats['co2'] = number_format(round($eventStats['co2']), 0, '.' , ',');
      // $this->set('party', $party);
      if(!is_null($class)) {
        return view('party.stats', [
          'framed' => true,
          'party' => $eventStats,
          'class' => 'wide',
        ]);
      } else {
        return view('party.stats', [
          'framed' => true,
          'party' => $eventStats,
        ]);
      }

  }

  // public function deleteImage($party_id, $id, $path){
  //
  //     $user = Auth::user();
  //
  //     $is_host_of_party = FixometerHelper::userHasEditPartyPermission($party_id, $user->id);
  //     if( FixometerHelper::hasRole($user, 'Administrator') || $is_host_of_party ){
  //
  //         $Image = new FixometerFile;
  //         $Image->deleteImage($id, $path);
  //
  //         return redirect()->back()->with('message', 'Thank you, the image has been deleted');
  //
  //     }
  //
  //     return redirect()->back()->with('message', 'Sorry, but the image can\'t be deleted');
  //
  // }

  public function getGroupEmails($event_id, $object = false){

    $group_user_ids = UserGroups::where('group', Party::find($event_id)->group)
              ->where('user', '!=', Auth::user()->id)
                ->pluck('user')
                  ->toArray();

    $event_user_ids = EventsUsers::where('event', $event_id)
              ->where('user', '!=', Auth::user()->id)
                ->pluck('user')
                  ->toArray();

    $unique_user_ids = array_diff($group_user_ids, $event_user_ids);

    if( $object == true ){
      $group_users = User::whereIn('id', $unique_user_ids)->get();
      return $group_users;
    } else {
      $group_users = User::whereIn('id', $unique_user_ids)->pluck('email')->toArray();
      return json_encode($group_users);
    }

  }

  public function updateQuantity(Request $request) {

    $event_id = $request->input('event_id');
    $quantity = $request->input('quantity');

    $return = [
      'success' => false
    ];

    if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($event_id, Auth::user()->id) ) || FixometerHelper::hasRole(Auth::user(), 'Administrator')) {

      Party::find($event_id)->update([
        'pax' => $quantity,
      ]);

      $return = [
        'success' => true
      ];

    }

    return response()->json($return);

  }

  public function removeVolunteer(Request $request) {

    $user_id = $request->input('user_id');
    $event_id = $request->input('event_id');

    $return = [
      'success' => false
    ];

    //Has current logged in user got permission to remove volunteer
    if ( ( FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($event_id, Auth::user()->id) ) || FixometerHelper::hasRole(Auth::user(), 'Administrator') ) {

      //Let's get the user before we delete them
      $volunteer = EventsUsers::where('user', $user_id)->where('event', $event_id)->first();

      //Let's delete the user
      $delete_user = EventsUsers::where('user', $user_id)->where('event', $event_id)->delete();
      if( $delete_user == 1 ){

        //If the user accepted the invitation, we decrement
        if( $volunteer->status == 1 )
          Party::find($event_id)->decrement('volunteers');

        //Return JSON
        $return = [
          'success' => true
        ];

      }

    }

    return response()->json($return);

  }

  public function postSendInvite(Request $request) {

    $from_id = Auth::id();
    $group_name = $request->input('group_name');
    $event_id = $request->input('event_id');
    $invite_group = $request->input('invite_group');

    $emails = explode(',', str_replace(' ', '', $request->input('manual_invite_box')));
    $message = $request->input('message_to_restarters');

    if ( !empty($emails) ) {

      $users = User::whereIn('email', $emails)->get();

      $non_users = array_diff($emails, User::whereIn('email', $emails)->pluck('email')->toArray());
      $from = User::find($from_id);

      foreach ($users as $user) {

        $user_event = EventsUsers::where('user', $user->id)->where('event', $event_id)->first();

        if (is_null($user_event) || $user_event->status != "1") {

            $hash = substr( bin2hex(openssl_random_pseudo_bytes(32)), 0, 24 );
            $url = url('/party/accept-invite/'.$event_id.'/'.$hash);

            if (!is_null($user_event)) {

              $user_event->update([
                'status' => $hash,
              ]);

            } else {

              EventsUsers::create([
                'user' => $user->id,
                'event' => $event_id,
                'status' => $hash,
                'role' => 4,
              ]);

            }

            $event = Party::find($event_id);

            $arr = array(
              'name' => $from->name,
              'group' => $group_name,
              'url' => $url,
              'message' => $message,
              'event' => $event,
            );
            Notification::send($user, new JoinEvent($arr, $user));

          } else {

            $not_sent[] = $user->email;

          }

      }

      if ( !empty($non_users) ) {

        foreach ($non_users as $non_user) {

          $hash = substr( bin2hex(openssl_random_pseudo_bytes(32)), 0, 24 );
          $url = url('/user/register/'.$hash);

          $invite = Invite::create(array(
            'record_id' => $event_id,
            'email' => $non_user,
            'hash' => $hash,
            'type' => 'event',
          ));

          $event = Party::find($event_id);

          $arr = array(
            'name' => $from->name,
            'group' => $group_name,
            'url' => $url,
            'message' => $message,
            'event' => $event,
          );

          Notification::send($invite, new JoinEvent($arr));

        }

      }

      if (!isset($not_sent)) {
        return redirect()->back()->with('success', 'Invites Sent!');
      } else {
        return redirect()->back()->with('warning', 'Invites Sent - apart from these ('.implode(',', $not_sent).') who were already part of the event');
      }
    } else {
      return redirect()->back()->with('warning', 'You have not entered any emails!');
    }

  }

  public function confirmInvite($event_id, $hash) {

    $user_event = EventsUsers::where('status', $hash)->where('event', $event_id)->first();

    if ( !empty($user_event) ) {

      EventsUsers::where('status', $hash)->where('event', $event_id)->update([
        'status' => 1
      ]);

      $user = User::find($user_event->user);
      try {
        $host = User::find(EventsUsers::where('event', $event_id)->where('role', 3)->first()->user);
      } catch (\Exception $e) {
        $host = null;
      }

      Party::find($event_id)->increment('volunteers');

      if ( !is_null($host) ) {

        //Send Notification to Host
        $arr = [
          'user_name' => $user->name,
          'event_venue' => Party::find($event_id)->venue,
          'event_url' => url('/party/view/'.$event_id),
          'preferences' => url('/profile/edit/'.$host->id),
        ];

        Notification::send($host, new RSVPEvent($arr, $host));

      }

      return redirect('/party/view/'.$user_event->event);

    } else {
      return redirect('/party/view/'.$event_id)->with('warning', 'Something went wrong - this invite is invalid or has expired');
    }

  }

  public function cancelInvite($event_id) {

      $user_event = EventsUsers::where('user', Auth::user()->id)->where('event', $event_id)->delete();
      return redirect('/party/view/'.$event_id)->with('success', 'You are no longer attending this event');

  }

  public function addVolunteer(Request $request) {

    // Get event ID
    $event_id = $request->input('event');
    $volunteer_email_address = $request->input('volunteer_email_address');

    // Retrieve name if one exists, if no name exists and user is null as well. This volunteer is anonymous
    if( $request->has('full_name') ){
      $full_name = $request->input('full_name');
    } else {
      $full_name = null;
    }

    // User is null, this volunteer is either anonymous or no user exists
    if( $request->has('user') && $request->input('user') !== 'not-registered' ){
      $user = $request->input('user');
    } else {
      $user = null;
    }

    //Let's add the volunteer
    EventsUsers::create([
      'event' => $event_id,
      'user' => $user,
      'status' => 1,
      'role' => 4,
      'full_name' => $full_name,
    ]);

    Party::find($event_id)->increment('volunteers');

    // Send email
    if( !is_null($volunteer_email_address) ){

      $event = Party::find($event_id);
      $from = User::find(Auth::user()->id);

      $hash = substr( bin2hex(openssl_random_pseudo_bytes(32)), 0, 24 );
      $url = url('/user/register/'.$hash);

      $invite = Invite::create([
        'record_id' => $event->theGroup->idgroups,
        'email' => $volunteer_email_address,
        'hash' => $hash,
        'type' => 'group',
      ]);

      Notification::send($invite, new JoinGroup([
        'name' => $from->name,
        'group' => $event->theGroup->name,
        'url' => $url,
        'message' => null,
      ]));

    }

    return redirect('/party/view/'.$event_id)->with('success', 'Volunteer has successfully been added to event');

  }

  public function imageUpload(Request $request, $id) {

    try {
      if(isset($_FILES) && !empty($_FILES)){
          $file = new FixometerFile;
          $file->upload('file', 'image', $id, env('TBL_EVENTS'), true, false, true);
      }

      return "success - image uploaded";
    } catch (\Exception $e) {
      return "fail - image could not be uploaded";
    }
  }

  public function deleteImage($event_id, $id, $path){

      $user = Auth::user();

      $in_event = EventsUsers::where('event', $event_id)->where('user', Auth::user()->id)->first();
      if(FixometerHelper::hasRole($user, 'Administrator') || is_object($in_event) ){

          $Image = new FixometerFile;
          $Image->deleteImage($id, $path);

          return redirect()->back()->with('success', 'Thank you, the image has been deleted');

      }

      return redirect()->back()->with('warning', 'Sorry, but the image can\'t be deleted');

  }

  public function emailHosts() {

    if(env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {

      //Get all events and hosts
      $event_users = EventsUsers::where('role', 3);
      $event_ids = $event_users->pluck('event')->toArray();
      $all_events = Party::whereIn('idevents', $event_ids)
                            ->where('event_date', '=', date('Y-m-d', strtotime("-1 day")))
                              ->get();

      foreach($all_events as $event) {
        $host_ids = $event_users->where('event', $event->idevents)->pluck('user')->toArray();

        if (!empty($host_ids)) {
          $hosts = User::whereIn('id', $host_ids)->get();

          //Send Emails to Admins notifying event creation
          $arr = [
            'event_venue' => $event->venue,
            'event_url' => url('/party/view/'.$event->idevents),
            'preferences' => url('/profile/edit'),
          ];

          Notification::send($hosts, new EventDevices($arr));
        }
      }

    }
  }

  /*
   *
   * This sends an email to all user except the host logged in an email to ask for contributions
   *
   */
  public function getContributions($event_id){

      // Let's check that current logged in user is a host of the event
      $in_event = EventsUsers::where('event', $event_id)
                                ->where('user', Auth::user()->id)
                                  ->where('role', 3)
                                    ->first();

      // We'll allow admins to send out email, just in case...
      if( FixometerHelper::hasRole(Auth::user(), 'Administrator') || is_object($in_event) ){

          if(env('APP_ENV') == 'development' || env('APP_ENV') == 'local') { //Testing purposes

            $all_restarters = User::whereIn('id', [91,92,93])->get();

          } else {

            $all_restarters = User::join('events_users', 'events_users.user', '=', 'users.id')
                                  ->where('users.invites', 1)
                                    ->where('events_users.role', 4)
                                      ->where('events_users.event', $event_id)
                                        ->get();

          }

          $event = Party::find($event_id);

          $arr = [
            'event_name' => $event->getEventName(),
            'event_url' => url('/party/view/'.$event_id),
            'preferences' => url('/profile/edit'),
          ];
          Notification::send($all_restarters, new EventRepairs($arr));

          return redirect()->back()->with('success', 'Thank you, all attendees have been informed');

      } else {

          return redirect()->back()->with('warning', 'Sorry, you are not the host of this event');

      }

  }

}
