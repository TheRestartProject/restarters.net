<?php

namespace App\Http\Controllers;

use App\Audits;
use App\Brands;
use App\Category;
use App\Cluster;
use App\Device;
use App\Events\ApproveEvent;
use App\Events\EditEvent;
use App\Events\EventImagesUploaded;
use App\EventsUsers;
use App\Group;
use App\Helpers\FootprintRatioCalculator;
use App\Host;
use App\Invite;
use App\Notifications\AdminModerationEvent;
use App\Notifications\EventDevices;
use App\Notifications\EventRepairs;
use App\Notifications\JoinEvent;
use App\Notifications\JoinGroup;
use App\Notifications\NotifyHostRSVPInvitesMade;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Notifications\RSVPEvent;
use App\Party;
use App\Session;
use App\User;
use App\UserGroups;

use Auth;
use DateTime;
use DB;
use FixometerFile;
use FixometerHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Notification;
use Spatie\CalendarLinks\Link;

class PartyController extends Controller
{

  // protected $hostParties = array();
    // protected $permissionsChecker;

    public $TotalWeight;
    public $TotalEmission;
    public $EmissionRatio;

    public function __construct()
    {
        //($model, $controller, $action)

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
        if (FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
            $moderate_events = Party::RequiresModeration()->get();
        } else {
            $moderate_events = null;
        }

        // Use this view for showing group only upcoming and past events
        if ( ! is_null($group_id)) {
          $upcoming_events = Party::upcomingEvents()
          ->where('events.group', $group_id)
          ->get();

          $past_events = Party::pastEvents()
            ->where('events.group', $group_id)
            ->paginate(10);

          $group = Group::find($group_id);
          $upcoming_events_in_area = null;

        } else {
          $upcoming_events = Party::upcomingEvents()
            ->where('users_groups.user', Auth::user()->id)
            ->take(3)
            ->get();

        $past_events = Party::UsersPastEvents([auth()->id()])->paginate(10);

        if ( ! is_null(Auth::user()->latitude) && ! is_null(Auth::user()->longitude)) {
            $upcoming_events_in_area = Party::upcomingEventsInUserArea(Auth::user())->take(3)->get();
        } else {
            $upcoming_events_in_area = null;
        }

          $group = null;
        }



        //Looks to see whether user has a group already, if they do, they can create events
        $user_groups = UserGroups::where('user', Auth::user()->id)->count();

        return view('events.index', [
            'moderate_events' => $moderate_events,
            'upcoming_events' => $upcoming_events,
            'past_events' => $past_events,
            'upcoming_events_in_area' => $upcoming_events_in_area,
            'user_groups' => $user_groups,
            'EmissionRatio' => $this->EmissionRatio,
            'group' => $group,
        ]);
    }

    public function allPast()
    {
        $past_events = Party::pastEvents();
        $past_events_count = $past_events->count();
        $past_events = $past_events->paginate(env('PAGINATE'));

        return view('events.all-past', [
          'past_events' => $past_events,
          'past_events_count' => $past_events_count,
          'EmissionRatio' => $this->EmissionRatio,
        ]);
    }

    public function allUpcoming()
    {
        $allUpcomingEventsQuery = Party::allUpcomingEvents();
        $allUpcomingEventsCount = $allUpcomingEventsQuery->count();
        $allUpcomingEvents = $allUpcomingEventsQuery->paginate(env('PAGINATE'));

        return view('events.all', [
            'upcoming_events_count' => $allUpcomingEventsCount,
            'upcoming_events' => $allUpcomingEvents,
        ]);
    }

    public function create(Request $request, $group_id = null)
    {

      // Let's determine whether currently logged in user is associated with any groups
        $user_groups = UserGroups::join('groups', 'groups.idgroups', '=', 'users_groups.group')
                                ->where('user', Auth::user()->id)
                                  ->where('role', 3)
                                    ->select('groups.idgroups AS id', 'groups.name')
                                      ->get();

        // Then let's redirect users away if they are a restarter or a host with no groups
        if (FixometerHelper::hasRole(Auth::user(), 'Restarter') || (count($user_groups) == 0 && FixometerHelper::hasRole(Auth::user(), 'Host'))) {
            return redirect('/user/forbidden');
        }

        $Groups = new Group;

        if ($request->isMethod('post')) {
            $error = array();

            if ($request->has('location')) {
              $json = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($request->input('location'))."&key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE");
              $json = json_decode($json);

              if ( empty($json->results) ) {
                $response['danger'] = 'Party could not be created. Address not found.';
                Log::error('An error occurred during geocoding: ' . $ex->getMessage());
                return view('events.create', [ //party.create
                  'response' => $response,
                  'title' => 'New Party',
                  'grouplist' => $Groups->findList(),
                  'gmaps' => true,
                  'group_list' => $Groups->findAll(),
                  'user' => Auth::user(),
                  'user_groups' => $user_groups,
                  'selected_group_id' => $group_id,
                ]);
              }

              if (is_object($json) && !empty($json->{'results'})) {
                  $latitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lat;
                  $longitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lng;
              }
            } else {
                $latitude = null;
                $longitude = null;
            }
            $data['latitude'] = $latitude;
            $data['longitude'] = $longitude;

            // We got data! Elaborate.
            $event_date = $request->input('event_date');
            $start = $request->input('start');
            $end = $request->input('end');
            $pax = 0;
            $free_text = $request->input('free_text');
            $venue = $request->input('venue');
            $location = $request->input('location');
            $group = $request->input('group');
            $user_id = Auth::user()->id;

            // saving this for wordpress
            $wp_date = $event_date;

            // formatting dates for the DB
            $event_date = date('Y-m-d', strtotime($event_date));

            if ( ! FixometerHelper::verify($event_date)) {
                $error['event_date'] = 'We must have a starting date and time.';
            }
            if ( ! FixometerHelper::verify($start)) {
                $error['name'] = 'We must have a starting date and time.';
            }

            if (empty($error)) {
                $startTime = $event_date.' '.$start;
                $endTime = $event_date.' '.$end;

                $dtStart = new DateTime($startTime);
                $dtDiff = $dtStart->diff(new DateTime($endTime));

                $hours = $dtDiff->h;

                // No errors. We can proceed and create the Party.
                $data = array(
                    'event_date' => $event_date,
                    'start' => $start,
                    'end' => $end,
                    'pax' => $pax,
                    'free_text' => $free_text,
                    'venue' => $venue,
                    'location' => $location,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'group' => $group,
                    'hours' => $hours,
                    // 'volunteers'    => $volunteers,
                    'user_id' => $user_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'shareable_code' => FixometerHelper::generateUniqueShareableCode('App\Party', 'shareable_code'),
                );

                $party = Party::create($data);
                $idParty = $party->idevents;
                if (is_numeric($idParty)) {

                      /** check and create User List **/
                    // $_POST['users'][] = 29;
                    // if(isset($_POST['users']) && !empty($_POST['users'])){
                    //   $users = $_POST['users'];
                    //   $Party->createUserList($idParty, $users);
                    // }

                    EventsUsers::create([
                        'event' => $idParty,
                        'user' => Auth::user()->id,
                        'status' => 1,
                        'role' => 3,
                    ]);

                    Party::find($idParty)->increment('volunteers');

                    // Notify relevant users
                    $notify_users = FixometerHelper::usersWhoHavePreference('admin-moderate-event');
                    Notification::send($notify_users, new AdminModerationEvent([
                        'event_venue' => Party::find($idParty)->venue,
                        'event_url' => url('/party/edit/'.$idParty),
                    ]));

                    /** let's create the image attachment! **/
                    if (isset($_FILES) && ! empty($_FILES)) {
                        if (is_array($_FILES['file']['name'])) {
                            $File = new FixometerFile;
                            $files = FixometerHelper::rearrange($_FILES['file']);
                            foreach ($files as $upload) {
                                $File->upload($upload, 'image', $idParty, env('TBL_EVENTS'));
                            }
                        }
                    }
                } else {
                    $response['danger'] = 'Party could <strong>not</strong> be created. Something went wrong with the database.';
                }
            } else {
                $response['danger'] = 'Party could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
            }

            if ( ! isset($response)) {
                $response = null;
            }
            if ( ! isset($error)) {
                $error = null;
            }
            if ( ! isset($_POST)) {
                $udata = null;
            } else {
                $udata = $_POST;
            }

            if (is_numeric($idParty)) {
                return redirect('/party/edit/'.$idParty)->with('response', $response);
            }

            return view('events.create', [ //party.create
                'title' => 'New Party',
                'grouplist' => $Groups->findList(),
                'gmaps' => true,
                'group_list' => $Groups->findAll(),
                'response' => $response,
                'error' => $error,
                'udata' => $_POST,
                'user' => Auth::user(),
                'user_groups' => $user_groups,
                'selected_group_id' => $group_id,
            ]);
        }

        return view('events.create', [ //party.create
            'title' => 'New Party',
            'grouplist' => $Groups->findList(),
            'gmaps' => true,
            'group_list' => $Groups->findAll(),
            'user' => Auth::user(),
            'user_groups' => $user_groups,
            'selected_group_id' => $group_id,
        ]);
    }

    public function sendCreationNotificationEmail($venue, $location, $event_date, $start, $end, $group_id)
    {
        $Groups = new Group;

        $group = $Groups->findOne($group_id);
        $group_name = $group->name;

        $hostname = Auth::user()->name;

        // send email to Admin
        $message = '<p>Hi,</p>'.
        '<p>This is an automatic email to let you know that <strong>'.$hostname.' </strong>has created a party on the <strong>'.APPNAME.'</strong>.</p>'.
        '<p><strong>Group Name:</strong> '.$group_name.' <p>'.
        '<p><strong>Party Name:</strong> '.$venue.' </p>'.
        '<p><strong>Party Location:</strong> '.$location.' </p>'.
        '<p><strong>Party Date:</strong> '.$event_date.' </p>'.
        '<p><strong>Party Start Time:</strong> '.$start.' </p>'.
        '<p><strong>Party End Time:</strong> '.$end.' </p>';

        $subject = env('APP_NAME').': Party created by the host : '.$hostname.' ';
        $headers = 'From: '.env('APP_EMAIL')."\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $email = env('NOTIFICATION_EMAIL');
        $sender = mail($email, $subject, $message, $headers);
    }

    /** sync all parties to wordpress - CREATES PARTIES! **/
    public function sync()
    {
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

    public function edit($id, Request $request)
    {
        $user = Auth::user();

        if ( ! FixometerHelper::userHasEditPartyPermission($id, $user->id)) {
            return redirect('/user/forbidden');
        }

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

        if ( ! isset($images)) {
            $images = null;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST)) {
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

            if ( ! empty($data['location'])) {
                $json = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($data['location']).'&key=AIzaSyDb1_XdeHbwLg-5Rr3EOHgutZfqaRp8THE');
                $json = json_decode($json);

                if ( empty($json->results) ) {
                  $response['danger'] = 'Party could not be saved. Address not found.';
                  $party = $Party->findThis($id)[0];
                  $audits = Party::findOrFail($id)->audits;

                  return view('events.edit', [ //party.edit
                      'gmaps' => true,
                      'images' => $images,
                      'title' => 'Edit Party',
                      'group_list' => $Groups->findAll(),
                      'formdata' => $party,
                      'remotePost' => null,
                      'grouplist' => $Groups->findList(),
                      'user' => Auth::user(),
                      'co2Total' => $co2Total[0]->total_footprints,
                      'wasteTotal' => $co2Total[0]->total_weights,
                      'device_count_status' => $device_count_status,
                      'user_groups' => $groups_user_is_host_of,
                      'audits' => $audits,
                      'response' => $response,
                  ]);
                }

                if (is_object($json) && ! empty($json->{'results'})) {
                    $latitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lat;
                    $longitude = $json->{'results'}[0]->{'geometry'}->{'location'}->lng;
                }
            } else {
                $latitude = null;
                $longitude = null;
            }
            $data['latitude'] = $latitude;
            $data['longitude'] = $longitude;

            $update = array(
                'event_date' => $data['event_date'],
                'start' => $data['start'],
                'end' => $data['end'],
                'free_text' => $data['free_text'],
                // 'pax'         => $data['pax'],
                // 'volunteers'  => $data['volunteers'],
                'group' => $data['group'],
                'venue' => $data['venue'],
                'location' => $data['location'],
                'latitude' => $latitude,
                'longitude' => $longitude,
            );

            // $u = $Party->where('idevents', $id)->update($update);
            $u = Party::findOrFail($id)->update($update);

            if ( ! $u) {
                $response['danger'] = 'Something went wrong. Please check the data and try again.';
            } else {
                $response['success'] = '<div class="row"><div class="col-md-8 col-lg-9 d-flex flex-column align-content-center">Event details updated</div><div class="col-md-4 col-lg-3 d-flex flex-column align-content-center"><a href="/party/view/'.$id.'" class="btn btn-success">View event</a></div></div>';

                $theParty = $Party->findThis($id)[0];

                // If event has just been approved, email Restarters attached to group, and push to Wordpress.
                if (isset($data['moderate']) && $data['moderate'] == 'approve') {
                    // Notify Restarters of relevant Group
                    $event = Party::find($id);
                    $group = Group::find($event->group);

                    // Retrieving all users from the User model whereby they allow you send emails but their role must not include group admins
                    $group_restarters = User::join('users_groups', 'users_groups.user', '=', 'users.id')
                                    ->where('users_groups.group', $event->group)
                                    ->where('users_groups.role', 4)
                                        ->select('users.*')
                                        ->get();

                    // If there are restarters against the group
                    if ( ! $group_restarters->isEmpty()) {
                        // Send user a notification and email
                        Notification::send($group_restarters, new NotifyRestartersOfNewEvent([
                            'event_venue' => $event->venue,
                            'event_url' => url('/party/view/'.$event->idevents),
                            'event_group' => $group->name,
                        ]));
                    }

                    event(new ApproveEvent($event, $data));
                } elseif ( ! empty($theParty->wordpress_post_id)) {
                    $event = Party::find($id);
                    event(new EditEvent($event, $data));
                }

                if (isset($_POST['users']) && ! empty($_POST['users'])) {
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
            if (FixometerHelper::hasRole($user, 'Host')) {
                header('Location: /host?action=pe&code=200');
            }
            // $this->set('response', $response);

            if ( ! isset($images)) {
                $images = null;
            }

            if ( ! isset($remotePost)) {
                $remotePost = null;
            }

            $party = $Party->findThis($id)[0];

            $audits = Party::findOrFail($id)->audits;

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
                'audits' => $audits,
            ]);
        }

        $images = $File->findImages(env('TBL_EVENTS'), $id);//NB: File facade can't find findImages may need to add

        if ( ! isset($images)) {
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

        $audits = Party::findOrFail($id)->audits;

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
            'audits' => $audits,
        ]);
    }

    public function view($id)
    {
        $File = new FixometerFile;
        $Party = new Party;
        $event = Party::find($id);

        // If event no longer exists
        if (empty($event)) {
            abort(404);
        }

        //Event details
        $images = $File->findImages(env('TBL_EVENTS'), $id);
        $party = $Party->findThis($id, true)[0];
        $hosts = EventsUsers::where('event', $id)->where('role', 3)->where('status', 1)->get();

        if (Auth::check()) {
            $is_attending = EventsUsers::where('event', $id)->where('user', Auth::user()->id)->first();
        } else {
            $is_attending = null;
        }

        //Info for attendance tabs
        $attendees = EventsUsers::where('event', $id)->where('status', 1);
        $attended = clone $attendees->get();

        if (count($attended) > 5 && $event->hasFinished() && ! Auth::guest() && ! FixometerHelper::hasRole(Auth::user(), 'Restarter')) {
            $attended_summary = clone $attendees->take(5)->get();
        } else {
            $attended_summary = clone $attendees->take(6)->get();
        }

        $invites = EventsUsers::where('event', $id)->where('status', '!=', 1);
        $invited = clone $invites->get();

        if (count($invited) > 5 && ! $event->hasFinished() && ! Auth::guest() && ! FixometerHelper::hasRole(Auth::user(), 'Restarter')) {
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
        if ($event->hasFinished() && ! Auth::guest()) {
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
            'invited_summary' => $invited_summary,
            'invited' => $invited,
            'hosts' => $hosts,
            'is_attending' => $is_attending,
            'brands' => $brands,
            'clusters' => $clusters,
            'device_images' => $device_images,
            'group_volunteers' => $group_volunteers,
            'calendar_links' => $this->generateAddToCalendarLinks($event),
        ]);
    }

    /**
     * Method to take an event object and return add to calendar links
     * @author Dean Appleton-Claydon
     * @date   2019-03-12
     * @param  object can use any Party eloquent query object
     * @return array either returns an array with the four links or an empty array in the rare instance when dateTime object is not created because the value is not correct
     */
    public function generateAddToCalendarLinks($event)
    {
        try {
            $from = DateTime::createFromFormat('Y-m-d H:i', $event->getEventDate('Y-m-d').' '.$event->getEventStart());
            $to = DateTime::createFromFormat('Y-m-d H:i', $event->getEventDate('Y-m-d').' '.$event->getEventEnd());

            $link = Link::create(trim(addslashes($event->getEventName())), $from, $to)
                            ->description(trim(addslashes(strip_tags($event->free_text))))
                                ->address(trim(addslashes($event->location)));

            return [
                'google' => $link->google(),
                'yahoo' => $link->yahoo(),
                'webOutlook' => $link->webOutlook(),
                'ics' => $link->ics(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getJoinEvent($event_id)
    {
        $user_id = Auth::id();
        $not_in_event = EventsUsers::where('event', $event_id)
        ->where('user', $user_id)
        ->where('status', '!=', 1)
        ->first();

        if (empty($not_in_event)) {
            try {
                $user_event = EventsUsers::updateOrCreate([
                    'user' => $user_id,
                    'event' => $event_id,
                ], [
                    'status' => 1,
                    'role' => 4,
                ]);

                $event = Party::find($event_id);

                $event->increment('volunteers');

                $flashData = [];
                if (! Auth::user()->isInGroup($event->theGroup->idgroups)) {
                    $flashData['prompt-follow-group'] = true;
                }

                $this->notifyHostsOfRsvp($user_event, $event_id);

                return redirect()->back()->with($flashData);
            } catch (\Exception $e) {
                $flashData['danger'] = 'Failed to join this event';
                return redirect()->back()->with($flashData);
            }
        } else {
            $flashData['warning'] = 'You are already part of this event';
            return redirect()->back()->with($flashData);
        }
    }

    public function notifyHostsOfRsvp($user_event, $event_id)
    {
        // Get users who have appropriate role and permission to email
        try {
            $hosts = User::join('events_users', 'events_users.user', '=', 'users.id')
                ->where('events_users.event', $event_id)
                ->where('events_users.role', 3)
                ->select('users.*')
                ->get();
        } catch (\Exception $e) {
            $hosts = null;
        }

        if ( ! is_null($hosts)) {
            try {
                // Get user information
                $user = User::find($user_event->user);

                //Send Notification to Host
                Notification::send($hosts, new RSVPEvent([
                    'user_name' => $user->name,
                    'event_venue' => Party::find($event_id)->venue,
                    'event_url' => url('/party/view/'.$event_id),
                ]));
            } catch (\Exception $ex) {
                Log::error('An error occurred when trying to notify host of invitation confirmation: '.$ex->getMessage());
            }
        }
    }

    public function manage($id)
    {
        $user = User::find(Auth::id());

        if ( ! FixometerHelper::hasRole($user, 'Host') && ! FixometerHelper::hasRole($user, 'Administrator')) {
            return redirect('/user/forbidden');
        }
        // $this->set('js',
        //             array('foot' => array(
        //                             '/components/jquery.floatThead/dist/jquery.floatThead.min.js'
        //             )));

        $Device = new Device;
        $Category = new Category;
        $User = new User;
        $Group = new Group;
        $Party = new Party;

        // $this->set('grouplist', $Group->findList());

        if (isset($_POST) && ! empty($_POST) && is_numeric($_POST['idparty']) && ($_POST['idparty'] > 0)) {
            $response = null;

            $partydata = $_POST['party'];
            $idparty = $_POST['idparty'];
            $Party->update($partydata, $idparty);

            if (isset($_POST['device'])) {
                $devices = $_POST['device'];

                // Rearrange files to more friendly Array
                if (isset($_FILES) && ! empty($_FILES)) {
                    $files = reflow($_FILES['device']);
                    $File = new FixometerFile;
                }
                //dbga($files);
                foreach ($devices as $i => $device) {
                    //dbga($device);
                    $error = false;
                    $device['event'] = $id;
                    $method = null;

                    if (isset($device['id']) && is_numeric($device['id'])) {
                        $method = 'update';
                        $iddevice = $device['id'];
                        unset($device['id']);
                    }

                    if ( ! isset($device['category']) || empty($device['category'])) {
                        $response['danger'] = 'Category needed! (device # '.$i.')';
                        $error = true;
                    }

                    if ( ! isset($device['repaired_by']) || empty($device['repaired_by'])) {
                        $device['repaired_by'] = 29;
                    }

                    if ($method == 'update') {
                        //echo "updating---";
                        $Device->update($device, $iddevice);
                        if (FixometerHelper::featureIsEnabled(env('FEATURE__DEVICE_PHOTOS'))) {
                            if ($files[$i]['error'] == 0) {
                                $File->simpleUpload($files[$i], 'device', $iddevice, 'Device S/N Image');
                            }
                        }
                    } else {
                        //echo "creating---";
                        $device['category_creation'] = $device['category'];
                        $iddevice = $Device->create($device);
                        if (FixometerHelper::featureIsEnabled(env('FEATURE__DEVICE_PHOTOS'))) {
                            if ($files[$i]['error'] == 0) {
                                $File->simpleUpload($files[$i], 'device', $iddevice, 'Device S/N Image');
                            }
                        }
                    }

                    $response['success'] = 'Party info updated!';
                }
            }

            if (env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {
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
                    array('key' => 'party_time',            'value' => substr($party->start, 0, -3).' - '.substr($party->end, 0, -3)),
                    array('key' => 'party_date',            'value' => date('d/m/Y', $party->event_date)),
                    array('key' => 'party_timestamp',       'value' => $party->event_timestamp),
                    array('key' => 'party_timestamp_end',   'value' => $party->event_end_timestamp),
                    array('key' => 'party_stats',           'value' => $idparty),
                    array('key' => 'party_lat',             'value' => $party->latitude),
                    array('key' => 'party_lon',             'value' => $party->longitude),

                );

                /** Start WP XML-RPC **/
                $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                $text = (empty($party->free_text) ? '...' : $party->free_text);
                $content = array(
                    'post_type' => 'party',
                    'post_title' => $party->location,
                    'post_content' => $text,
                    'custom_fields' => $custom_fields,
                );

                // Check for WP existence in DB
                // $theParty = $this->Party->findOne($idparty);
                if ( ! empty($party->wordpress_post_id)) {
                    // echo "WP id present (" . $party->wordpress_post_id . ")! Editing...<br />";
                    // we need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                    $thePost = $wpClient->getPost($party->wordpress_post_id);

                    foreach ($thePost['custom_fields'] as $i => $field) {
                        foreach ($custom_fields as $k => $set_field) {
                            if ($field['key'] == $set_field['key']) {
                                $custom_fields[$k]['id'] = $field['id'];
                            }
                        }
                    }

                    $content['custom_fields'] = $custom_fields;
                    $wpClient->editPost($party->wordpress_post_id, $content);
                } else {
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

        $party = $Party->findThis($id, true);
        $categories = $Category->listed();
        $restarters = $User->find(array('idroles' => 4));

        $party = $party[0];

        $party->co2 = 0;
        $party->ewaste = 0;
        $party->fixed_devices = 0;
        $party->repairable_devices = 0;
        $party->dead_devices = 0;

        if ( ! empty($party->devices)) {
            foreach ($party->devices as $device) {
                if ($device->isFixed()) {
                    $party->co2 += $device->co2Diverted($this->EmissionRatio, $Device->displacement);
                    $party->ewaste += $device->ewasteDiverted();
                }

                switch ($device->repair_status) {
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

        if ( ! isset($response)) {
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

    public function delete($id)
    {
        if (FixometerHelper::hasRole($this->user, 'Administrator') || (hasRole($this->user, 'Host') && in_array($id, $this->hostParties))) {
            // fetch the postID in WP to delete it later
            $party = $this->Party->findOne($id);
            $wpId = $party->wordpress_post_id;

            $usersDelete = $this->Party->deleteUserList($id);
            $r = $this->Party->delete($id);

            if ( ! $r) {
                $response = 'action=de&code=403';
            } else {
                if ( ! is_null($wpId) && is_numeric($wpId)) {
                    // delete from WordPress
                    /** Start WP XML-RPC **/
                    $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                    $wpClient->setCredentials(env('WP_XMLRPC_ENDPOINT'), env('WP_XMLRPC_USER'), env('WP_XMLRPC_PSWD'));

                    $deletion = $wpClient->deletePost($wpId);
                    if ( ! $wpId) {
                        $response = 'action=de&code=500';
                    } else {
                        $response = 'action=de&code=200';
                    }
                } else {
                    $response = 'action=de&code=200';
                }
            }

            if (FixometerHelper::hasRole($this->user, 'Host')) {
                header('Location: /host?'.$response);
            } else {
                header('Location: /party?'.$response);
            }
        } else {
            header('Location: /user/forbidden');
        }
    }

    public static function stats($id, $class = null)
    {
        $Device = new Device;

        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();

        // $this->set('framed', true);
        $event = Party::where('idevents', $id)->first();

        $eventStats = $event->getEventStats($emissionRatio);

        $eventStats['co2'] = number_format(round($eventStats['co2']), 0, '.', ',');
        // $this->set('party', $party);
        if ( ! is_null($class)) {
            return view('party.stats', [
                'framed' => true,
                'party' => $eventStats,
                'class' => 'wide',
            ]);
        }

        return view('party.stats', [
            'framed' => true,
            'party' => $eventStats,
        ]);
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

    public function getGroupEmails($event_id, $object = false)
    {
        $group_user_ids = UserGroups::where('group', Party::find($event_id)->group)
        ->where('user', '!=', Auth::user()->id)
        ->pluck('user')
        ->toArray();

        // Users already associated with the event.
        // (Not including those invited but not RSVPed)
        $event_user_ids = EventsUsers::where('event', $event_id)
        ->where('user', '!=', Auth::user()->id)
        ->where('status', 1)
        ->pluck('user')
        ->toArray();

        $unique_user_ids = array_diff($group_user_ids, $event_user_ids);

        if ($object == true) {
            $group_users = User::whereIn('id', $unique_user_ids)->get();

            return $group_users;
        }
        $group_users = User::whereIn('id', $unique_user_ids)->pluck('email')->toArray();

        return json_encode($group_users);
    }

    /**
    * This is called via ajax in the Invite Volunteers to Event modal.
    * It finds the users associated with the group that the event is for,
    * in order to quickly add them to the list of invitees.
    *
    * @param int $event_id The event for which to find associated users.
    *
    * @return Response json formatted array of relevant info on users in the group.
    */
    public function getGroupEmailsWithNames($event_id)
    {
        $group_user_ids = UserGroups::where('group', Party::find($event_id)->group)
        ->where('user', '!=', Auth::user()->id)
        ->pluck('user')
        ->toArray();

        // Users already associated with the event.
        // (Not including those invited but not RSVPed)
        $event_user_ids = EventsUsers::where('event', $event_id)
        ->where('user', '!=', Auth::user()->id)
        ->where('status', 1)
        ->pluck('user')
        ->toArray();

        $unique_user_ids = array_diff($group_user_ids, $event_user_ids);

        $group_users = User::whereIn('id', $unique_user_ids)->select('name', 'email', 'invites')->get()->toArray();

        return response()->json($group_users);
    }

    public function updateQuantity(Request $request)
    {
        $event_id = $request->input('event_id');
        $quantity = $request->input('quantity');

        $return = [
            'success' => false,
        ];

        if ((FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($event_id, Auth::user()->id)) || FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
            Party::find($event_id)->update([
                'pax' => $quantity,
            ]);

            $return = [
                'success' => true,
            ];
        }

        return response()->json($return);
    }

    public function updateVolunteerQuantity(Request $request)
    {
        $event_id = $request->input('event_id');
        $quantity = $request->input('quantity');

        $return = [
            'success' => false,
        ];

        if ((FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($event_id, Auth::user()->id)) || FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
            Party::find($event_id)->update([
                'volunteers' => $quantity,
            ]);

            $return = [
                'success' => true,
            ];
        }

        return response()->json($return);
    }

    public function removeVolunteer(Request $request)
    {
        $user_id = $request->input('user_id');
        $event_id = $request->input('event_id');

        $return = [
            'success' => false,
        ];

        //Has current logged in user got permission to remove volunteer
        if ((FixometerHelper::hasRole(Auth::user(), 'Host') && FixometerHelper::userHasEditPartyPermission($event_id, Auth::user()->id)) || FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
            //Let's get the user before we delete them
            $volunteer = EventsUsers::where('user', $user_id)->where('event', $event_id)->first();

            //Let's delete the user
            $delete_user = EventsUsers::where('user', $user_id)->where('event', $event_id)->delete();
            if ($delete_user == 1) {
                //If the user accepted the invitation, we decrement
                if ($volunteer->status == 1) {
                    Party::find($event_id)->decrement('volunteers');
                }

                //Return JSON
                $return = [
                    'success' => true,
                ];
            }
        }

        return response()->json($return);
    }

    public function postSendInvite(Request $request)
    {
        $from_id = Auth::id();
        $group_name = $request->input('group_name');
        $event_id = $request->input('event_id');
        $invite_group = $request->input('invite_group');

        $emails = explode(',', str_replace(' ', '', $request->input('manual_invite_box')));
        $message = $request->input('message_to_restarters');

        if ( ! empty($emails)) {
            $users = User::whereIn('email', $emails)->get();

            $non_users = array_diff($emails, User::whereIn('email', $emails)->pluck('email')->toArray());
            $from = User::find($from_id);

            foreach ($users as $user) {
                $user_event = EventsUsers::where('user', $user->id)->where('event', $event_id)->first();

                if (is_null($user_event) || $user_event->status != '1') {
                    $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
                    $url = url('/party/accept-invite/'.$event_id.'/'.$hash);

                    if ( ! is_null($user_event)) {
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

                    // Get Creator of Event
                    if ( ! empty($userCreator = User::find($event->user_id))) {
                        $event_details = [
                            'event_venue' => $event->venue,
                            'event_url' => url('/party/edit/'.$event->idevents),
                        ];

                        // Notify Host of event that Invites have been sent out
                        Notification::send($userCreator, new NotifyHostRSVPInvitesMade($event_details));
                    }

                    // Send Invites
                    Notification::send($user, new JoinEvent($arr, $user));
                } else {
                    $not_sent[] = $user->email;
                }
            }

            if ( ! empty($non_users)) {
                foreach ($non_users as $non_user) {
                    $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
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

            if ( ! isset($not_sent)) {
                return redirect()->back()->with('success', 'Invites Sent!');
            }

            return redirect()->back()->with('warning', 'Invites Sent - apart from these ('.implode(',', $not_sent).') who were already part of the event');
        }

        return redirect()->back()->with('warning', 'You have not entered any emails!');
    }

    public function confirmInvite($event_id, $hash)
    {
        $user_event = EventsUsers::where('status', $hash)->where('event', $event_id)->first();

        if ( ! empty($user_event)) {
            // Update event invite
            EventsUsers::where('status', $hash)->where('event', $event_id)->update([
                'status' => 1,
            ]);

            // Increment volunteers column to include latest invite
            Party::find($event_id)->increment('volunteers');

            $this->notifyHostsOfRsvp($user_event, $event_id);

            return redirect('/party/view/'.$user_event->event);
        }

        return redirect('/party/view/'.$event_id)->with('warning', 'Something went wrong - this invite is invalid or has expired');
    }

    public function cancelInvite($event_id)
    {
        $user_event = EventsUsers::where('user', Auth::user()->id)->where('event', $event_id)->delete();

        return redirect('/party/view/'.$event_id)->with('success', 'You are no longer attending this event');
    }

    public function addVolunteer(Request $request)
    {

      // Get event ID
        $event_id = $request->input('event');
        $volunteer_email_address = $request->input('volunteer_email_address');

        // Retrieve name if one exists, if no name exists and user is null as well. This volunteer is anonymous
        if ($request->has('full_name')) {
            $full_name = $request->input('full_name');
        } else {
            $full_name = null;
        }

        // User is null, this volunteer is either anonymous or no user exists
        if ($request->has('user') && $request->input('user') !== 'not-registered') {
            $user = $request->input('user');
        } else {
            $user = null;
        }

        // Check if user was invited but not RSVPed.
        $invitedUserQuery = EventsUsers::where('event', $event_id)
        ->where('user', $user)
        ->where('status', '<>', 1)
        ->whereNotNull('status')
        ->where('role', 4);
        $userWasInvited = $invitedUserQuery->count() == 1;

        if ($userWasInvited) {
            $invitedUser = $invitedUserQuery->first();
            $invitedUser->status = 1;
            $invitedUser->save();
        } else {
            //Let's add the volunteer
            EventsUsers::create([
                'event' => $event_id,
                'user' => $user,
                'status' => 1,
                'role' => 4,
                'full_name' => $full_name,
            ]);
        }

        Party::find($event_id)->increment('volunteers');

        // Send email
        if ( ! is_null($volunteer_email_address)) {
            $event = Party::find($event_id);
            $from = User::find(Auth::user()->id);

            $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
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

    public function imageUpload(Request $request, $id)
    {
        try {
            if (empty($_FILES) && !empty($request->files)) {
                // Shim to handle uploads from Tests
                $file = $request->file('file');
                $_FILES['file'] = [
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'tmp_name' => $file->getRealPath(),
                    'error' => $file->getError(),
                ];
            }

            if (!empty($_FILES)) {
                $file = new FixometerFile;
                $file->upload('file', 'image', $id, env('TBL_EVENTS'), true, false, true);

                event(new EventImagesUploaded(Party::find($id), auth()->id()));
            }

            return 'success - image uploaded';
        } catch (\Exception $e) {
            Log::info('An exception occurred when uploading image: ' . $e->getMessage());
            return Response::json('An error occurred', 400);
        }
    }

    public function deleteImage($event_id, $id, $path)
    {
        $user = Auth::user();

        $in_event = EventsUsers::where('event', $event_id)->where('user', Auth::user()->id)->first();
        if (FixometerHelper::hasRole($user, 'Administrator') || is_object($in_event)) {
            $Image = new FixometerFile;
            $Image->deleteImage($id, $path);

            return redirect()->back()->with('success', 'Thank you, the image has been deleted');
        }

        return redirect()->back()->with('warning', 'Sorry, but the image can\'t be deleted');
    }

    public function emailHosts()
    {
        if (env('APP_ENV') != 'development' && env('APP_ENV') != 'local') {
            //Get all events and hosts
            $event_users = EventsUsers::where('role', 3);
            $event_ids = $event_users->pluck('event')->toArray();
            $all_events = Party::whereIn('idevents', $event_ids)
            ->where('event_date', '=', date('Y-m-d', strtotime('-1 day')))
            ->get();

            foreach ($all_events as $event) {
                $host_ids = $event_users->where('event', $event->idevents)->pluck('user')->toArray();

                if ( ! empty($host_ids)) {
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
    public function getContributions($event_id)
    {
        $event = Party::find($event_id);

        // Check that current logged in user is a host of the event or a host of the group
        if (FixometerHelper::userHasEditPartyPermission($event_id) || FixometerHelper::userIsHostOfGroup($event->group, Auth::user()->id)) {
            $all_restarters = User::join('events_users', 'events_users.user', '=', 'users.id')
            ->where('events_users.status', 1)
            ->where('events_users.role', 4)
            ->where('events_users.event', $event_id)
            ->get();

            Notification::send($all_restarters, new EventRepairs([
                'event_name' => $event->getEventName(),
                'event_url' => url('/party/view/'.$event_id.'#devices'),
                'preferences' => url('/profile/edit'),
            ]));

            return redirect()->back()->with('success', 'Thanks - all Restarters that attended have been sent a notification');
        }

        return redirect()->back()->with('warning', 'Sorry - you do not have the correct permissions for this action');
    }

    public function deleteEvent($id)
    {
        $user = Auth::user();
        $event = Party::find($id);

        if ( ! isset($id) || is_null($event)) {
            abort(404);
        }

        // Check for authentication
        if (FixometerHelper::userHasEditPartyPermission($id) || FixometerHelper::userIsHostOfGroup($event->group, Auth::user()->id)) {
            // Let's delete everything just to be certain
            $audits = Audits::where('auditable_type', 'App\Party')->where('auditable_id', $id)->delete();
            $device = Device::where('event', $id)->delete();
            $event_users = EventsUsers::where('event', $id)->delete();
            $event = Party::where('idevents', $id)->delete();

            // Let the user know everything has been done
            return redirect('/party')->with('success', 'Event has been deleted');
        }

        return redirect()->back()->with('warning', 'You do not have permission to delete this event');
    }

    public function noDataEntered()
    {
        return redirect('/party');
    }

    /**
     * [confirmCodeInvite description]
     *
     * @author Christopher Kelker - @date 2019-03-25
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @param   Request     $request
     * @param   [type]      $code
     * @return  [type]
     */
    public function confirmCodeInvite(Request $request, $code)
    {
        // Variables
        $party = Party::where('shareable_code', $code)->first();
        $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);

        // Validate a record exists with the Event code
        if (empty($party)) {
            abort(404);
        }

        // Create a new Invite record
        $invite = Invite::create([
            'record_id' => $party->idevents,
            'email' => '',
            'hash' => $hash,
            'type' => 'event',
        ]);

        // Push this into a session variable to find by the Event prefix
        session()->push('events.'.$code, $hash);

        return redirect('/user/register')->with('auth-for-invitation', __('auth.login_before_using_shareable_link', ['login_url' => url('/login')]));
    }

    /**
     * [getEventsByKey description]
     * Get all Events where a User has an API KEY that exists,
     * and that User has Group Tags associated with it
     *
     * @author  Christopher Kelker
     * @version 1.0.0
     * @date    2019-03-13
     * @param   [type]     $api_key
     * @return  [type]
     */
     public function getEventsByKey(Request $request, $api_key, $date_from = null, $date_to = null)
     {
         $user = User::where('api_key', $api_key)->first();

         $parties = Party::join('groups', 'groups.idgroups', '=', 'events.group')
         ->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
         ->join('group_tags', 'group_tags.id', '=', 'grouptags_groups.group_tag')
         ->join('users', 'users.access_group_tag_id', '=', 'group_tags.id');

         if ( ! empty($date_from) && ! empty($date_to)) {
           $parties = $parties->where('events.event_date', '>=', date('Y-m-d', strtotime($date_from)))
           ->where('events.event_date', '<=', date('Y-m-d', strtotime($date_to)));
         }

         $parties = $parties->where([
             ['users.api_key', $user->api_key],
             ['users.access_group_tag_id', $user->access_group_tag_id]
         ])
         ->select('events.*')
         ->get();

         // If no parties are found, through 404 error
         if (empty($parties)) {
           return abort(404, 'No Events found.');
         }

         // Get Emission Ratio
         $footprintRatioCalculator = new FootprintRatioCalculator();
         $emissionRatio = $footprintRatioCalculator->calculateRatio();


         $groups = Group::join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
         ->where('group_tag', $user->access_group_tag_id)->get();

         $groups_array = collect([]);
         foreach ($groups as $key => $group) {
           $groups_array->push([
               'id' => $group->idgroups,
               'name' => $group->name,
               'description' => $group->free_text,
               'image_url' => $group->groupImagePath(),
               'volunteers' => $group->volunteers,
               'participants' => $group->getGroupStats($emissionRatio)['pax'],
               'hours_volunteered' => $group->getGroupStats($emissionRatio)['hours'],
               'parties_thrown' => $group->getGroupStats($emissionRatio)['parties'],
               'waste_prevented' => $group->getGroupStats($emissionRatio)['waste'],
               'co2_emissions_prevented' => $group->getGroupStats($emissionRatio)['co2'],
           ]);
         }

         $collection = collect([]);
         foreach ($parties as $key => $party) {

           $group = $groups_array->filter(function ($group) use($party) {
             return $group['id'] == $party->group;
           })->first();

           // Push Party to Collection
           $collection->push([
             'id' => $party->idevents,
             'group' => [$group],
             'event_date' => $party->event_date,
             'start_time' => $party->start,
             'end_time' => $party->end,
             'name' => $party->venue,
             'location' => [
                 'value' => $party->location,
                 'latitude' => $party->latitude,
                 'longitude' => $party->longitude,
             ],
             'description' => $party->free_text,
             'user' => $party_user = collect(),
             'impact' => [
                 'participants' => $party->pax,
                 'volunteers' => $party->getEventStats($emissionRatio)['volunteers'],
                 'waste_prevented' => $party->getEventStats($emissionRatio)['ewaste'],
                 'co2_emissions_prevented' => $party->getEventStats($emissionRatio)['co2'],
                 'devices_fixed' => $party->getEventStats($emissionRatio)['fixed_devices'],
                 'devices_repairable' => $party->getEventStats($emissionRatio)['repairable_devices'],
                 'devices_dead' => $party->getEventStats($emissionRatio)['dead_devices'],
             ],
             'widgets' => [
                 'headline_stats' => url("/party/stats/{$party->idevents}/wide"),
                 'co2_equivalence_visualisation' => url("/outbound/info/party/{$party->idevents}/manufacture"),
             ],
             'hours_volunteered' => $party->hoursVolunteered(),
           ]);

           if ( ! empty($party->owner)) {
             $party_user->put('id', $party->owner->id);
             $party_user->put('name', $party->owner->name);
           }
         }

         return $collection;
     }

    /**
     * [getEventByKeyAndId description]
     * Get Past Event using Route Model Binding,
     * If Event is not found, throw 404 error,
     * Else return the Event's JSON data
     *
     * @author  Christopher Kelker
     * @version 1.0.0
     * @date    2019-03-13
     * @param   [type]     $api_key
     * @param   [type]     $id
     * @return  [type]
     */
    public function getEventByKeyAndId(Request $request, $api_key, Party $party)
    {
        // If Event is not found, through 404 error
        if (empty($party) && ! $party->exists) {
            return abort(404, 'Invalid Event ID.');
        }

        // Get Emission Ratio
        $footprintRatioCalculator = new FootprintRatioCalculator();
        $emissionRatio = $footprintRatioCalculator->calculateRatio();
        $emissionRatio = ApiController::getEmissionRatio();

        // New Collection Instance
        $collection = collect([
            'id' => $party->idevents,
            'group' => [
                'id' => $party->theGroup->idgroups,
                'name' => $party->theGroup->name,
                'description' => $party->theGroup->free_text,
                'image_url' => $party->theGroup->groupImagePath(),
                'volunteers' => $party->theGroup->volunteers,
                'participants' => $party->theGroup->getGroupStats($emissionRatio)['pax'],
                'hours_volunteered' => $party->theGroup->getGroupStats($emissionRatio)['hours'],
                'parties_thrown' => $party->theGroup->getGroupStats($emissionRatio)['parties'],
                'waste_prevented' => $party->theGroup->getGroupStats($emissionRatio)['waste'],
                'co2_emissions_prevented' => $party->theGroup->getGroupStats($emissionRatio)['co2'],
            ],
            'event_date' => $party->event_date,
            'start_time' => $party->start,
            'end_time' => $party->end,
            'name' => $party->venue,
            'location' => [
                'value' => $party->location,
                'latitude' => $party->latitude,
                'longitude' => $party->longitude,
            ],
            'description' => $party->free_text,
            'user' => $party_user = collect(),
            'impact' => [
                'participants' => $party->pax,
                'volunteers' => $party->getEventStats($emissionRatio)['volunteers'],
                'waste_prevented' => $party->getEventStats($emissionRatio)['ewaste'],
                'co2_emissions_prevented' => $party->getEventStats($emissionRatio)['co2'],
                'devices_fixed' => $party->getEventStats($emissionRatio)['fixed_devices'],
                'devices_repairable' => $party->getEventStats($emissionRatio)['repairable_devices'],
                'devices_dead' => $party->getEventStats($emissionRatio)['dead_devices'],
            ],
            'widgets' => [
                'headline_stats' => url("/party/stats/{$party->idevents}/wide"),
                'co2_equivalence_visualisation' => url("/outbound/info/party/{$party->idevents}/manufacture"),
            ],
            'hours_volunteered' => $party->hoursVolunteered(),
        ]);

        if ( ! empty($party->owner)) {
            $party_user->put('id', $party->owner->id);
            $party_user->put('name', $party->owner->name);
        }

        return $collection;
    }
}
