<?php

namespace App\Http\Controllers;

use App\Audits;
use App\Brands;
use App\Category;
use App\Cluster;
use App\Device;
use App\Events\ApproveEvent;
use App\Events\EditEvent;
use App\Events\EventDeleted;
use App\Events\EventImagesUploaded;
use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\FootprintRatioCalculator;
use App\Helpers\Geocoder;
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
use App\Services\DiscourseService;
use App\Session;
use App\User;
use App\UserGroups;
use Auth;
use DateTime;
use DB;
use FixometerFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lang;
use Notification;
use Spatie\CalendarLinks\Link;

class PartyController extends Controller
{
    protected $geocoder;
    protected $discourseService;

    public function __construct(Geocoder $geocoder, DiscourseService $discourseService)
    {
        $this->geocoder = $geocoder;
        $this->discourseService = $discourseService;
    }

    public static function expandEvent($event, $group)
    {
        $thisone = $event->getAttributes();

        if (is_null($group)) {
            // We are showing events for multiple groups and so we need to pass the relevant group, in order that
            // we can show the group name and link to it.
            $thisone['group'] = \App\Group::find($event->group);
        }

        $thisone['attending'] = Auth::user() && $event->isBeingAttendedBy(Auth::user()->id);
        $thisone['allinvitedcount'] = $event->allInvited->count();

        // We might have been invited; if so we should include the invitation link.
        $is_attending = EventsUsers::where('event', $event->idevents)->where('user', Auth::user()->id)->first();

        if ($is_attending && $is_attending->status !== 1) {
            $thisone['invitation'] = "/party/accept-invite/{$event->idevents}/{$is_attending->status}";
        }

        // TODO LATER Consider whether these stats should be in the event or passed into the store.
        $thisone['stats'] = $event->getEventStats();
        $thisone['participants_count'] = $event->participants;
        $thisone['volunteers_count'] = $event->allConfirmedVolunteers->count();

        $thisone['isVolunteer'] = $event->isVolunteer();
        $thisone['requiresModeration'] = $event->requiresModerationByAdmin();
        $thisone['canModerate'] = Auth::user() && (Fixometer::hasRole(Auth::user(), 'Administrator') || Fixometer::hasRole(Auth::user(), 'NetworkCoordinator'));

        return $thisone;
    }

    public function index($group_id = null)
    {
        if (Fixometer::hasRole(Auth::user(), 'Administrator')) {
            $moderate_events = Party::RequiresModeration()->get();
        } else {
            $moderate_events = null;
        }

        // Use this view for showing group only upcoming and past events
        if (! is_null($group_id)) {
            $upcoming_events = Party::upcomingEvents()
                ->where('events.group', $group_id)
                ->get();

            $past_events = Party::pastEvents()
                ->where('events.group', $group_id)
                ->get();

            $group = Group::find($group_id);
        } else {
            $upcoming_events = Party::upcomingEvents()->where('users_groups.user', Auth::user()->id)
                ->get();

            $past_events = Party::UsersPastEvents([auth()->id()])->get();

            $group = null;
        }

        // We want the upcoming events in the area, and all upcoming events, irrespective of whether or not we're
        // looking at a specific group.  We want to exclude any events we have already obtained.
        $exclude = [];

        foreach ($upcoming_events as $e) {
            $exclude[] = $e->idevents;
        }

        foreach ($past_events as $e) {
            $exclude[] = $e->idevents;
        }

        if (! is_null(Auth::user()->latitude) && ! is_null(Auth::user()->longitude)) {
            $upcoming_events_in_area = Party::upcomingEventsInUserArea(Auth::user())->whereNotIn('idevents', $exclude)->get();
        } else {
            $upcoming_events_in_area = null;
        }

        $upcoming_events_all = Party::upcomingEvents()->whereNotIn('idevents', $exclude)->get();

        //Looks to see whether user has a group already, if they do, they can create events
        $user_groups = UserGroups::where('user', Auth::user()->id)->count();

        $is_host_of_group = Fixometer::userHasEditGroupPermission($group_id, Auth::user()->id);
        $isCoordinatorForGroup = $group && Auth::user()->isCoordinatorForGroup($group);

        $expanded_events = [];

        foreach (array_merge($upcoming_events->all(), $past_events->all()) as $event) {
            $expanded_events[] = \App\Http\Controllers\PartyController::expandEvent($event, $group);
        }

        if ($upcoming_events_in_area) {
          foreach ($upcoming_events_in_area as $event) {
              $e = \App\Http\Controllers\PartyController::expandEvent($event, $group);
              $e['nearby'] = TRUE;
              $expanded_events[] = $e;
          }
        }

        if ($upcoming_events_all) {
          foreach ($upcoming_events_all as $event) {
              $e = \App\Http\Controllers\PartyController::expandEvent($event, $group);
              $e['all'] = TRUE;
              $expanded_events[] = $e;
          }
        }

        return view('events.index', [
            'moderate_events' => $moderate_events,
            'upcoming_events' => $upcoming_events,
            'past_events' => $past_events,
            'upcoming_events_in_area' => $upcoming_events_in_area,
            'upcoming_events_all' => $upcoming_events_all,
            'user_groups' => $user_groups,
            'expanded_events' => $expanded_events,
            'is_host_of_group' => $is_host_of_group,
            'isCoordinatorForGroup' => $isCoordinatorForGroup,
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
        ]);
    }

    public function allUpcoming(Request $request)
    {
        $allUpcomingEventsQuery = Party::allUpcomingEvents();

        $hasSearched = false;
        if ($request->input('from-date') !== null) {
            $allUpcomingEventsQuery->whereDate('event_date', '>=', $request->input('from-date'));
            $hasSearched = true;
        }
        if ($request->input('to-date') !== null) {
            $allUpcomingEventsQuery->whereDate('event_date', '<=', $request->input('to-date'));
            $hasSearched = true;
        }
        if ($request->has('online')) {
            $allUpcomingEventsQuery->where('online', true);
            $hasSearched = true;
        }

        $allUpcomingEventsCount = $allUpcomingEventsQuery->count();
        $allUpcomingEvents = $allUpcomingEventsQuery->paginate(env('PAGINATE'));

        return view('events.all', [
            'upcoming_events' => $allUpcomingEvents,
            'upcoming_events_count' => $allUpcomingEventsCount,
            'fromDate' => $request->input('from-date'),
            'toDate' => $request->input('to-date'),
            'online' => $request->input('online'),
            'hasSearched' => $hasSearched,
        ]);
    }

    public function create(Request $request, $group_id = null)
    {
        $user = Auth::user();

        $groupsUserIsInChargeOf = $user->groupsInChargeOf();
        $userInChargeOfMultipleGroups = $user->hasRole('Administrator') || count($groupsUserIsInChargeOf) > 1;

        // Show an error page if they are a restarter or a host with no groups
        if (Fixometer::hasRole(Auth::user(), 'Restarter') || (count($groupsUserIsInChargeOf) == 0 && Fixometer::hasRole(Auth::user(), 'Host'))) {
            return view('events.cantcreate');
        }

        $allGroups = Group::orderBy('name')->get();

        if ($request->isMethod('post')) {
            $request->validate([
                'event_date' => 'required',
                'start' => 'required',
                'end' => 'required',
                'location' => [
                    function ($attribute, $value, $fail) use ($request) {
                        if (! $request->filled('online') && ! $value) {
                            $fail(__('events.validate_location'));
                        }
                    },
                ],
            ]);

            $error = [];

            if ($request->filled('location')) {
                try {
                    $results = $this->geocoder->geocode($request->get('location'));

                    if (empty($results)) {
                        $response['danger'] = 'Party could not be created. Address not found.';

                        return view('events.create', [
                            'response' => $response,
                            'title' => 'New Party',
                            'gmaps' => true,
                            'allGroups' => $allGroups,
                            'user' => Auth::user(),
                            'user_groups' => $groupsUserIsInChargeOf,
                            'selected_group_id' => $group_id,
                        ]);
                    }

                    $latitude = $results['latitude'];
                    $longitude = $results['longitude'];
                } catch (\Exception $ex) {
                    Log::error('An error occurred during geocoding: '.$ex->getMessage());
                }
            } else {
                $latitude = null;
                $longitude = null;
            }
            $data['latitude'] = $latitude;
            $data['longitude'] = $longitude;

            $online = $request->has('online');
            $event_date = $request->input('event_date');
            $start = $request->input('start');
            $end = $request->input('end');
            $pax = 0;
            $free_text = $request->input('free_text');
            $venue = $request->input('venue');
            $location = $request->input('location');
            $group = $request->input('group');
            $user_id = Auth::user()->id;

            // formatting dates for the DB
            $event_date = date('Y-m-d', strtotime($event_date));

            if (! Fixometer::verify($event_date)) {
                $error['event_date'] = 'We must have a starting date and time.';
            }
            if (! Fixometer::verify($start)) {
                $error['name'] = 'We must have a starting date and time.';
            }

            if (empty($error)) {
                $startTime = $event_date.' '.$start;
                $endTime = $event_date.' '.$end;

                $dtStart = new DateTime($startTime);
                $dtDiff = $dtStart->diff(new DateTime($endTime));

                $hours = $dtDiff->h;

                // No errors. We can proceed and create the Party.
                $data = [
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
                    'shareable_code' => Fixometer::generateUniqueShareableCode(\App\Party::class, 'shareable_code'),
                    'online' => $online,
                ];

                $party = Party::create($data);
                $idParty = $party->idevents;
                if (is_numeric($idParty)) {
                    EventsUsers::create([
                        'event' => $idParty,
                        'user' => Auth::user()->id,
                        'status' => 1,
                        'role' => 3,
                    ]);

                    Party::find($idParty)->increment('volunteers');

                    // Notify relevant users
                    $usersToNotify = Fixometer::usersWhoHavePreference('admin-moderate-event');
                    foreach ($party->associatedNetworkCoordinators() as $coordinator) {
                        $usersToNotify->push($coordinator);
                    }
                    Notification::send($usersToNotify->unique(), new AdminModerationEvent([
                        'event_venue' => Party::find($idParty)->venue,
                        'event_url' => url('/party/edit/'.$idParty),
                    ]));

                    /* let's create the image attachment! **/
                    if (isset($_FILES) && ! empty($_FILES) && is_array($_FILES['file']['name'])) {
                        $File = new FixometerFile;
                        $files = Fixometer::rearrange($_FILES['file']);
                        foreach ($files as $upload) {
                            $File->upload($upload, 'image', $idParty, env('TBL_EVENTS'));
                        }
                    }
                } else {
                    $response['danger'] = 'Party could <strong>not</strong> be created. Something went wrong with the database.';
                }
            } else {
                $response['danger'] = 'Party could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
            }

            if (! isset($response)) {
                $response = null;
            }
            if (! isset($error)) {
                $error = null;
            }

            if (is_numeric($idParty)) {
                return redirect('/party/edit/'.$idParty)->with('success', Lang::get('events.created_success_message'));
            }

            return view('events.create', [
                'title' => 'New Party',
                'gmaps' => true,
                'allGroups' => $allGroups,
                'response' => $response,
                'error' => $error,
                'udata' => $_POST,
                'user' => Auth::user(),
                'user_groups' => $groupsUserIsInChargeOf,
                'selected_group_id' => $group_id,
                'userInChargeOfMultipleGroups' => $userInChargeOfMultipleGroups,
            ]);
        }

        return view('events.create', [
            'title' => 'New Party',
            'gmaps' => true,
            'allGroups' => $allGroups,
            'user' => Auth::user(),
            'user_groups' => $groupsUserIsInChargeOf,
            'selected_group_id' => $group_id,
            'userInChargeOfMultipleGroups' => $userInChargeOfMultipleGroups,
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
        mail($email, $subject, $message, $headers);
    }

    public function edit($id, Request $request)
    {
        $user = Auth::user();

        if (! Fixometer::userHasEditPartyPermission($id, $user->id)) {
            return redirect('/user/forbidden');
        }

        $Groups = new Group;
        $File = new FixometerFile;
        $Party = new Party;

        $groupsUserIsInChargeOf = $user->groupsInChargeOf();
        $userInChargeOfMultipleGroups = $user->hasRole('Administrator') || count($groupsUserIsInChargeOf) > 1;

        $images = $File->findImages(env('TBL_EVENTS'), $id);

        if (! isset($images)) {
            $images = null;
        }

        $allGroups = Group::orderBy('name')->get();

        if ($request->isMethod('post') && ! empty($request->post())) {
            $id = $request->post('id');
            $data = $request->post();
            unset($data['files']);
            unset($data['file']);
            unset($data['users']);
            unset($data['id']);

            // formatting dates for the DB
            $data['event_date'] = Fixometer::dbDateNoTime($data['event_date']);

            if (! empty($data['location'])) {
                $results = $this->geocoder->geocode($data['location']);

                if (empty($results)) {
                    $response['danger'] = 'Party could not be saved. Address not found.';
                    $party = $Party->findThis($id)[0];
                    $audits = Party::findOrFail($id)->audits;

                    return view('events.edit', [ //party.edit
                      'gmaps' => true,
                      'images' => $images,
                      'title' => 'Edit Party',
                      'allGroups' => $allGroups,
                      'formdata' => $party,
                      'remotePost' => null,
                      'grouplist' => $Groups->findList(),
                      'user' => Auth::user(),
                      'user_groups' => $groupsUserIsInChargeOf,
                      'userInChargeOfMultipleGroups' => $userInChargeOfMultipleGroups,
                      'audits' => $audits,
                      'response' => $response,
                  ]);
                }

                $latitude = $results['latitude'];
                $longitude = $results['longitude'];
            } else {
                $latitude = null;
                $longitude = null;
            }
            $data['latitude'] = $latitude;
            $data['longitude'] = $longitude;

            $update = [
                'event_date' => $data['event_date'],
                'start' => $data['start'],
                'end' => $data['end'],
                'free_text' => $data['free_text'],
                'online' => $request->has('online'),
                'group' => $data['group'],
                'venue' => $data['venue'],
                'location' => $data['location'],
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            $u = Party::findOrFail($id)->update($update);

            if (! $u) {
                $response['danger'] = 'Something went wrong. Please check the data and try again.';
            } else {
                $response['success'] = '<div class="row"><div class="col-md-8 col-lg-8 d-flex flex-column align-content-center">Event details updated.</div><div class="col-md-4 col-lg-4 text-right"><a href="/party/view/'.$id.'" class="btn btn-secondary">View event</a></div></div>';

                $theParty = $Party->findThis($id)[0];

                // If event has just been approved, email Restarters attached to group, and push to Wordpress.
                if (isset($data['moderate']) && $data['moderate'] == 'approve') {
                    // Notify Restarters of relevant Group
                    $event = Party::find($id);
                    $group = Group::find($event->group);

                    // Only send notifications if the event is in the future.
                    // We don't want to send emails to Restarters about past events being added.
                    if ($event->isUpcoming()) {
                        // Retrieving all users from the User model whereby they allow you send emails but their role must not include group admins
                        $group_restarters = User::join('users_groups', 'users_groups.user', '=', 'users.id')
                                        ->where('users_groups.group', $event->group)
                                        ->where('users_groups.role', 4)
                                            ->select('users.*')
                                            ->get();

                        // If there are restarters against the group
                        if (! $group_restarters->isEmpty()) {
                            // Send user a notification and email
                            Notification::send($group_restarters, new NotifyRestartersOfNewEvent([
                                'event_venue' => $event->venue,
                                'event_url' => url('/party/view/'.$event->idevents),
                                'event_group' => $group->name,
                            ]));
                        }
                    }

                    event(new ApproveEvent($event, $data));
                } elseif (! empty($theParty->wordpress_post_id)) {
                    $event = Party::find($id);
                    event(new EditEvent($event, $data));
                }

                if (isset($_POST['users']) && ! empty($_POST['users'])) {
                    $users = $_POST['users'];
                    $Party->createUserList($id, $users);
                }
            }
            if (Fixometer::hasRole($user, 'Host')) {
                header('Location: /host?action=pe&code=200');
            }

            if (! isset($images)) {
                $images = null;
            }

            if (! isset($remotePost)) {
                $remotePost = null;
            }

            $party = $Party->findThis($id)[0];

            $audits = Party::findOrFail($id)->audits;

            return view('events.edit', [ //party.edit
                'response' => $response,
                'gmaps' => true,
                'images' => $images,
                'title' => 'Edit Party',
                'allGroups' => $allGroups,
                'formdata' => $party,
                'remotePost' => $remotePost,
                'grouplist' => $Groups->findList(),
                'user' => Auth::user(),
                'user_groups' => $groupsUserIsInChargeOf,
                'userInChargeOfMultipleGroups' => $userInChargeOfMultipleGroups,
                'images' => $images,
                'audits' => $audits,
            ]);
        }

        $images = $File->findImages(env('TBL_EVENTS'), $id); //NB: File facade can't find findImages may need to add

        if (! isset($images)) {
            $images = null;
        }

        $party = $Party->findThis($id)[0];
        $remotePost = null;

        $audits = Party::findOrFail($id)->audits;

        return view('events.edit', [ //party.edit
            'gmaps' => true,
            'images' => $images,
            'title' => 'Edit Party',
            'allGroups' => $allGroups,
            'formdata' => $party,
            'remotePost' => $remotePost,
            'grouplist' => $Groups->findList(),
            'user' => Auth::user(),
            'user_groups' => $groupsUserIsInChargeOf,
            'userInChargeOfMultipleGroups' => $userInChargeOfMultipleGroups,
            'audits' => $audits,
        ]);
    }

    public function duplicate($id, Request $request)
    {
        $user = Auth::user();

        if (! Fixometer::userHasEditPartyPermission($id, $user->id)) {
            return redirect('/user/forbidden');
        }

        $File = new FixometerFile;
        $Party = new Party;
        $Device = new Device;

        $groupsUserIsInChargeOf = $user->groupsInChargeOf();
        $userInChargeOfMultipleGroups = $user->hasRole('Administrator') || count($groupsUserIsInChargeOf) > 1;

        $images = $File->findImages(env('TBL_EVENTS'), $id);

        if (! isset($images)) {
            $images = null;
        }

        $allGroups = Group::orderBy('name')->get();

        $images = $File->findImages(env('TBL_EVENTS'), $id); //NB: File facade can't find findImages may need to add

        if (! isset($images)) {
            $images = null;
        }

        $party = $Party->findThis($id)[0];
        $remotePost = null;

        // Put the values we want to preserve into the session, to be picked up by the blade template's use of old().
        $request->session()->put('_old_input.venue', $party->venue);
        $request->session()->put('_old_input.online', $party->online);
        $request->session()->put('_old_input.free_text', $party->free_text);
        $request->session()->put('_old_input.location', $party->location);
        $request->session()->put('_old_input.start', $party->start);
        $request->session()->put('_old_input.end', $party->end);

        return view('events.create', [
            'title' => 'Duplicate Party',
            'gmaps' => true,
            'allGroups' => $allGroups,
            'user' => Auth::user(),
            'user_groups' => $groupsUserIsInChargeOf,
            'selected_group_id' => $party->group,
            'userInChargeOfMultipleGroups' => $userInChargeOfMultipleGroups,
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

        // status is a string, so using 'like' rather than = otherwise
        // those with an invite string starting with '1' are included.
        $hosts = EventsUsers::where('event', $id)->where('role', 3)->where('status', 'like', '1')->get();

        if (Auth::check()) {
            $is_attending = EventsUsers::where('event', $id)->where('user', Auth::user()->id)->first();
        } else {
            $is_attending = null;
        }

        //Info for attendance tabs
        // status is a string, so using 'like' rather than = otherwise
        // those with an invite string starting with '1' are included.
        $attendees = EventsUsers::where('event', $id)->where('status', 'like', '1');
        $attended = clone $attendees->get();

        if (count($attended) > 5 && $event->hasFinished() && ! Auth::guest() && ! Fixometer::hasRole(Auth::user(), 'Restarter')) {
            $attended_summary = clone $attendees->take(5)->get();
        } else {
            $attended_summary = clone $attendees->take(6)->get();
        }

        $invites = EventsUsers::where('event', $id)->where('status', '!=', 1);
        $invited = clone $invites->get();

        if (count($invited) > 5 && ! $event->hasFinished() && ! Auth::guest() && ! Fixometer::hasRole(Auth::user(), 'Restarter')) {
            $invited_summary = clone $invites->take(5)->get();
        } else {
            $invited_summary = clone $invites->take(6)->get();
        }

        //Useful for add/edit device
        $brands = Brands::all();
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

        if ($event->isInProgress() || $event->hasFinished()) {
            $stats = $event->getEventStats();
        } else {
            $stats = [
              'force_object' => true,
            ];
        }

        return view('events.view', [
            'gmaps' => true,
            'images' => $images,
            'event' => $event,
            'stats' => $stats,
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
            'item_types' => Device::getItemTypes(),
        ]);
    }

    /**
     * Method to take an event object and return add to calendar links.
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
                $this->addToDiscourseThread($event, Auth::user());

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

        if (! is_null($hosts)) {
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

    public function addToDiscourseThread($event, $user)
    {
        if ($event->discourse_thread) {
            // We want a host of the event to add the user to the thread.
            try {
                $hosts = User::join('events_users', 'events_users.user', '=', 'users.id')
                    ->where('events_users.event', $event->idevents)
                    ->where('events_users.role', 3)
                    ->select('users.*')
                    ->get();
            } catch (\Exception $e) {
                $hosts = null;
            }

            if (! is_null($hosts) && count($hosts)) {
                $this->discourseService->addUserToPrivateMessage(
                    $event->discourse_thread,
                    $hosts[0]->username,
                    $user->username
                );
            }
        }
    }

    public static function stats($id, $class = null)
    {
        $event = Party::where('idevents', $id)->first();

        $eventStats = $event->getEventStats();

        $eventStats['co2'] = number_format(round($eventStats['co2']), 0, '.', ',');

        if (! is_null($class)) {
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

    public function getGroupEmails($event_id, $object = false)
    {
        $group_user_ids = UserGroups::where('group', Party::find($event_id)->group)
            ->pluck('user')
            ->toArray();

        // Users already associated with the event.  Normally this would include the host, unless they've been
        // removed.
        // (Not including those invited but not RSVPed)
        $event_user_ids = EventsUsers::where('event', $event_id)
            ->where('status', 'like', '1')
            ->pluck('user')
            ->toArray();

        $unique_user_ids = array_diff($group_user_ids, $event_user_ids);

        if ($object) {
            return User::whereIn('id', $unique_user_ids)->get();
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
        ->where('status', 'like', '1')
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

        if ((Fixometer::hasRole(Auth::user(), 'Host') && Fixometer::userHasEditPartyPermission($event_id, Auth::user()->id)) || Fixometer::hasRole(Auth::user(), 'Administrator')) {
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

        if ((Fixometer::hasRole(Auth::user(), 'Host') && Fixometer::userHasEditPartyPermission($event_id, Auth::user()->id)) || Fixometer::hasRole(Auth::user(), 'Administrator')) {
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
        if ((Fixometer::hasRole(Auth::user(), 'Host') && Fixometer::userHasEditPartyPermission($event_id, Auth::user()->id)) || Fixometer::hasRole(Auth::user(), 'Administrator')) {
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

        $emails = explode(',', str_replace(' ', '', $request->input('manual_invite_box')));
        $message = $request->input('message_to_restarters');

        if (! empty($emails)) {
            $users = User::whereIn('email', $emails)->get();

            $non_users = array_diff($emails, User::whereIn('email', $emails)->pluck('email')->toArray());
            $from = User::find($from_id);

            foreach ($users as $user) {
                $user_event = EventsUsers::where('user', $user->id)->where('event', $event_id)->first();

                if (is_null($user_event) || $user_event->status != '1') {
                    $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
                    $url = url('/party/accept-invite/'.$event_id.'/'.$hash);

                    if (! is_null($user_event)) {
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

                    $arr = [
                        'name' => $from->name,
                        'group' => $group_name,
                        'url' => $url,
                        'message' => $message,
                        'event' => $event,
                    ];

                    // Get Creator of Event
                    if (! empty($userCreator = User::find($event->user_id))) {
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

            if (! empty($non_users)) {
                foreach ($non_users as $non_user) {
                    $hash = substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24);
                    $url = url('/user/register/'.$hash);

                    $invite = Invite::create([
                        'record_id' => $event_id,
                        'email' => $non_user,
                        'hash' => $hash,
                        'type' => 'event',
                    ]);

                    $event = Party::find($event_id);

                    $arr = [
                        'name' => $from->name,
                        'group' => $group_name,
                        'url' => $url,
                        'message' => $message,
                        'event' => $event,
                    ];

                    Notification::send($invite, new JoinEvent($arr));
                }
            }

            if (! isset($not_sent)) {
                return redirect()->back()->with('success', 'Invites Sent!');
            }

            return redirect()->back()->with('warning', 'Invites Sent - apart from these ('.implode(',', $not_sent).') who were already part of the event');
        }

        return redirect()->back()->with('warning', 'You have not entered any emails!');
    }

    public function confirmInvite($event_id, $hash)
    {
        $user_event = EventsUsers::where('status', $hash)->where('event', $event_id)->first();

        if (! empty($user_event)) {
            // Update event invite
            EventsUsers::where('status', $hash)->where('event', $event_id)->update([
                'status' => 1,
            ]);

            // Increment volunteers column to include latest invite
            $event = Party::find($event_id);
            $event->increment('volunteers');

            $this->notifyHostsOfRsvp($user_event, $event_id);
            $this->addToDiscourseThread($event, Auth::user());

            return redirect('/party/view/'.$user_event->event);
        }

        return redirect('/party/view/'.intval($event_id))->with('warning', 'Something went wrong - this invite is invalid or has expired');
    }

    public function cancelInvite($event_id)
    {
        EventsUsers::where('user', Auth::user()->id)->where('event', $event_id)->delete();

        return redirect('/party/view/'.intval($event_id))->with('success', 'You are no longer attending this event.');
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
        if (! is_null($volunteer_email_address)) {
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

        return redirect('/party/view/'.intval($event_id))->with('success', 'Volunteer has successfully been added to event');
    }

    public function imageUpload(Request $request, $id)
    {
        try {
            if (empty($_FILES) && ! empty($request->files)) {
                // Shim to handle uploads from Tests
                $file = $request->file('file');
                $_FILES['file'] = [
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'tmp_name' => $file->getRealPath(),
                    'error' => [$file->getError()],
                ];
            }

            if (! empty($_FILES)) {
                $file = new FixometerFile;
                $file->upload('file', 'image', $id, env('TBL_EVENTS'), true, false, true);

                event(new EventImagesUploaded(Party::find($id), auth()->id()));
            }

            return 'success - image uploaded';
        } catch (\Exception $e) {
            Log::info('An exception occurred when uploading image: '.$e->getMessage());

            return response()->json('An error occurred', 400);
        }
    }

    public function deleteImage($event_id, $id, $path)
    {
        $user = Auth::user();

        $in_event = EventsUsers::where('event', $event_id)->where('user', Auth::user()->id)->first();
        if (Fixometer::hasRole($user, 'Administrator') || is_object($in_event)) {
            $Image = new FixometerFile;
            $Image->deleteImage($id, $path);

            return redirect()->back()->with('success', 'Thank you, the image has been deleted');
        }

        return redirect()->back()->with('warning', 'Sorry, but the image can\'t be deleted');
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
        if (Fixometer::userHasEditPartyPermission($event_id) || Fixometer::userIsHostOfGroup($event->group, Auth::user()->id)) {
            $all_restarters = User::join('events_users', 'events_users.user', '=', 'users.id')
            ->where('events_users.status', 1)
            ->where('events_users.role', 4)
            ->where('events_users.event', $event_id)
            ->get();

            Notification::send($all_restarters, new EventRepairs([
                'event_name' => $event->getEventName(),
                'event_url' => url('/party/view/'.intval($event_id).'#devices'),
                'preferences' => url('/profile/edit'),
            ]));

            return redirect()->back()->with('success', 'Thanks - all Restarters that attended have been sent a notification');
        }

        return redirect()->back()->with('warning', 'Sorry - you do not have the correct permissions for this action');
    }

    // TODO: is this alive?
    // It looks like recent-ish code, but I recall James mentioned recently that
    // he couldn't delete events.  Perhaps it's disappeared from the interface?

    /**
     * Called via AJAX.
     * @param id The event id.
     */
    public function deleteEvent($id)
    {
        $event = Party::findOrFail($id);
        $user = Auth::user();

        Log::info("User {$user->id} attempting to delete event {$id}");

        if (! Fixometer::userHasEditPartyPermission($id) &&
            ! Fixometer::userIsHostOfGroup($event->group, Auth::user()->id)) {
            return redirect()->back()->with('warning', 'You do not have permission to delete this event');
        }

        $event = Party::findOrFail($id);

        Audits::where('auditable_type', \App\Party::class)->where('auditable_id', $id)->delete();
        Device::where('event', $id)->delete();
        EventsUsers::where('event', $id)->delete();
        $event->delete();

        event(new EventDeleted($event));

        Log::info('Event deleted');

        return redirect('/party')->with('success', 'Event has been deleted');
    }

    /**
     * [confirmCodeInvite description].
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
        Invite::create([
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
     * and that User has Group Tags associated with it.
     *
     * @author  Christopher Kelker
     * @version 1.0.0
     * @date    2019-03-13
     * @param   [type]     $api_token
     * @return  [type]
     */
    public function getEventsByKey(Request $request, $api_token, $date_from = null, $date_to = null)
    {
        $user = User::where('api_token', $api_token)->first();

        $parties = Party::join('groups', 'groups.idgroups', '=', 'events.group')
         ->join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
         ->join('group_tags', 'group_tags.id', '=', 'grouptags_groups.group_tag')
         ->join('users', 'users.access_group_tag_id', '=', 'group_tags.id');

        if (! empty($date_from) && ! empty($date_to)) {
            $parties = $parties->where('events.event_date', '>=', date('Y-m-d', strtotime($date_from)))
           ->where('events.event_date', '<=', date('Y-m-d', strtotime($date_to)));
        }

        $parties = $parties->where([
             ['users.api_token', $user->api_token],
             ['users.access_group_tag_id', $user->access_group_tag_id],
         ])
         ->select('events.*')
         ->get();

        // If no parties are found, through 404 error
        if (! count($parties)) {
            return abort(404, 'No Events found.');
        }

        $groups = Group::join('grouptags_groups', 'grouptags_groups.group', '=', 'groups.idgroups')
         ->where('group_tag', $user->access_group_tag_id)->get();

        $groups_array = collect([]);
        foreach ($groups as $group) {
            $gstats = $group->getGroupStats();
            $groups_array->push([
               'id' => $group->idgroups,
               'name' => $group->name,
               'description' => $group->free_text,
               'image_url' => $group->groupImagePath(),
               'volunteers' => $group->volunteers,
               'participants' => $gstats['pax'],
               'hours_volunteered' => $gstats['hours'],
               'parties_thrown' => $gstats['parties'],
               'waste_prevented' => $gstats['waste'],
               'co2_emissions_prevented' => $gstats['co2'],
           ]);
        }

        $collection = collect([]);
        foreach ($parties as $key => $party) {
            $group = $groups_array->filter(function ($group) use ($party) {
                return $group['id'] == $party->group;
            })->first();
            $estats = $party->getEventStats();
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
                 'volunteers' => $estats['volunteers'],
                 'waste_prevented' => $estats['ewaste'],
                 'co2_emissions_prevented' => $estats['co2'],
                 'devices_fixed' => $estats['fixed_devices'],
                 'devices_repairable' => $estats['repairable_devices'],
                 'devices_dead' => $estats['dead_devices'],
             ],
             'widgets' => [
                 'headline_stats' => url("/party/stats/{$party->idevents}/wide"),
                 'co2_equivalence_visualisation' => url("/outbound/info/party/{$party->idevents}/manufacture"),
             ],
             'hours_volunteered' => $party->hoursVolunteered(),
           ]);

            if (! empty($party->owner)) {
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
     * Else return the Event's JSON data.
     *
     * @author  Christopher Kelker
     * @version 1.0.0
     * @date    2019-03-13
     * @param   [type]     $api_token
     * @param   [type]     $id
     * @return  [type]
     */
    public function getEventByKeyAndId(Request $request, $api_token, Party $party)
    {
        // If Event is not found, through 404 error
        if (empty($party) && ! $party->exists) {
            return abort(404, 'Invalid Event ID.');
        }

        $estats = $party->getEventStats();
        $gstats = $party->theGroup->getGroupStats();
        // New Collection Instance
        $collection = collect([
            'id' => $party->idevents,
            'group' => [
                'id' => $party->theGroup->idgroups,
                'name' => $party->theGroup->name,
                'description' => $party->theGroup->free_text,
                'image_url' => $party->theGroup->groupImagePath(),
                'volunteers' => $party->theGroup->volunteers,
                'participants' => $gstats['pax'],
                'hours_volunteered' => $gstats['hours'],
                'parties_thrown' => $gstats['parties'],
                'waste_prevented' => $gstats['waste'],
                'co2_emissions_prevented' => $gstats['co2'],
            ],
            'event_date' => $party->event_date,
            'start_time' => $party->start,
            'end_time' => $party->end,
            'name' => $party->venue,
            'location' => [
                'value' => $party->location,
                'latitude' => $party->latitude,
                'longitude' => $party->longitude,
                'area' => $party->theGroup->area,
                'postcode' => $party->theGroup->postcode,
            ],
            'description' => $party->free_text,
            'user' => $party_user = collect(),
            'impact' => [
                'participants' => $party->pax,
                'volunteers' => $estats['volunteers'],
                'waste_prevented' => $estats['ewaste'],
                'co2_emissions_prevented' => $estats['co2'],
                'devices_fixed' => $estats['fixed_devices'],
                'devices_repairable' => $estats['repairable_devices'],
                'devices_dead' => $estats['dead_devices'],
            ],
            'widgets' => [
                'headline_stats' => url("/party/stats/{$party->idevents}/wide"),
                'co2_equivalence_visualisation' => url("/outbound/info/party/{$party->idevents}/manufacture"),
            ],
            'hours_volunteered' => $party->hoursVolunteered(),
        ]);

        if (! empty($party->owner)) {
            $party_user->put('id', $party->owner->id);
            $party_user->put('name', $party->owner->name);
        }

        return $collection;
    }
}
