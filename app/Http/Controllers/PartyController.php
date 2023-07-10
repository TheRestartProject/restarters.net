<?php

namespace App\Http\Controllers;

use App\Audits;
use App\Brands;
use App\Cluster;
use App\Device;
use App\Events\EditEvent;
use App\Events\EventDeleted;
use App\Events\EventImagesUploaded;
use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\Geocoder;
use App\Invite;
use App\Notifications\AdminModerationEvent;
use App\Notifications\EventRepairs;
use App\Notifications\JoinEvent;
use App\Notifications\RSVPEvent;
use App\Party;
use App\Services\DiscourseService;
use App\User;
use App\UserGroups;
use Auth;
use Carbon\Carbon;
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

    public static function expandEvent($event, $group = null, $countries = null)
    {
        // Use attributesToArray rather than getAttributes so that our custom accessors are invoked.
        $thisone = $event->attributesToArray();

        if (is_null($group)) {
            // We are showing events for multiple groups and so we need to pass the relevant group, in order that
            // we can show the group name and link to it.
            $thisone['group'] = \App\Group::with('networks')->find($event->group);

            $group_image = $thisone['group']->groupImage;
            if (is_object($group_image) && is_object($group_image->image)) {
                $thisone['group']['group_image'] = $group_image->image->path;
            }

            // We need to translate the country in the group, because it is stored in
            $thisone['group']['country'] = Fixometer::translateCountry($thisone['group']['country'], $countries);
        }

        $thisone['attending'] = Auth::user() && $event->isBeingAttendedBy(Auth::user()->id);
        $thisone['allinvitedcount'] = $event->allInvited->count();

        if (Auth::user()) {
            // We might have been invited; if so we should include the invitation link.
            $is_attending = EventsUsers::where('event', $event->idevents)->where('user', Auth::user()->id)->first();

            if ($is_attending && $is_attending->status !== 1) {
                $thisone['invitation'] = "/party/accept-invite/{$event->idevents}/{$is_attending->status}";
            }
        }

        // TODO LATER Consider whether these stats should be in the event or passed into the store.
        $thisone['stats'] = $event->getEventStats();

        // These counts are separate from the list of participants - that list is of named individuals, but you
        // can also just record a number, and that's what these are.
        $thisone['participants_count'] = $event->participants;
        $thisone['volunteers_count'] = $event->volunteers;

        $thisone['isVolunteer'] = $event->isVolunteer();
        $thisone['requiresModeration'] = $event->requiresModerationByAdmin();
        $thisone['canModerate'] = Auth::user() && (Fixometer::hasRole(Auth::user(), 'Administrator') || Fixometer::hasRole(Auth::user(), 'NetworkCoordinator'));

        $thisone['event_date_local'] = $event->eventDateLocal;
        $thisone['start_local'] = $event->startLocal;
        $thisone['end_local'] = $event->endLocal;

        $thisone['upcoming'] = $event->isUpcoming();
        $thisone['finished'] = $event->hasFinished();
        $thisone['inprogress'] = $event->isInProgress();
        $thisone['startingsoon'] = $event->isStartingSoon();
        $thisone['approved'] = $event->approved ? true : false;

        if (!empty($event->wordpress_post_id)) {
            $thisone['wordpress_post_id'] = $event->wordpress_post_id;
        }

        return $thisone;
    }

    public function index($group_id = null)
    {
        $events = [];

        $countries = array_flip(\App\Helpers\Fixometer::getAllCountries('en'));

        if (! is_null($group_id)) {
            // This is the page for a specific group's events.  We want all events for this group.
            foreach (Party::where('events.group', $group_id)->get() as $event) {
                $e = \App\Http\Controllers\PartyController::expandEvent($event, NULL, $countries);
                $events[] = $e;
            }

            $group = Group::find($group_id);
        } else {
            // This is a logged-in user's events page.  We want all relevant events.
            foreach (Party::forUser(null)->reorder()->orderBy('event_start_utc', 'DESC')->get() as $event) {
                $e = \App\Http\Controllers\PartyController::expandEvent($event, NULL, $countries);
                $events[] = $e;
            }

            if (! is_null(Auth::user()->latitude) && ! is_null(Auth::user()->longitude)) {
                // We know the location of this user, so we can also get nearby upcoming events.
                $upcoming_events_in_area = Party::upcomingEventsInUserArea(Auth::user())
                    ->whereNotIn('idevents', \Illuminate\Support\Arr::pluck($events, 'idevents'))
                    ->get();

                foreach ($upcoming_events_in_area as $event) {
                    if (Fixometer::userHasViewPartyPermission($event->idevents)) {
                        $e = self::expandEvent($event, null, $countries);
                        $e['nearby'] = true;
                        $e['all'] = true;
                        $events[] = $e;
                    }
                }
            }

            // ...and any other upcoming approved events
            $other_upcoming_events = Party::with('theGroup.networks')->future()->
                whereNotIn('idevents', \Illuminate\Support\Arr::pluck($events, 'idevents'))->
                get();

            foreach ($other_upcoming_events as $event) {
                if (Fixometer::userHasViewPartyPermission($event->idevents)) {
                    $e = self::expandEvent($event, NULL, $countries);
                    $e['all'] = TRUE;
                    $events[] = $e;
                }
            }

            $group = null;
        }

        $is_host_of_group = Fixometer::userHasEditGroupPermission($group_id, Auth::user()->id);
        $isCoordinatorForGroup = $group && Auth::user()->isCoordinatorForGroup($group);

        return view('events.index', [
            'expanded_events' => $events,
            'is_host_of_group' => $is_host_of_group,
            'isCoordinatorForGroup' => $isCoordinatorForGroup,
            'group' => $group,
        ]);
    }

    public function create(Request $request, $group_id = null)
    {
        $user = Auth::user();
        $autoapprove = $group_id ? Group::where('idgroups', $group_id)->first()->auto_approve : false;

        $groupsUserIsInChargeOf = $user->groupsInChargeOf();
        $userInChargeOfMultipleGroups = $user->hasRole('Administrator') || count($groupsUserIsInChargeOf) > 1;

        // Show an error page if they are a restarter or a host with no groups
        if (Fixometer::hasRole(Auth::user(), 'Restarter') || (count($groupsUserIsInChargeOf) == 0 && Fixometer::hasRole(Auth::user(), 'Host'))) {
            return view('events.cantcreate');
        }

        $allGroups = Group::orderBy('name')->get();

        return view('events.create', [
            'title' => 'New Party',
            'gmaps' => true,
            'allGroups' => $allGroups,
            'user' => Auth::user(),
            'user_groups' => $groupsUserIsInChargeOf,
            'selected_group_id' => $group_id,
            'userInChargeOfMultipleGroups' => $userInChargeOfMultipleGroups,
            'autoapprove' => $autoapprove,
        ]);
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

        // Fetch the nextwork here - avoids fetching for each group as we encode.
        $allGroups = Group::with('networks')->orderBy('name')->get();

        $images = $File->findImages(env('TBL_EVENTS'), $id); //NB: File facade can't find findImages may need to add

        if (! isset($images)) {
            $images = null;
        }

        $party = $Party->find($id);
        $remotePost = null;

        $audits = Party::findOrFail($id)->audits;

        return view('events.edit', [ //party.edit
            'gmaps' => true,
            'images' => $images,
            'title' => 'Edit Party',
            'allGroups' => $allGroups,
            'formdata' => PartyController::expandEvent($party, NULL),
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

        $party = $Party->find($id);
        $remotePost = null;

        return view('events.create', [
            'title' => 'Duplicate Party',
            'gmaps' => true,
            'allGroups' => $allGroups,
            'user' => Auth::user(),
            'user_groups' => $groupsUserIsInChargeOf,
            'userInChargeOfMultipleGroups' => $userInChargeOfMultipleGroups,
            'duplicateFrom' => $party->idevents,
        ]);
    }

    public function view($id)
    {
        $File = new FixometerFile;
        $Party = new Party;
        $event = Party::find($id);

        // If event no longer exists or is not visible
        if (empty($event) || !Fixometer::userHasViewPartyPermission($id)) {
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

        // Items can be logged at any time.
        $stats = $event->getEventStats(null, null, true);

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
            $link = Link::create(trim(addslashes($event->getEventName())), new DateTime($event->event_start_utc), new DateTime($event->event_end_utc))
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
                $event = Party::find($event_id);

                if (!$event) {
                    $flashData['danger'] = 'Invalid event id';

                    return redirect()->back()->with($flashData);
                } else {
                    $user_event = EventsUsers::updateOrCreate([
                                                                  'user' => $user_id,
                                                                  'event' => $event_id,
                                                              ], [
                                                                  'status' => 1,
                                                                  'role' => 4,
                                                              ]);


                    $flashData = [];
                    if (! Auth::user()->isInGroup($event->theGroup->idgroups)) {
                        $flashData['prompt-follow-group'] = true;
                    }

                    $this->notifyHostsOfRsvp($user_event, $event_id);

                    return redirect()->back()->with($flashData);
                }
            } catch (\Exception $e) {
                $flashData['danger'] = 'Failed to join this event';

                return redirect()->back()->with($flashData);
            }
        } else {
            $flashData['warning'] = 'You are already part of this event';

            // Don't log to Sentry - legitimate user error.
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

        if ($hosts && count($hosts)) {
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

    public static function stats($id)
    {
        $event = Party::where('idevents', $id)->first();

        $eventStats = $event->getEventStats();

        return view('party.stats', [
            'framed' => true,
            'party' => $eventStats,
        ]);
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
        ->where('status', '=', 1)
        ->pluck('user')
        ->toArray();

        // Users already confirmed as attending the event.
        //
        // We don't want to return users who are already invited - we shouldn't be able to invite twice.
        $event_user_ids = EventsUsers::where('event', $event_id)
        ->where('user', '!=', Auth::user()->id)
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

        if ((Fixometer::hasRole(Auth::user(), 'Host') || Fixometer::hasRole(Auth::user(), 'NetworkCoordinator') && Fixometer::userHasEditPartyPermission($event_id, Auth::user()->id)) || Fixometer::hasRole(Auth::user(), 'Administrator')) {
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

        if ((Fixometer::hasRole(Auth::user(), 'Host') || Fixometer::hasRole(Auth::user(), 'NetworkCoordinator') && Fixometer::userHasEditPartyPermission($event_id, Auth::user()->id)) || Fixometer::hasRole(Auth::user(), 'Administrator')) {
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
        // The id that's passed in is that of the events_users table, because the entry may refer to a user without
        // an id.
        $id = $request->input('id');
        $volunteer = EventsUsers::where('idevents_users', $id)->first();

        $return = [
            'success' => false,
        ];

        if ($volunteer) {
            $event_id = $volunteer->event;

            // Has current logged-in user got permission to remove volunteer?
            if (((Fixometer::hasRole(Auth::user(), 'Host') || Fixometer::hasRole(Auth::user(), 'NetworkCoordinator')) && Fixometer::userHasEditPartyPermission($event_id, Auth::user()->id)) || Fixometer::hasRole(Auth::user(), 'Administrator')) {
                //Let's delete the user
                $delete_user = $volunteer->delete();

                if ($delete_user == 1) {
                    //Return JSON
                    $return = [
                        'success' => true,
                    ];
                }
            }

            return response()->json($return);
        }
    }

    public function postSendInvite(Request $request)
    {
        $from_id = Auth::id();
        $group_name = $request->input('group_name');
        $event_id = $request->input('event_id');

        $emails = explode(',', str_replace(' ', '', $request->input('manual_invite_box')));
        $message = $request->input('message_to_restarters');

        if (! empty($emails)) {
            $invalid = [];

            foreach ($emails as $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalid[] = $email;
                }
            }

            if (count($invalid)) {
                return redirect()->back()->with('warning', __('events.invite_invalid_emails', [
                    'emails' => implode(', ', $invalid)
                ]));
            } else {
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
                            'view_url' => url('/party/view/'.$event->idevents),
                            'message' => $message,
                            'event' => $event,
                        ];

                        // Get Creator of Event
                        if (! empty($userCreator = User::find($event->user_id))) {
                            $event_details = [
                                'event_venue' => $event->venue,
                                'event_url' => url('/party/edit/'.$event->idevents),
                            ];
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
                            'view_url' => url('/party/view/'.$event->idevents),
                            'message' => $message,
                            'event' => $event,
                        ];

                        Notification::send($invite, new JoinEvent($arr));
                    }
                }

                if (! isset($not_sent)) {
                    return redirect()->back()->with('success', __('events.invite_success'));
                }

                // Don't log to Sentry - legitimate user error.
                return redirect()->back()->with('warning', __('events.invite_invalid_emails', [
                    'emails' => implode(', ', $not_sent)
                ]));
            }
        }

        \Sentry\CaptureMessage(__('events.invite_noemails'));

        return redirect()->back()->with('warning', __('events.invite_noemails'));
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

            $this->notifyHostsOfRsvp($user_event, $event_id);

            return redirect('/party/view/'.$user_event->event);
        }

        \Sentry\CaptureMessage(__('events.invite_invalid'));
        return redirect('/party/view/'.intval($event_id))->with('warning', __('events.invite_invalid'));
    }

    public function cancelInvite($event_id)
    {
        // We have to do a loop to avoid the gotcha where bulk delete operations don't invoke observers.
        foreach (EventsUsers::where('user', Auth::user()->id)->where('event', $event_id)->get() as $delete) {
            $delete->delete();
        };

        return redirect('/party/view/'.intval($event_id))->with('success', __('events.invite_cancelled'));
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

            return redirect()->back()->with('success', __('events.image_delete_success'));
        }

        \Sentry\CaptureMessage(__('events.image_delete_error'));
        return redirect()->back()->with('warning', __('events.image_delete_error'));
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
                'event_url' => url('/party/view/'.intval($event_id).'#devices')
            ]));

            return redirect()->back()->with('success', __('events.review_requested'));
        }

        \Sentry\CaptureMessage(__('events.review_requested_permissions'));
        return redirect()->back()->with('warning', __('events.review_requested_permissions'));
    }

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
            \Sentry\CaptureMessage(__('events.delete_permission'));
            return redirect()->back()->with('warning', __('events.delete_permission'));
        }

        $event = Party::findOrFail($id);

        Audits::where('auditable_type', \App\Party::class)->where('auditable_id', $id)->delete();
        Device::where('event', $id)->delete();

        // We have to do a loop to avoid the gotcha where bulk delete operations don't invoke observers.
        foreach (EventsUsers::where('event', $id)->get() as $delete) {
            $delete->delete();
        };

        $event->delete();

        event(new EventDeleted($event));

        Log::info('Event deleted');

        return redirect('/party')->with('success', __('events.delete_success'));
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
}
