<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App;
use App\Models\Device;
use App\Events\PasswordChanged;
use App\Events\UserLanguageUpdated;
use App\Events\UserRegistered;
use App\Models\EventsUsers;
use App\Models\Group;
use App\Helpers\Fixometer;
use App\Http\Controllers\PartyController;
use App\Models\Invite;
use App\Models\Network;
use App\Notifications\AdminNewUser;
use App\Notifications\ResetPassword;
use App\Models\Party;
use App\Models\Permissions;
use App\Models\Preferences;
use App\Models\Role;
use App\Models\RolePermissions;
use App\Models\Skills;
use App\Models\User;
use App\Models\UserGroups;
use App\Models\UsersPermissions;
use App\Models\UsersPreferences;
use App\Models\UsersSkills;
use App\WikiSyncStatus;
use Auth;
use Cache;
use FixometerFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Lang;
use LaravelLocalization;
use Notification;

class UserController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index($id = null): View
    {
        if (is_null($id)) {
            $id = Auth::id();
        }
        $user = User::getProfile($id);

        // if(Fixometer::hasRole($user, 'Administrator')){
        $skill_ids = UsersSkills::where('user', $id)->pluck('skill');
        $skills = Skills::whereIn('id', $skill_ids)->pluck('skill_name')->toArray();

        return view('user.profile-new', [//user.profile
        'user' => $user,
        'skills' => $skills,
        ]);
    }

    public function getProfileEdit($id = null): View
    {
        if (is_null($id)) {
            $user = Auth::user();
            $id = $user->id;
        } elseif (! Fixometer::hasRole(Auth::user(), 'Administrator') &&
            ! Auth::user()->isRepairDirectorySuperAdmin() &&
            ! Auth::user()->isRepairDirectoryRegionalAdmin() &&
            Auth::user()->id !== intval($id)
        ) {
            // We don't have permissions to see any of the tabs on this page, so we shouldn't see the page.
            abort(404);
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
            'skills' => Fixometer::allSkills(),
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

    public function getNotifications(): View
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(10);

        return view('user.notifications', [
            'user' => $user,
            'notifications' => $notifications,
        ]);
    }

    public function postProfileInfoEdit(Request $request, App\Helpers\Geocoder $geocoder): RedirectResponse
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

        User::find($id)->update([
        'email'    => $request->input('email'),
        'name'     => $request->input('name'),
        'country_code' => $request->input('country'),
        'location' => $request->input('townCity'),
        'age'      => $request->input('age'),
        'gender'   => $request->input('gender'),
        'biography'=> $request->input('biography'),
        ]);

        $user = User::find($id);

        if (! empty($user->location)) {
            $geocoded = $geocoder->geocode("{$user->location}, " . Fixometer::getCountryFromCountryCode($user->country_code));
            if (! empty($geocoded)) {
                $user->latitude = $geocoded['latitude'];
                $user->longitude = $geocoded['longitude'];
            } else {
                $user->latitude = null;
                $user->longitude = null;
            }
        } else {
            $user->latitude = null;
            $user->longitude = null;
        }

        $user->save();

        return redirect()->back()->with('message', __('profile.profile_updated'));
    }

    public function postProfilePasswordEdit(Request $request): RedirectResponse
    {
        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        $user = User::find($id);

        if ($request->input('new-password') !== $request->input('new-password-repeat')) {
            return redirect()->back()->with('error', __('profile.password_new_mismatch'));
        }

        if ($request->input('new-password') == $request->input('new-password-repeat') && Hash::check($request->input('current-password'), $user->password)) {
            $oldPassword = $user->password;
            $user->setPassword(Hash::make($request->input('new-password')));
            $user->save();

            $user->update([
            'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
            'recovery_expires' => strftime('%Y-%m-%d %X', time() + (24 * 60 * 60)),
            ]);

            event(new PasswordChanged($user, $oldPassword));

            return redirect()->back()->with('message', __('profile.password_changed'));
        }

        return redirect()->back()->with('error', __('profile.password_old_mismatch'));
    }

    public function postProfileRepairDirectory(Request $request): RedirectResponse
    {
        $rules = [
            'role' => 'required|digits_between:'.Role::REPAIR_DIRECTORY_SUPERADMIN.','.Role::REPAIR_DIRECTORY_EDITOR,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $id = $request->input('id');
        $role = intval($request->input('role'));
        $user = User::find($id);

        // Check that we are allowed to change the role, based on our own role.
        Gate::authorize('changeRepairDirRole', [Auth::user(), $user, $role]);

        $user->update([
            'repairdir_role' => $role,
        ]);

        $user->save();

        return redirect()->back()->with('message', __('profile.profile_updated'));
    }

    public function storeLanguage(Request $request): RedirectResponse
    {
        if ($request->input('id') !== null) {
            $userId = $request->input('id');
        } else {
            $userId = Auth::id();
        }

        $newLanguage = $request->input('user_language');
        $user = User::find($userId);
        $user->language = $newLanguage;
        $user->save();

        // Update current language settings in the app.
        // But don't update it for an admin if they're changing someone else's settings.
        if ($userId == Auth::id()) {
            session()->put('locale', $newLanguage);
            LaravelLocalization::setLocale($newLanguage);
            App::setLocale($newLanguage);

            event(new UserLanguageUpdated($user));
        }

        return redirect()->back()->with('message', Lang::get('profile.language_updated'));
    }

    public function postSoftDeleteUser(Request $request): RedirectResponse
    {
        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        $user = User::find($id);
        $old_user_name = $user->name;
        $user_id = $user->id;

        $user->delete(); // Will be anonymised automatically by event handlers

        if (Auth::id() !== $user_id) {
            return redirect('user/all')->with('danger', __('profile.soft_deleted', [
                'name' => $old_user_name
            ]));
        } else {
            return redirect('login');
        }
    }

    public function postProfilePreferencesEdit(Request $request): RedirectResponse
    {
        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        $user = User::find($id);

        if ($request->input('invites') !== null) :
            $user->invites = 1; else :
            $user->invites = 0;
        endif;

        $user->save();

        return redirect()->back()->with('message', Lang::get('profile.preferences_updated'));
    }

    public function postProfileTagsEdit(Request $request): RedirectResponse
    {
        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        $user = User::find($id);

        $skills = $request->input('tags');
        $user->skillsold()->sync($skills);
        $user->refresh();

        $roleBasedOnSkills = Fixometer::skillsDetermineRole($skills);

        if ($roleBasedOnSkills == Role::HOST) {
            $user->convertToHost();
        }

        return redirect()->back()->with('message', Lang::get('profile.skills_updated'));
    }

    public function postProfilePictureEdit(Request $request): RedirectResponse
    {
        if ($request->input('id') !== null) {
            $id = $request->input('id');
        } else {
            $id = Auth::id();
        }

        if (isset($_FILES) && ! empty($_FILES)) {
            $file = new FixometerFile;
            $file->upload('profilePhoto', 'image', $id, env('TBL_USERS'), false, true);

            return redirect()->back()->with('message', __('profile.picture_success'));
        }

        return redirect()->back()->with('error', __('profile.picture_error'));
    }

    public function postAdminEdit(Request $request): RedirectResponse
    {
        if ($request->input('id') !== null) {
            $user_id = $request->input('id');
        } else {
            $user_id = Auth::id();
        }

        $user = User::find($user_id);

        $oldRole = $user->role;

        // Set role for User
        $user->update([
            'role' => $request->input('user_role'),
        ]);

        // If we are demoting from NetworkCoordinator, remove them from the list of coordinators for
        // any networks they are currently coordinating.
        if ($oldRole == Role::NETWORK_COORDINATOR && ($user->role == Role::HOST || $user->role == Role::RESTARTER)) {
            $user->networks()->detach();
        }

        // The user may have previously been removed from the group, which will mean they have an entry in
        // users_groups with deleted_at set.  Zap that if present so that sync() then works.  sync() doesn't
        // handle soft deletes itself.
        $groups = $request->input('assigned_groups');

        foreach ($groups as $idgroups) {
            $in_group = UserGroups::where('user', $user_id)->where('group', $idgroups)->withTrashed()->first();

            if ($in_group && $in_group->trashed()) {
                $in_group->restore();
            }
        }

        // Then sync relevant pivots
        $user->groups()->sync($groups);
        $user->preferences()->sync($request->input('preferences'));
        $user->permissions()->sync($request->input('permissions'));

        return redirect()->back()->with('message', __('profile.admin_success'));
    }

    public function recover(Request $request): View
    {
        $User = new User;

        $email = $request->get('email');

        if ($request->getMethod() == 'POST' && $email) {
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['danger'] = __('passwords.invalid');

                // Don't log to Sentry - legitimate user error.
            } else {
                $user = $User->where('email', $email)->first();

                if (! empty($user)) {
                    $id = $user->id;
                    $data = [];

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

                    User::find($id)->notify(new ResetPassword([
                      'url' => env('APP_URL').'/user/reset?recovery='.$data['recovery'],
                    ]));

                    $response['success'] = __('passwords.sent');
                } else {
                    $response['danger'] = __('passwords.user');

                    // Don't log to Sentry - legitimate user error.
                }
            }

            return view('auth.forgot-password', [//user.recover
                'title' => __('passwords.recover_title'),
                'response' => $response,
            ]);
        }

        return view('auth.forgot-password', [//user.recover
            'title' => __('passwords.recover_title'),
        ]);
    }

    public function reset(Request $request)
    {
        $User = new User;
        $user = null;

        $recovery = $request->recovery;

        if (!$recovery) {
            $valid_code = false;
        } else {
            $recovery = filter_var($recovery, FILTER_SANITIZE_STRING);
            $user = $User->where('recovery', '=', $recovery)->first();

            if (is_object($user) && strtotime($user->recovery_expires) > time()) {
                $valid_code = true;
            } else {
                $valid_code = false;
            }
        }

        $pwd = $request->post('password');
        $cpwd = $request->post('confirm_password');
        $response = null;
        $email = null;

        if ($request->getMethod() == 'POST' && $pwd && $cpwd) {
            if (!$valid_code) {
                $response['danger'] = __('passwords.token');
                \Sentry\CaptureMessage($response['danger']);
            } elseif ($pwd !== $cpwd) {
                $response['danger'] = __('passwords.match');

                // Don't log to Sentry - legitimate user error.
            } else {
                $email = $user->email;
                $oldPassword = $user->password;

                $update = $user->update([
                    'password' => Hash::make($pwd),
                ]);

                if ($update) {
                    event(new PasswordChanged($user, $oldPassword));
                    return redirect('login')->with('success', __('passwords.updated'));
                } else {
                    $response['danger'] = __('passwords.failed');
                    \Sentry\CaptureMessage($response['danger']);
                }
            }
        } else {
            $email = $user ? $user->email : null;
        }

        return view('auth.reset-password', [
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

        if (Fixometer::hasRole($user, 'Administrator')) {
            $User = new User;
            $userlist = $User->getUserList(true)->paginate(env('PAGINATE'));

            //get permissions and group_ids for every user
            $userlist->map(function ($user) use ($User) {
                $user['permissions'] = $User->getRolePermissions($user->role);
                $user['groups'] = $user->groups;
                $user['lastLogin'] = $user->lastLogin();
                $user['country'] = Fixometer::getCountryFromCountryCode($user->country_code);

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

        if (Fixometer::hasRole($user, 'Administrator')) {
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

            //get permissions and group_ids for every user
            $userlist->map(function ($user) use ($User) {
                $user['permissions'] = $User->getRolePermissions($user->role);
                $user['groups'] = $user->groups;
                $user['lastLogin'] = $user->lastLogin();
                $user['country'] = Fixometer::getCountryFromCountryCode($user->country_code);

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

    public function create(Request $request)
    {
        $user = Auth::user();

        // Administrators can add users.
        if (Fixometer::hasRole($user, 'Administrator')) {
            $Roles = new Role;
            $Roles = $Roles->findAll();

            $Groups = new Group;
            $Groups = $Groups->findAll();

            $User = new User;

            $error = [];

            // We got data! Elaborate.
            $name = $request->get('name');
            $email = $request->get('email');
            $role = $request->get('role');
            if (! $request->has('modal')) {
                $groups = $request->get('groups');
            }

            if (empty($name)) {
                $error['name'] = 'Please input a name.';
            }

            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error['email'] = 'Please input a <strong>valid</strong> email.';
            }

            if (empty($role)) {
                $error['role'] = 'Please select a role for the User.';
            }

            if (empty($group)) {
                $group = null;
            }
            if (! $User->checkEmail($email)) {
                $error['email'] = 'This email is already in use in our database. Please use another one.';
            }

            if (empty($error)) {
                // random password
                $pwd = bin2hex(openssl_random_pseudo_bytes(8));

                // No errors. We can proceed and create the User.
                $data = ['name'     => $name,
                'email'    => $email,
                'password' => Hash::make($pwd),
                'role'     => $role,
                'calendar_hash' => Str::random(15),
                //'group'    => $group
                  ];

                // add password recovery data
                $bytes = 32;
                $data['recovery'] = substr(bin2hex(openssl_random_pseudo_bytes($bytes)), 0, 24);
                // add date timestamp
                $data['recovery_expires'] = strftime('%Y-%m-%d %X', time() + (24 * 60 * 60));

                $idUser = $User->create($data)->id;
                if ($idUser) {
                    if (isset($groups) && ! empty($groups)) {
                        $Usersgroups = new UserGroups;
                        $Usersgroups->createUsersGroups($idUser, $groups);
                    }

                    if (isset($_FILES) && ! empty($_FILES)) {
                        $file = new FixometerFile;
                        $file->upload('profile', 'image', $idUser, env('TBL_USERS'), false, true);
                    }
                }
                if ($idUser) {
                    $response['success'] = 'User created correctly.  <strong>NB No email has been sent to the user.</strong>';
                } else {
                    $response['danger'] = 'User could not be created';
                    \Sentry\CaptureMessage($response['danger']);
                }
            } else {
                $response['danger'] = 'User could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
                \Sentry\CaptureMessage($response['danger']);
            }

            if (! isset($data)) {
                $data = null;
            }

            if (! isset($_POST['modal'])) {
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
                return redirect()->back()->with('success', __('profile.create_success'));
            }
        } else {
            header('Location: /user/forbidden');
        }
    }

    public function edit($id, Request $request): View
    {
        global $fixometer_languages;

        $user = Auth::user();
        $User = new User;

        // Administrators can edit users.
        if (Fixometer::hasRole($user, 'Administrator') || Fixometer::hasRole($user, 'Host')) {
            $Roles = new Role;
            $Roles = $Roles->findAll();

            $Groups = new Group;
            $Groups = $Groups->findAll();

            $data = $request->post();

            if (! Fixometer::hasRole($User->find($id), 'Administrator')) {
                $sent_groups = $data['groups'];
            }

            $error = false;
            // check for email in use
            $editingUser = $User->find($id);
            if ($editingUser->email !== $data['email'] && ! $User->checkEmail($data['email'])) {
                $error['email'] = 'The email you entered is already in use in our database. Please use another one.';
            }

            if (! empty($data['new-password'])) {
                if ($data['new-password'] !== $data['password-confirm']) {
                    $error['password'] = 'The passwords are not identical!';
                } else {
                    $data['password'] = Hash::make($data['new-password']);
                }
            }

            unset($data['new-password']);
            unset($data['password-confirm']);

            unset($data['groups']);
            unset($data['profile']);
            unset($data['id']);

            if (! is_array($error)) {
                $u = $User->find($id)->update($data);

                $ug = new UserGroups;
                if (isset($sent_groups)) {
                    $ug->createUsersGroups($id, $sent_groups);
                }

                if (isset($_FILES) && ! empty($_FILES)) {
                    $file = new FixometerFile;
                    $file->upload('profile', 'image', $id, env('TBL_USERS'), false, true);
                }

                if (! $u) {
                    $response['danger'] = 'Something went wrong. Please check the data and try again.';
                    \Sentry\CaptureMessage($response['danger']);
                } else {
                    $response['success'] = 'User updated!';
                    if (Fixometer::hasRole($user, 'Host')) {
                        // Use @ for phpunit tests.
                        @header('Location: /host?action=ue&code=200');
                    }
                }

                $userdata = User::find($id);

                $usergroups = [];
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

                $usergroups = [];
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
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        return redirect('/login');
    }

    public function getRegister($hash = null)
    {
        if (Auth::check() && Auth::user()->hasUserGivenConsent()) {
            return redirect('dashboard');
        }

        $stats = Fixometer::loginRegisterStats();
        $deviceCount = array_key_exists(0, $stats['device_count_status']) ? $stats['device_count_status'][0]->counter : 0;

        $activeRepairNetworkId = session()->get('repair_network');
        $network = Network::find($activeRepairNetworkId);
        $showNewsletterSignup = $network->shortname == 'restarters';

        return view('auth.register-new', [
            'skills' => Fixometer::allSkills(),
            'co2Total' => $stats['waste_stats'][0]->powered_footprint + $stats['waste_stats'][0]->unpowered_footprint,
            'wasteTotal' => $stats['waste_stats'][0]->powered_waste + $stats['waste_stats'][0]->unpowered_waste,
            'partiesCount' => count($stats['allparties']),
            'deviceCount' => $deviceCount,
            'showNewsletterSignup' => $showNewsletterSignup,
        ]);
    }

    public function postRegister(Request $request, $hash = null): RedirectResponse
    {
        $geocoder = new \App\Helpers\Geocoder();

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
            $user = User::find(Auth::user()->id);
            $user->country_code = $request->input('country');
            $user->location = $request->input('city');
            $user->gender = $request->input('gender');
            $user->age = $request->input('age');
            $user->consent_past_data = $timestamp;
        } else {
            $role = Fixometer::skillsDetermineRole($skills);

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => $role,
                'recovery' => substr(bin2hex(openssl_random_pseudo_bytes(32)), 0, 24),
                'recovery_expires' => strftime('%Y-%m-%d %X', time() + (24 * 60 * 60)),
                'country_code' => $request->input('country'),
                'location' => $request->input('city'),
                'gender' => $request->input('gender'),
                'age' => $request->input('age'),
                'calendar_hash' => Str::random(15),
                'username' => '',
            ]);
        }

        $user->generateAndSetUsername();

        // Save timestamps
        $user->consent_gdpr = $timestamp;
        $user->consent_future_data = $timestamp;

        // Opted-in to Subscribe to newsletter
        if (! is_null($request->input('newsletter')) && $request->input('newsletter') == 1) {
            $subscribed = true;
            $user->newsletter = 1;
        } else {
            $subscribed = false;
        }

        // 'invites' refers to receiving notifications about groups or events near the user.
        if (! is_null($request->input('invites')) && $request->input('invites') == 1) { //Subscribe to invites
            $user->invites = 1;
        }

        // Now determine lat and long values from location field (if provided)
        if (! is_null($request->input('city'))) {
            $geocoded = $geocoder->geocode("{$request->input('city')}, {$request->input('country')}");
            if (! empty($geocoded)) {
                $user->latitude = $geocoded['latitude'];
                $user->longitude = $geocoded['longitude'];
            }
        }

        // Login will occur immediately after this registration has completed.
        // So wiki account creation will be attempted.
        // This could in future be made conditional on who the user is,
        // but for now every single user is given a wiki account.
        $user->wiki_sync_status = WikiSyncStatus::CreateAtLogin;

        $user->save();

        // Notify relevant users
        $notify_users = Fixometer::usersWhoHavePreference('admin-new-user');
        Notification::send($notify_users, new AdminNewUser([
        'id' => $user->id,
        'name' => $user->name,
        ]));

        // Sync user skills
        if (! empty($skills)) {
            User::find($user->id)->skillsold()->sync($skills);
        }

        // If this is an invite
        if (! is_null($hash)) {
            $acceptance = Invite::where('hash', $hash)->first();
            if (! empty($acceptance) && $acceptance->type == 'event') {
                EventsUsers::create([
                  'user' => $user->id,
                  'event' => $acceptance->record_id,
                  'status' => 1,
                  'role' => 4,
                ]);
                $acceptance->delete();
            } elseif (! empty($acceptance) && $acceptance->type == 'group') {
                UserGroups::create([
                  'user' => $user->id,
                  'group' => $acceptance->record_id,
                  'status' => 1,
                  'role' => 4,
                ]);
                $acceptance->delete();
            }
        }

        event(new UserRegistered($user));

        $redirectTo = 'dashboard';

        if ($request->session()->has('redirectTo') && $request->session()->has('redirectTime')) {
            if (time() - $request->session()->get('redirectTime') < 3600) {
                // We have recently visited a page to which we want to return.   This is an intentionally
                // partial solution to the problem of redirecting after login.
                $redirectTo = $request->session()->get('redirectTo');
            }
        }

        if (Auth::check()) { //Existing users are to update
            return redirect($redirectTo);
        }

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            return redirect()->intended($redirectTo);
        }
    }

    public function getOnboardingComplete()
    {
        $user = Auth::user();

        if ($user->number_of_logins < 2) {
            $user->number_of_logins += 1;
            $user->save();
        }

        return 'true';
    }

    public function postEmail(Request $request): JsonResponse
    {
        if (User::where('email', '=', $request->get('email'))->exists()) {
            return response()->json(['message' =>  __('auth.email_address_validation')]);
        }
    }

    public static function getThumbnail(Request $request): JsonResponse
    {
        $user = User::where('mediawiki', $request->input('wiki_username'))->first();

        if (! $user) {
            abort('404', 'Wiki user not found');
        }

        if (isset($user->getProfile($user->id)->path) && ! is_null($user->getProfile($user->id)->path)) {
            $thumbnailPath = config('app.url').'/uploads/thumbnail_'.$user->getProfile($user->id)->path;
        } else {
            $thumbnailPath = config('app.url').'/images/placeholder-avatar.png';
        }

        return response()->json($thumbnailPath);
    }

    public function getUserMenus(Request $request): JsonResponse
    {
        $user = User::where('mediawiki', $request->input('wiki_username'))->first();

        if (! $user) {
            abort('404', 'Wiki user not found');
        }

        $menus = [];

        if (($user->hasRole('Administrator') || $user->hasPermission('verify-translation-access') || $user->hasRole('NetworkCoordinator'))) {
            $items = [];

            if ($user->hasRole('Administrator')) {
                $items['Brands'] = route('brands');
                $items['Skills'] = route('skills');
                $items['Group tags'] = route('tags');
                $items['Categories'] = route('category');
                $items['Users'] = route('users');
                $items['Roles'] = route('roles');
                $items[Lang::get('networks.general.networks')] = route('networks.index');
            }

            if ($user->hasPermission('verify-translation-access')) {
                $items['Translations'] = url('/translations/view/admin');
            }

            if ($user->hasRole('NetworkCoordinator')) {
                if (count($user->networks) == 1) {
                    $network = Auth::user()->networks->first();
                    $items[Lang::get('networks.general.particular_network', ['networkName' => $network->name])] = route('networks.show', $network->id);
                } else {
                    $items[Lang::get('networks.general.networks')] = route('networks.index');
                }
            }

            $adminMenu = ['name' => 'Administrator', 'items' => $items];
            $adminMenu['svg'] = self::adminMenuSvg;
            $menus['Administrator'] = $adminMenu;
        }

        if ($user->hasRole('Administrator') || $user->hasRole('Host')) {
            $items = [];
            $items[Lang::get('general.party_reporting')] = url('/search');

            $reportingMenu = [
                'name' => Lang::get('general.reporting'),
                'items' => $items,
                'svg' => self::reportingMenuSvg,
            ];

            $menus['Reporting'] = $reportingMenu;
        }

        $generalMenu = [
            'name' => Lang::get('general.general'),
            'items' => [
                Lang::get('general.profile') => url('/profile/edit/'.$user->id),
                Lang::get('auth.change_password') => url('/profile/edit/'.$user->id.'#change-password'),
                Lang::get('general.logout') => url('/logout'),
            ],
            'svg' => self::generalMenuSvg,
        ];
        $menus['General'] = $generalMenu;

        return response()->json($menus);
    }

    const adminMenuSvg = '<svg width="15" height="15" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M5.625 1.185a4.456 4.456 0 0 1 4.454 4.454 4.456 4.456 0 0 1-4.454 4.454 4.456 4.456 0 0 1-4.454-4.454 4.456 4.456 0 0 1 4.454-4.454zm0 2.28a2.175 2.175 0 0 1 0 4.347 2.174 2.174 0 0 1 0-4.347z"/><ellipse cx="4.854" cy=".162" rx=".205" ry=".162"/><ellipse cx="6.396" cy=".162" rx=".205" ry=".162"/><path d="M4.854 0h1.542v1.046H4.854z"/><path d="M6.601.162H4.649l-.206 1.172h2.364L6.601.162z"/><ellipse cx="6.396" cy="11.088" rx=".205" ry=".162"/><ellipse cx="4.854" cy="11.088" rx=".205" ry=".162"/><path d="M4.854 10.204h1.542v1.046H4.854z"/><path d="M4.649 11.088h1.952l.206-1.172H4.443l.206 1.172zM1.102 2.193c.081-.081.197-.094.261-.031.063.064.049.18-.031.26-.08.081-.196.094-.26.031-.063-.064-.05-.18.03-.26zm1.091-1.091c.08-.08.196-.093.26-.03.063.064.05.18-.031.26-.08.08-.196.094-.26.031-.063-.064-.05-.18.031-.261z"/><path d="M2.193 1.102L1.102 2.193l.74.739 1.09-1.09-.739-.74z"/><path d="M2.453 1.072L1.072 2.453l.683.973 1.671-1.671-.973-.683zm7.695 7.985c-.081.081-.197.094-.261.031-.063-.064-.049-.18.031-.26.08-.081.196-.094.26-.031.063.064.05.18-.03.26zm-1.091 1.091c-.08.08-.196.093-.26.03-.063-.064-.05-.18.031-.26.08-.08.196-.094.26-.031.063.064.05.18-.031.261z"/><path d="M9.057 10.148l1.091-1.091-.74-.739-1.09 1.09.739.74z"/><path d="M8.797 10.178l1.381-1.381-.683-.973-1.671 1.671.973.683zM0 6.396c0-.114.073-.206.162-.206.09 0 .163.092.163.206 0 .113-.073.205-.163.205-.089 0-.162-.092-.162-.205zm0-1.542c0-.113.073-.205.162-.205.09 0 .163.092.163.205 0 .114-.073.206-.163.206C.073 5.06 0 4.968 0 4.854z"/><path d="M0 4.854v1.542h1.046V4.854H0z"/><path d="M.162 4.649v1.952l1.172.206V4.443l-1.172.206zm11.088.205c0 .114-.073.206-.162.206-.09 0-.163-.092-.163-.206 0-.113.073-.205.163-.205.089 0 .162.092.162.205zm0 1.542c0 .113-.073.205-.162.205-.09 0-.163-.092-.163-.205 0-.114.073-.206.163-.206.089 0 .162.092.162.206z"/><path d="M11.25 6.396V4.854h-1.046v1.542h1.046z"/><path d="M11.088 6.601V4.649l-1.172-.206v2.364l1.172-.206zm-8.895 3.547c-.081-.081-.094-.197-.031-.261.064-.063.18-.049.26.031.081.08.094.196.031.26-.064.063-.18.05-.26-.03zM1.102 9.057c-.08-.08-.093-.196-.03-.26.064-.063.18-.05.26.031.08.08.094.196.031.26-.064.063-.18.05-.261-.031z"/><path d="M1.102 9.057l1.091 1.091.739-.74-1.09-1.09-.74.739z"/><path d="M1.072 8.797l1.381 1.381.973-.683-1.671-1.671-.683.973zm7.985-7.695c.081.081.094.197.031.261-.064.063-.18.049-.26-.031-.081-.08-.094-.196-.031-.26.064-.063.18-.05.26.03zm1.091 1.091c.08.08.093.196.03.26-.064.063-.18.05-.26-.031-.08-.08-.094-.196-.031-.26.064-.063.18-.05.261.031z"/><path d="M10.148 2.193L9.057 1.102l-.739.74 1.09 1.09.74-.739z"/><path d="M10.178 2.453L8.797 1.072l-.973.683 1.671 1.671.683-.973z"/></g></svg>';
    const reportingMenuSvg = '<svg width="19" height="13" viewBox="0 0 15 11" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M1.598 7.937a1.053 1.053 0 1 1 .438 2.06 1.053 1.053 0 0 1-.438-2.06zm2.403-4.869a1.224 1.224 0 0 1 .509 2.393 1.223 1.223 0 1 1-.509-2.393z"/><path d="M4.51 5.461L3.133 3.777.865 8.514l1.902.909L4.51 5.461z"/><path d="M3.991 5.241l1.249-1.68 3.131 3.637-.926 1.966-3.454-3.923z"/><path d="M9.389 9.035l2.77-5.008-1.611-1.054-2.578 4.47 1.419 1.592z"/><path d="M13.393.265l-.351-.188-4.009 2.706.024.394 4.001 2.159.335-.22V.265z"/><circle cx="8.371" cy="8.4" r="1.202"/><path d="M9.12 2.748a.229.229 0 1 1-.185.265.229.229 0 0 1 .185-.265zM13.124.04a.23.23 0 0 1 .08.451.23.23 0 0 1-.265-.186.228.228 0 0 1 .185-.265zm.001 4.868a.229.229 0 1 1 .08.45.229.229 0 0 1-.08-.45z"/></g></svg>';
    const generalMenuSvg = '<svg width="15" height="13" viewBox="0 0 12 10" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M11.25 6.245H.002v2.25s-.038.75.208 1.066c.242.311.997.269.997.269l8.953-.011s.565.039.843-.26c.259-.278.247-.929.247-.929V6.245zm0-.625H6.887V4.618H4.365V5.62H.002V1.946s.008-.267.105-.386c.098-.12.335-.14.335-.14l10.29-.004s.237-.027.385.1c.133.114.133.43.133.43V5.62z"/><path d="M7.592 0v1.946H3.66V0h3.932zm-.705.666H4.365v.75h2.522v-.75z"/></g></svg>';
}
