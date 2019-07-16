<?php

namespace App\Http\Controllers;

use Auth;
use App\Device;
use App\EventsUsers;
use App\Events\PasswordChanged;
use App\Group;
use App\Mail\RegistrationWelcome;
use App\Preferences;
use App\Permissions;
use App\UsersPreferences;
use App\UsersPermissions;
use App\Http\Controllers\PartyController;
use App\Invite;
use App\Party;
use App\Notifications\ResetPassword;
use App\Notifications\AdminNewUser;
use App\Role;
use App\Skills;
use App\User;
use App\UserGroups;
use App\UsersSkills;
use App\RolePermissions;
use Cache;
use FixometerHelper;
use FixometerFile;
use Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Input;
use App\DripEvent;

class UserController extends Controller
{
  /**
  * Create a new controller instance.
  *
  * @return void
  */
  // public function __construct()
  // {
  //     $this->middleware('auth');
  // }

  /**
  * Show the application dashboard.
  *
  * @return \Illuminate\Http\Response
  */
    public function index($id = null)
    {
        if (is_null($id)) {
            $id = Auth::id();
        }
        $user = User::getProfile($id);

      // if(FixometerHelper::hasRole($user, 'Administrator')){
        $skill_ids = UsersSkills::where('user', $id)->pluck('skill');
        $skills = Skills::whereIn('id', $skill_ids)->pluck('skill_name')->toArray();

        return view('user.profile-new', [//user.profile
        'user' => $user,
        'skills' => $skills,
        ]);
      // return view('admin.index', [
      //   'user' => $user,
      //   'grouplist' => null,
      //   'upcomingparties' => null,
      //   'allparties' => null,
      //   'upcomingparties' => null,
      //   'waste_year_data' => null,
      //   'clusters' => null,
      // ]);
      // }
      // elseif(FixometerHelper::hasRole($user, 'Host')){
      // header('Location: /host');
      // return view('user.profile-new', [//user.profile
      //   'user' => $user,
      // ]);
      // }
      // else {
      // return view('user.profile-new', [//user.profile
      //   'user' => $user,
      // ]);
      // }
    }

    public function getProfileEdit($id = null)
    {

        if (is_null($id) || !FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
            $user = Auth::user();
        } else {
            $user = User::find($id);
        }

        $user_skills = UsersSkills::where('user', $id)->pluck('skill')->toArray();
        $user_groups = UserGroups::where('user', $id)->pluck('group')->toArray();

        $user_preferences = UsersPreferences::where('user_id', $id)->pluck('preference_id')->toArray();
        $user_permissions = UsersPermissions::where('user_id', $id)->pluck('permission_id')->toArray();

        $all_groups = Group::all();
        $all_preferences = Preferences::all();
        $all_permissions = Permissions::all();

        $groups = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
        ->join('events', 'events.group', '=', 'groups.idgroups')
        ->where('users_groups.user', auth()->id())
        ->select('groups.*')
        ->groupBy('groups.idgroups')
        ->orderBy('groups.idgroups', 'ASC')
        ->get();

        $all_group_areas = Group::whereNotNull('area')
        ->groupBy('area')
        ->get(['area'])
        ->pluck('area')
        ->toArray();

        return view('user.profile-edit', [
        'user' => $user,
        'skills' => FixometerHelper::allSkills(),
        'user_skills' => $user_skills,
        'user_groups' => $user_groups,
        'user_preferences' => $user_preferences,
        'user_permissions' => $user_permissions,
        'all_groups' => $all_groups,
        'all_preferences' => $all_preferences,
        'all_permissions' => $all_permissions,
        'groups' => $groups,
        'all_group_areas' => $all_group_areas,
        ]);
    }

    public function postProfileInfoEdit(Request $request)
    {
        $rules = [
        'name'            => 'required|string|max:255',
        'email'           => 'required|string|email|max:255',
        'age'             => 'required',
        'country'         => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        $user = User::find($id)->update([
        'email'    => $request->input('email'),
        'name'     => $request->input('name'),
        'country'  => $request->input('country'),
        'location' => $request->input('townCity'),
        'age'      => $request->input('age'),
        'gender'   => $request->input('gender'),
        'biography'=> $request->input('biography'),
        ]);

        $user = User::find($id);

        if ( $user->isDripSubscriber() ) {
          DripEvent::createOrUpdateSubscriber($user, true, auth()->user()->email, request()->input('email'));
        }

        if (!empty($user->location)) {
            $lat_long = FixometerHelper::getLatLongFromCityCountry($user->location, $user->country);
            if (!empty($lat_long)) {
                $user->latitude = $lat_long[0];
                $user->longitude = $lat_long[1];
            } else {
                $user->latitude = null;
                $user->longitude = null;
            }
        } else {
            $user->latitude = null;
            $user->longitude = null;
        }

        $user->save();

        return redirect()->back()->with('message', 'User Profile Updated!');
    }

    public function postProfilePasswordEdit(Request $request)
    {

        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        $user = User::find($id);

        if ($request->input('new-password') !== $request->input('new-password-repeat')) {
            return redirect()->back()->with('error', 'New Passwords do not match!');
        }

        if ($request->input('new-password') == $request->input('new-password-repeat') && Hash::check($request->input('current-password'), $user->password)) {
            $user->setPassword(Hash::make($request->input('new-password')));
            $user->save();

            $updateInfo = $user->update([
            'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
            'recovery_expires' => strftime('%Y-%m-%d %X', time() + (24 * 60 * 60)),
            ]);

            event(new PasswordChanged($user));

            return redirect()->back()->with('message', 'User Password Updated!');
        }

        return redirect()->back()->with('error', 'Current Password does not match!');
    }

    public function postSoftDeleteUser(Request $request)
    {

        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        $user = User::find($id);
        $old_user_name = $user->name;
        $user_id = $user->id;

        if ( $user->isDripSubscriber() ) {
          //DripEvent::deleteSubscriber($user);
          //$user->newsletter = 0;
          $user->drip_subscriber_id = null;
        }

        $user->delete(); // Will be anonymised automatically by event handlers

        if (Auth::id() !== $user_id) {
            return redirect('user/all')->with('danger', $old_user_name.'\'s account has been soft deleted');
        } else {
            return redirect('login');
        }
    }

    public function postProfilePreferencesEdit(Request $request)
    {

        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        $user = User::find($id);

        // Subscriptions only happen at registration.
        // Unsubscriptions only happen via links in newsletter.
        /*if ( is_null(request()->input('newsletter')) ) {
          $user->newsletter = 0;
          $unsubscribe_user = DripEvent::unsubscribeSubscriberFromNewsletter($user);
        } else {
          $drip_subscribe_user = DripEvent::subscribeSubscriberToNewsletter($user);
          if (!empty((array) $drip_subscribe_user)) {
            $user->newsletter = 1;
            $user->drip_subscriber_id = $drip_subscribe_user->id;
          }
        }*/

        if ($request->input('invites') !== null) :
            $user->invites = 1;
        else :
            $user->invites = 0;
        endif;

        $user->save();

        return redirect()->back()->with('message', 'User Preferences Updated!');
    }

    public function postProfileTagsEdit(Request $request)
    {

        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

      // Get user
        $user = User::find($id);

      // Update skills
        $skills = $request->input('tags');
        $user->skills()->sync($skills);

      // Look at role based on skills
        $role = FixometerHelper::skillsDetermineRole($skills);

      // Update existing user, if new role is greater than the old
        if ($user->role > $role) {
            $update_user = User::find($id)->update([
            'role' => $role
            ]);
        }

        return redirect()->back()->with('message', 'User Skills Updated!');
    }

    public function postProfilePictureEdit(Request $request)
    {

        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        if (isset($_FILES) && !empty($_FILES)) {
          // $file = $request->file('profilePhoto');
          // $path = 'images/' . $file->getClientOriginalName();
          // $image = Image::make($file)
          // // ->resize(320, 240)
          // ->orientate()
          // ->save($path);

            $file = new FixometerFile;
            $file->upload('profilePhoto', 'image', $id, env('TBL_USERS'), false, true);

            return redirect()->back()->with('message', 'Profile Picture Updated!');
        }

        return redirect()->back()->with('error', 'Failed to upload profile picture!');
    }

    public function postAdminEdit(Request $request)
    {

        if ($request->input('id') !== null) {
            $user_id = $request->input('id');
        } else {
            $user_id = Auth::id();
        }

        $user = User::find($user_id);

      // Set role for User
        $user->update([
        'role' => $request->input('user_role'),
        ]);

      // Sync relevant pivots
        $user->groups()->sync($request->input('assigned_groups'));
        $user->preferences()->sync($request->input('preferences'));
        $user->permissions()->sync($request->input('permissions'));

        return redirect()->back()->with('message', 'Admin settings updated');
    }

    public function postEdit(Request $request)
    {

        $user = User::find($request->input('id'));

        $check_password = Hash::check($request->input('password'), $user->password);

        if (!is_null($request->input('new-password')) && !$check_password) {
            return redirect()
            ->back()
            ->withErrors('Incorrect old password - please try again');
        }

        $validator = Validator::make($request->all(), [
        'name' => 'required|max:255',
        'email' => 'required|unique:users,email,'.$user->id.'|max:255',
        'location' => 'max:191',
        'new-password' => 'confirmed'
        ])->validate();

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->age = $request->input('age');
        $user->gender = $request->input('gender');
        $user->location = $request->input('location');
        if (!empty($request->input('new-password'))) {
            $user->setPassword(Hash::make($request->input('new-password')));
        }
        $user->save();

        return redirect()->back()->with('success', 'Profile updated');
    }

    public function recover()
    {

      //To display Stats
        $Device = new Device;
        $Party = new Party;
        $User = new User;

        $weights= $Device->getWeights();
        $devices= $Device->statusCount();

      // $this->set('weights', $weights);
      // $this->set('devices', $devices);
      //
      // $this->set('nextparties', $Party->findNextParties());
      // $this->set('allparties', $Party->findAll());

        $co2_years = $Device->countCO2ByYear();
      // $this->set('year_data', $co2_years);
        $stats = array();
        foreach ($co2_years as $year) {
            $stats[$year->year] = $year->co2;
        }
      // $this->set('bar_chart_stats', array_reverse($stats, true));

        $waste_years = $Device->countWasteByYear();
      // $this->set('waste_year_data', $waste_years);
        $wstats = array();
        foreach ($waste_years as $year) {
            $wstats[$year->year] = $year->waste;
        }
      // $this->set('waste_bar_chart_stats', array_reverse($wstats, true));

      //Account recovery
      // $this->set('title', 'Account recovery');

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && isset($_POST['email']) && !empty($_POST['email'])) {
            $email = $_POST['email'];
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['danger'] = 'Please input a <strong>valid</strong> email.';
            } else {
                $user = $User->where('email', $email)->first();

                if (!empty($user)) {
                    $id = $user->id;
                    $data = array();

                    // generate recovery code
                    $bytes = 32;
                    $data['recovery'] = substr(bin2hex(openssl_random_pseudo_bytes($bytes)), 0, 24);

                    // add date timestamp
                    $data['recovery_expires'] = strftime('%Y-%m-%d %X', time() + (24 * 60 * 60));

                    // update record
                    $user->update([
                    'recovery' => $data['recovery'],
                    'recovery_expires' => $data['recovery_expires'],
                    ]);

                    // send email to User
                    // $message = "<p>Hi,</p>" .
                    //          "<p>You've requested to recover your password for the " . env('APP_NAME') . ".</p>" .
                    //          "<hr/>" .
                    //          "<p>Please click on this link to recover your password: <a href=\"" . env('APP_URL') . "/user/reset/?recovery=" . $data['recovery'] . "\">" . env('APP_URL') . "/user/reset/?recovery=" . $data['recovery'] . "</a>.</p>" .
                    //          "<p>If the link doesn't work, please copy and paste it in the address bar of your browser.</p>" .
                    //          "<p>The link will be active for the next 24 hours.</p>" .
                    //          "<hr/>" .
                    //          "<p>If you have any issues, or if you did <strong>not</strong> ask to recover your password, please contact <a href='mailto:" . env('SUPPORT_CONTACT_EMAIL') . "'>" . env('SUPPORT_CONTACT_EMAIL') . "</a>.</p>" .
                    // "<p>Thanks for using the " . env('APP_NAME') . "!</p>" .
                    // "<p><em>The Restart Project</em></p>";
                    // $subject = env('APP_NAME') . ": Password recovery";
                    // $headers = "From: " . env('APP_EMAIL') . "\r\n";
                    // $headers .= "MIME-Version: 1.0\r\n";
                    // $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                    // $headers .= "Bcc: " . env('SUPPORT_CONTACT_EMAIL') . "\r\n";
                    //
                    // $sender = mail($email, $subject, $message, $headers);

                    User::find($id)->notify(new ResetPassword([
                      'url' => env('APP_URL') . "/user/reset?recovery=" . $data['recovery']
                    ]));

                    // if(!$sender){
                    //     $response['danger'] = 'Could not send email with reset instructions.';
                    // }
                    // else {
                    $response['success'] = 'Email Sent! Please check your inbox and follow instructions';
                    //}
                } else {
                    $response['danger'] = 'This email is not in our database.';
                }
            }
          // $this->set('response', $response);

            return view('auth.forgot-password', [//user.recover
            'weights' => $weights,
            'devices' => $devices,
            'nextparties' => $Party->findNextParties(),
            'allparties' => $Party->findAll(),
            'year_data' => $co2_years,
            'bar_chart_stats' => array_reverse($stats, true),
            'waste_year_data' => $waste_years,
            'waste_bar_chart_stats' => array_reverse($wstats, true),
            'title' => 'Account recovery',
            'response' => $response,
            ]);
        }

        return view('auth.forgot-password', [//user.recover
        'weights' => $weights,
        'devices' => $devices,
        'nextparties' => $Party->findNextParties(),
        'allparties' => $Party->findAll(),
        'year_data' => $co2_years,
        'bar_chart_stats' => array_reverse($stats, true),
        'waste_year_data' => $waste_years,
        'waste_bar_chart_stats' => array_reverse($wstats, true),
        'title' => 'Account recovery',
        ]);
    }

    public function reset()
    {

      //To display Stats
        $Device = new Device;
        $Party = new Party;
        $User = new User;

        $weights= $Device->getWeights();
        $devices= $Device->statusCount();

      // $this->set('weights', $weights);
      // $this->set('devices', $devices);
      //
      // $this->set('nextparties', $Party->findNextParties());
      // $this->set('allparties', $Party->findAll());

        $co2_years = $Device->countCO2ByYear();
      // $this->set('year_data', $co2_years);
        $stats = array();
        foreach ($co2_years as $year) {
            $stats[$year->year] = $year->co2;
        }
      // $this->set('bar_chart_stats', array_reverse($stats, true));

        $waste_years = $Device->countWasteByYear();
      // $this->set('waste_year_data', $waste_years);
        $wstats = array();
        foreach ($waste_years as $year) {
            $wstats[$year->year] = $year->waste;
        }
      // $this->set('waste_bar_chart_stats', array_reverse($wstats, true));

      //account recovery
      // $this->set('title', 'Account recovery');

        if (!isset($_GET['recovery']) || empty($_GET['recovery'])) {
            $valid_code = false;
        } else {
            $recovery = filter_var($_GET['recovery'], FILTER_SANITIZE_STRING);
            $user = $User->where('recovery', '=', $recovery)->first();

            if (is_object($user) && strtotime($user->recovery_expires) > time()) {
                $valid_code = true;
              // $this->set('recovery', $recovery);
            } else {
                $valid_code = false;
            }
        }
      // $this->set('valid_code', $valid_code);

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && isset($_POST['password']) && !empty($_POST['password']) && isset($_POST['confirm_password']) && !empty($_POST['confirm_password'])) {
            $recovery = $_POST['recovery'];
            $pwd      = $_POST['password'];
            $cpwd     = $_POST['confirm_password'];
            if (empty($recovery) || !filter_var($recovery, FILTER_SANITIZE_STRING)) {
                $response['danger'] = 'Recovery code invalid.';
            } elseif ($pwd !== $cpwd) {
                $response['danger'] = 'The passwords do not match';
            } else {
                $user = $User->where('recovery', '=', $recovery)->first();
                if (!empty($user)) {
                    $data = array(
                    'password' => crypt($pwd, '$1$'.strrev(md5(env('APP_KEY'))))
                    );
                    $update = $user->update($data);
                    if ($update) {
                          return redirect('login')->with('success', 'Password updated, please login to continue');
                    } else {
                        $response['danger'] = "Could not update the password.";
                    }
                } else {
                    $response['danger'] = "No account matches the recovery code";
                }
            }
        }
      // $this->set('response', $response);

        if (!isset($recovery)) {
            $recovery = null;
        }

        if (!isset($response)) {
            $response = null;
        }

        if (isset($user)) {
            $email = $user->email;
        } else {
            $email = null;
        }

        return view('auth.reset-password', [//user.reset
        'weights' => $weights,
        'devices' => $devices,
        'nextparties' => $Party->findNextParties(),
        'allparties' => $Party->findAll(),
        'year_data' => $co2_years,
        'bar_chart_stats' => array_reverse($stats, true),
        'waste_year_data' => $waste_years,
        'waste_bar_chart_stats' => array_reverse($wstats, true),
        'title' => 'Account recovery',
        'recovery' => $recovery,
        'valid_code' => $valid_code,
        'response' => $response,
        'email' => $email,
        ]);
    }




    public function all()
    {
        $user = User::find(Auth::id());

        if (FixometerHelper::hasRole($user, 'Administrator')) {
            $User = new User;
            $userlist = $User->getUserList(true)->paginate(env('PAGINATE'));

            $UserGroups = new UserGroups;
          //get permissions and group_ids for every user
            $userlist->map(function ($user) use ($User, $UserGroups) {
                $user['permissions'] = $User->getRolePermissions($user->role);
                $user['groups'] = $user->groups;
                $user['lastLogin'] = $user->lastLogin();
                $user['country'] = FixometerHelper::getCountryFromCountryCode($user->country);

                return $user;
            });

            return view('user.all', [
            'title' => 'Users',
            'user' => $user,
            'header' => true,
            'userlist' => $userlist,
            'name' => null,
            'email' => null,
            'location' => null,
            'country' => null,
            'role' => null,
            'permissions' => null,
            ]);
        } else {
            return redirect('/user/forbidden');
        }
    }

    public function search(Request $request)
    {
        $user = User::find(Auth::id());

        if (FixometerHelper::hasRole($user, 'Administrator')) {
            $User = new User;
          //Have true as parameter for eloquent collection instead of array
            $userlist = $User->getUserList(true);

          //do searches
            if ($request->input('name') !== null) {
                $userlist = $userlist->where('name', 'like', '%'.$request->input('name').'%');
            }

            if ($request->input('email') !== null) {
                $userlist = $userlist->where('email', 'like', '%'.$request->input('email').'%');
            }

            if ($request->input('location') !== null) {
                $userlist = $userlist->where('location', 'like', '%'.$request->input('location').'%');
            }

            if ($request->input('country') !== null) {
                $userlist = $userlist->where('country', '=', $request->input('country'));
            }

            if ($request->input('role') !== null) {
                $userlist = $userlist->where('users.role', '=', $request->input('role'));
            }

            if ($request->input('sort') !== null) {
                $sortField = $request->input('sort');
                if ($request->input('sortdir') == null) {
                    $sortDir = 'asc';
                } else {
                    $sortDir = $request->input('sortdir');
                }
                $userlist->orderBy($sortField, $sortDir);
            } else {
                $userlist = $userlist->orderBy('users.id', 'asc');
            }

            $userlist = $userlist->paginate(env('PAGINATE'));

            $UserGroups = new UserGroups;
          //get permissions and group_ids for every user
            $userlist->map(function ($user) use ($User, $UserGroups) {
                $user['permissions'] = $User->getRolePermissions($user->role);
                $user['groups'] = $user->groups;
                $user['lastLogin'] = $user->lastLogin();
                $user['country'] = FixometerHelper::getCountryFromCountryCode($user->country);

                return $user;
            });

            return view('user.all', [
            'title' => 'Users',
            'user' => $user,
            'header' => true,
            'userlist' => $userlist,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'location' => $request->input('location'),
            'country' => $request->input('country'),
            'role' => $request->input('role'),
            'permissions' => $request->input('permissions'),
            ]);
        } else {
            header('Location: /user/forbidden');
        }
    }


    public function create()
    {
        $user = Auth::user();

      // Administrators can add users.
        if (FixometerHelper::hasRole($user, 'Administrator')) {
            $Roles = new Role;
            $Roles = $Roles->findAll();

            $Groups = new Group;
            $Groups = $Groups->findAll();

            $User = new User;

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                $error = array();

              // We got data! Elaborate.
                $name   =       $_POST['name'];
                $email  =       $_POST['email'];
              /*$pwd    =       $_POST['password'];
              $cpwd   =       $_POST['c_password']; */
                $role   =       $_POST['role'];
                if (!isset($_POST['modal'])) {
                    $groups  =      $_POST['groups'];
                }

              // dbga($group);

                if (empty($name)) {
                    $error['name'] = 'Please input a name.';
                }

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error['email'] = 'Please input a <strong>valid</strong> email.';
                }
              /*
              if(empty($pwd) || empty($cpwd) || !($pwd === $cpwd)){
              $error['password'] = 'The password cannot be emtpy and must match with the confirmation field.';
            }
            */
                if (empty($role)) {
                    $error['role'] = 'Please select a role for the User.';
                }

                if (empty($group)) {
                    $group = null;
                }
                if (!$User->checkEmail($email)) {
                    $error['email'] = 'This email is already in use in our database. Please use another one.';
                }

                if (empty($error)) {
                    // random password
                    $pwd = bin2hex(openssl_random_pseudo_bytes(8));

                    // No errors. We can proceed and create the User.
                    $data = array(  'name'     => $name,
                    'email'    => $email,
                    'password' => crypt($pwd, '$1$'.strrev(md5(env('APP_KEY')))),
                    'role'     => $role,
                    'calendar_hash' => str_random(15),
                    //'group'    => $group
                      );


                      // add password recovery data
                      $bytes = 32;
                      $data['recovery'] = substr(bin2hex(openssl_random_pseudo_bytes($bytes)), 0, 24);
                      // add date timestamp
                      $data['recovery_expires'] = strftime('%Y-%m-%d %X', time() + (24 * 60 * 60));


                      $idUser = $User->create($data)->id;
                    if ($idUser) {
                        if (isset($groups) && !empty($groups)) {
                            $Usersgroups = new UserGroups;
                            $Usersgroups->createUsersGroups($idUser, $groups);
                        }

                            //$Session = new Session;
                            //$Session->createSession($idUser);

                        if (isset($_FILES) && !empty($_FILES)) {
                            $file = new FixometerFile;
                            $file->upload('profile', 'image', $idUser, env('TBL_USERS'), false, true);
                        }
                    }
                    if ($idUser) {
                  //Send out email

                  // send email to User
                  // $message = "<p>Hi,</p>" .
                  //          "<p>This is an automatic email to let you know that we have just created an account for you on the <strong>" . APPNAME . "</strong>.</p>" .
                  //          "<p>Please click on this link to set your password: <a href=\"" . env('APP_URL') . "/user/reset/?recovery=" . $data['recovery'] . "\">" . BASE_URL . "/user/reset/?recovery=" . $data['recovery'] . "</a>.</p>" .
                  //          "<p>If the link doesn't work, please copy and paste it in the address bar of your browser.</p>" .
                  //          "<p>The link will be active for the next 24 hours.</p>" .
                  // "<p>If you have any issues, please contact <a href='mailto:" . env('SUPPORT_CONTACT_EMAIL') . "'>" . env('SUPPORT_CONTACT_EMAIL') . "</a>.</p>" .
                  //          "<p>Thanks for using the " . env('APP_NAME') . "!</p>" .
                  //          "<p><em>The Restart Project</em></p>";
                  // $subject = env('APP_NAME') . ": Account created - please set your password";
                  // $headers = "From: " . env('APP_EMAIL') . "\r\n";
                  // $headers .= "MIME-Version: 1.0\r\n";
                  // $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                  // $headers .= "Bcc: " . env('SUPPORT_CONTACT_EMAIL') . "\r\n";
                  //
                  // $sender = mail($email, $subject, $message, $headers);

                        $response['success'] = 'User created correctly.  <strong>An email has been sent to the user to ask them to set their password.</strong>';
                    } else {
                        $response['danger'] = 'User could not be created';
                    }
                } else {
                    $response['danger'] = 'User could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
                }

                if (!isset($data)) {
                      $data = null;
                }

                if (!isset($_POST['modal'])) {
                      return view('user.create', [
                        'title' => 'New User',
                        'user' => $user,
                        'header' => true,
                        'roles' => $Roles,
                        'groups' => $Groups,
                        'response' => $response,
                        'error' => $error,
                        'originalData' => $data,
                      ]);
                } else {
                      return redirect()->back()->with('success', 'User Successfully Created!');
                }
            }

            return view('user.create', [
            'title' => 'New User',
            'user' => $user,
            'header' => true,
            'roles' => $Roles,
            'groups' => $Groups,
            ]);
        } else {
            header('Location: /user/forbidden');
        }
    }


    public function edit($id)
    {

        global $fixometer_languages;

        $user = Auth::user();
        $User = new User;

      // Administrators can edit users.
        if (FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::hasRole($user, 'Host')) {
            $Roles = new Role;
            $Roles = $Roles->findAll();

            $Groups = new Group;
            $Groups = $Groups->findAll();

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                $data = $_POST;
                $id = $_POST['id'];

                if (!FixometerHelper::hasRole($User->find($id), 'Administrator')) {
                    $sent_groups = $data['groups'];
                }

                $error = false;
                // check for email in use
                $editingUser = $User->find($id);
                if ($editingUser->email !== $data['email']) {
                    if (!$User->checkEmail($data['email'])) {
                        $error['email'] = 'The email you entered is already in use in our database. Please use another one.';
                    }
                }


                if (!empty($data['new-password'])) {
                    if ($data['new-password'] !== $data['password-confirm']) {
                        $error['password'] = 'The passwords are not identical!';
                    } else {
                        $data['password'] = crypt($data['new-password'], '$1$'.strrev(md5(env('APP_KEY'))));
                    }
                }

                unset($data['new-password']);
                unset($data['password-confirm']);

                unset($data['groups']);
                unset($data['profile']);
                unset($data['id']);


                if (!is_array($error)) {
                    $u = $User->find($id)->update($data);

                    $expire = time() + (60 * 60 * 24 * 365 * 10);
                  // setcookie(env('LANGUAGE_COOKIE'), $data['language'], $time, '/', $_SERVER['HTTP_HOST']);

                    $ug = new UserGroups;
                    if (isset($sent_groups)) {
                        $ug->createUsersGroups($id, $sent_groups);
                    }


                    if (isset($_FILES) && !empty($_FILES)) {
                        $file = new FixometerFile;
                        $file->upload('profile', 'image', $id, env('TBL_USERS'), false, true);
                    }

                    if (!$u) {
                        $response['danger'] = 'Something went wrong. Please check the data and try again.';
                    } else {
                        $response['success'] = 'User updated!';
                        if (FixometerHelper::hasRole($user, 'Host')) {
                              header('Location: /host?action=ue&code=200');
                        }
                    }

                    $userdata = User::find($id);

                    $usergroups = array();
                    $ugroups = $User->getUserGroups($id);
                    foreach ($ugroups as $g) {
                        $usergroups[] = $g->group;
                    }

                    $userdata->groups = $usergroups;

                    return view('user.edit', [
                    'title' => 'Edit User',
                    'langs' => $fixometer_languages,
                    'user' => $user,
                    'header' => true,
                    'response' => $response,
                    'roles' => $Roles,
                    'groups' => $Groups,
                    'data' => $userdata,
                    ]);
                } else {
                    $userdata = User::find($id);

                    $usergroups = array();
                    $ugroups = $User->getUserGroups($id);
                    foreach ($ugroups as $g) {
                        $usergroups[] = $g->group;
                    }

                    $userdata->groups = $usergroups;

                    return view('user.edit', [
                    'title' => 'Edit User',
                    'langs' => $fixometer_languages,
                    'user' => $user,
                    'header' => true,
                    'error' => $error,
                    'roles' => $Roles,
                    'groups' => $Groups,
                    'data' => $userdata,
                    ]);
                }
            }

            $userdata = User::find($id);

            $usergroups = array();
            $ugroups = $User->getUserGroups($id);
            foreach ($ugroups as $g) {
                $usergroups[] = $g->group;
            }

            $userdata->groups = $usergroups;

            return view('user.edit', [
            'title' => 'Edit User',
            'langs' => $fixometer_languages,
            'user' => $user,
            'header' => true,
            'roles' => $Roles,
            'groups' => $Groups,
            'data' => $userdata,
            ]);
        }
    }

    public function forbidden()
    {
        $this->set('title', 'Nope.');
        return view('users.forbidden', [
        'title' => 'Nope.',
        ]);
    }


    public function profile($id = null)
    {
        $Auth = new Auth($url);
        if (!$Auth->isLoggedIn()) {
            header('Location: /user/login');
        } else {
            $user = $Auth->getProfile();
            $this->set('user', $user);
            $this->set('header', true);
            $profile =  $this->User->profilePage($id);

      //load profile
            $this->set('profile', $profile);
            $this->set('title', $profile->name);
      // Load statistics
            $Groups  = new Group;
            $Parties = new Party;
            $Devices = new Device;


            $this->set('devices', $Devices->ofThisUser($id));
            $this->set('parties', $Parties->ofThisUser($id));
            $this->set('groups', $Groups->ofThisUser($id));

            return view('users.profile', [
            'user' => $user,
            'header' => true,
            'profile' => $profile,
            'title' => $profile->name,
            'devices' => $Devices->ofThisUser($id),
            'parties' => $Parties->ofThisUser($id),
            'groups' =>  $Groups->ofThisUser($id),
            ]);
        }
    }

    public function logout()
    {

        Auth::logout();
        return redirect('/login');
    }

    public function delete()
    {
        $Auth = new Auth($url);
        if (!$Auth->isLoggedIn()) {
            header('Location: /user/login');
        } else {
            $user = $Auth->getProfile();
            $this->set('user', $user);
            $this->set('header', true);

      // Administrators can edit users.
            if (FixometerHelper::hasRole($user, 'Administrator') || hasRole($user, 'Host')) {
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                    $id = (int)$_POST['id'];
                    $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);

                  // Delete Session
                  //$session = new Session;
                  //$session->destroySession($id);

                    if ($this->User->delete($id)) {
                          header('Location: /user/all?msg=ok');
                    } else {
                        header('Location: /user/all?msg=no');
                    }
                }
            }
        }
    }

    public function lng($lang)
    {
        global $fixometer_languages;
        if (in_array($lang, array_keys($fixometer_languages))) {
            $expire = time() + (60 * 60 * 24 * 365 * 10);
            setcookie(LANGUAGE_COOKIE, $lang, $time, '/', $_SERVER['HTTP_HOST']);
            header('Location: /user/login');
        }
    }

    public function getRegister($hash = null)
    {

        if (Auth::check() && Auth::user()->hasUserGivenConsent()) {
            return redirect('dashboard');
        }

        $stats = FixometerHelper::loginRegisterStats();
        $deviceCount = array_key_exists(0, $stats['device_count_status']) ? $stats['device_count_status'][0]->counter : 0;

        return view('auth.register-new', [
        'skills' => FixometerHelper::allSkills(),
        'co2Total' => $stats['co2Total'][0]->total_footprints,
        'wasteTotal' => $stats['co2Total'][0]->total_weights,
        'partiesCount' => count($stats['allparties']),
        'deviceCount' => $deviceCount,
        ]);
    }

    public function postRegister(Request $request, $hash = null)
    {
        if (Auth::check()) { //Existing users don't need all the same rules
            $rules = [
            'age'                 => 'required',
            'country'             => 'required',
            'my_name'             => 'honeypot',
            'my_time'             => 'required|honeytime:5',
            'consent_gdpr'        => 'required',
            'consent_past_data'   => 'required',
            'consent_future_data' => 'required',
            ];
        } else {
            $rules = [
            'name'                => 'required|string|max:255',
            'email'               => 'required|string|email|max:255|unique:users',
            'age'                 => 'required',
            'country'             => 'required',
            'password'            => 'required|string|min:6|confirmed',
            'my_name'             => 'honeypot',
            'my_time'             => 'required|honeytime:5',
            'consent_gdpr'        => 'required',
            'consent_future_data' => 'required',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $skills = $request->input('skills');
        $timestamp = date('Y-m-d H:i:s');

  // try {

        if (Auth::check()) { //Existing users are to update
            $user_id = Auth::user()->id;

            $user = User::find(Auth::user()->id);
            $user->country = $request->input('country');
            $user->location = $request->input('city');
            $user->gender = $request->input('gender');
            $user->age = $request->input('age');
            $user->consent_past_data = $timestamp;
        } else {
            $role = FixometerHelper::skillsDetermineRole($skills);

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => $role,
                'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
                'recovery_expires' => strftime('%Y-%m-%d %X', time() + (24 * 60 * 60)),
                'country' => $request->input('country'),
                'location' => $request->input('city'),
                'gender' => $request->input('gender'),
                'age' => $request->input('age'),
                'calendar_hash' => str_random(15),
            ]);
        }

        $user->generateAndSetUsername();

        // Save timestamps
        $user->consent_gdpr = $timestamp;
        $user->consent_future_data = $timestamp;

        // Opted-in to Subscribe to newsletter
        if ( ! is_null($request->input('newsletter')) && $request->input('newsletter') == 1) {
          $subscribed = true;
          $user->newsletter = 1;
        }

        $drip_subscribe_user = DripEvent::createOrUpdateSubscriber($user, $subscribed);
        $user->drip_subscriber_id = $drip_subscribe_user->id;

        if (!is_null($request->input('invites')) && $request->input('invites') == 1) { //Subscribe to invites
            $user->invites = 1;
        }

  // Now determine lat and long values from location field (if provided)
        if (!is_null($request->input('city'))) {
            $lat_long = FixometerHelper::getLatLongFromCityCountry($request->input('city'), $request->input('country'));
            if (!empty($lat_long)) {
                $user->latitude = $lat_long[0];
                $user->longitude = $lat_long[1];
            }
        }

        $user->save();

  // Notify relevant users
        $notify_users = FixometerHelper::usersWhoHavePreference('admin-new-user');
        Notification::send($notify_users, new AdminNewUser([
        'id' => $user->id,
        'name' => $user->name,
        ]));

      // Sync user skills
        if (!empty($skills)) {
            User::find($user->id)->skills()->sync($skills);
        }

      // If this is an invite
        if (!is_null($hash)) {
            $acceptance = Invite::where('hash', $hash)->first();
            if (!empty($acceptance) && $acceptance->type == 'event') {
                EventsUsers::create([
                  'user' => $user->id,
                  'event' => $acceptance->record_id,
                  'status' => 1,
                  'role' => 4,
                ]);
                    $acceptance->delete();
            } elseif (!empty($acceptance) && $acceptance->type == 'group') {
                UserGroups::create([
                  'user' => $user->id,
                  'group' => $acceptance->record_id,
                  'status' => 1,
                  'role' => 4,
                ]);
                $acceptance->delete();
            }
        }

        // Send post-registration welcome email.
        if (env('FEATURE__REGISTRATION_WELCOME_EMAIL') === true) {
            try {
                $firstName = $user->getFirstName();
                Mail::to($user)->send(new RegistrationWelcome($firstName));
            } catch (\Exception $ex) {
                Log::error('Failed to send post-registration welcome email: ' . $ex->getMessage());
            }
        }

        if (Auth::check()) { //Existing users are to update
            return redirect('dashboard');
        }

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            return redirect()->intended('dashboard');
        }
    }

    public function getOnboardingComplete()
    {
        $user = Auth::user();

        if ($user->number_of_logins < 2) {
            $user->number_of_logins += 1;
            $user->save();
        }

        return "true";
    }


    public function postEmail(Request $request)
    {

        if (User::where("email", "=", $request->get('email'))->exists()) {
            return response()->json(['message' =>  __('auth.email_address_validation')]);
        }
    }
}
