<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\User;
use App\Role;
use App\Group;
use App\Device;
use App\Party;
use App\UserGroups;
use App\Session;

use FixometerHelper;
use FixometerFile;
use Auth;

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
        $user = User::getProfile(Auth::id());

        if(FixometerHelper::hasRole($user, 'Administrator')){
            // header('Location: /admin');
            return view('user.profile', [
              'user' => $user,
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
        }
        elseif(FixometerHelper::hasRole($user, 'Host')){
            // header('Location: /host');
            return view('user.profile', [
              'user' => $user,
            ]);
        }
        else {
          return view('user.profile', [
            'user' => $user,
          ]);
        }
    }

    public function postEdit(Request $request) {

      $user = User::find($request->input('id'));

      $check_password = Hash::check($request->input('password'), $user->password);

      if ( !is_null($request->input('new-password')) && !$check_password ) {
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

    // public function test() {
    //   $u = new User;
    //   dd($u->checkEmail('will@wecreatedigital.co.uk'));
    // }

    // public function login(){
    //
    //     $Auth = new Auth($url);
    //     if($Auth->isLoggedIn()){
    //
    //         $user = $Auth->getProfile();
    //
    //         if(hasRole($user, 'Administrator')){
    //             header('Location: /admin');
    //         }
    //         elseif(hasRole($user, 'Host')){
    //             header('Location: /host');
    //         }
    //         else {
    //             header('Location: /user/forbidden');
    //         }
    //
    //     }
    //     else {
    //
    //         $this->set('title', 'Login');
    //         $this->set('charts', true);
    //
    //         // set up stuff for engagin login page
    //         $Device = new Device;
    //         $Party = new Party;
    //
    //         $weights= $Device->getWeights();
    //         $devices= $Device->statusCount();
    //
    //         $this->set('weights', $weights);
    //         $this->set('devices', $devices);
    //
    //         $this->set('nextparties', $Party->findNextParties());
    //         $this->set('allparties', $Party->findAll());
    //
    //         $co2_years = $Device->countCO2ByYear();
    //         $this->set('year_data', $co2_years);
    //         $stats = array();
    //         foreach($co2_years as $year){
    //             $stats[$year->year] = $year->co2;
    //         }
    //         $this->set('bar_chart_stats', array_reverse($stats, true));
    //
    //         $waste_years = $Device->countWasteByYear();
    //         $this->set('waste_year_data', $waste_years);
    //         $wstats = array();
    //         foreach($waste_years as $year){
    //             $wstats[$year->year] = $year->waste;
    //         }
    //         $this->set('waste_bar_chart_stats', array_reverse($wstats, true));
    //
    //         if($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET)){
    //             $reset_result = $_GET['reset'];
    //
    //             if (!empty($reset_result) && $reset_result == 'ok') {
    //                 $response['success'] = '<strong>Password reset successfully</strong>. You can now login with your new password.';
    //
    //                 $this->set('response', $response);
    //             }
    //         }
    //
    //         if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
    //
    //             $response = array();
    //
    //             $uput_email = $_POST['email'];
    //             $uput_password = $_POST['password'];
    //
    //             if(empty($uput_email) || !filter_var($uput_email, FILTER_VALIDATE_EMAIL)){
    //                 $response['danger'] = '<strong>Invalid/Empty email</strong>. Please input a valid email address.';
    //             }
    //             if(empty($uput_password)){
    //                 $response['danger'] = '<strong>Empty Password</strong>. Please input a password.';
    //             }
    //
    //             if(!isset($response['danger'])){
    //                 // No errors, we can proceed and see if we can auth this guy here.
    //
    //                 $user = $this->User->find(array(
    //                                                 'email' => $uput_email,
    //                                                 'password' => crypt($uput_password, '$1$' . SECRET)
    //                                             )
    //                                         );
    //
    //                 if(!empty($user)){
    //                     $Auth = new Auth;
    //                     if(!$Auth->isLoggedIn()){
    //
    //                         $pass = $Auth->authorize($user[0]->idusers);
    //
    //                     }
    //                     else {
    //                         $pass = true;
    //                     }
    //
    //                     if($pass == true){
    //                       // fetch lang preference
    //                       $lang = (isset($_COOKIE[LANGUAGE_COOKIE]) ? $_COOKIE[LANGUAGE_COOKIE] : 'en');
    //                       $this->User->update(array( 'language' => $lang ), $user[0]->idusers);
    //
    //                         if(hasRole($user[0], 'Administrator')){
    //                             header('Location: /admin');
    //                         }
    //                         elseif(hasRole($user[0], 'Host')){
    //                             header('Location: /host');
    //                         }
    //                         else {
    //                             header('Location: /user/forbidden');
    //                         }
    //
    //                     }
    //                 }
    //                 else {
    //                     $response['danger'] = 'No user account was found for the email and password provided. Please check your details and try again.';
    //                     $this->set('response', $response);
    //                     //header('Location: /user/login');
    //                 }
    //             }
    //             else {
    //                 $this->set('response', $response);
    //             }
    //
    //         }
    //     }
    // }
    //
    public function recover(){

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
      foreach($co2_years as $year){
          $stats[$year->year] = $year->co2;
      }
      // $this->set('bar_chart_stats', array_reverse($stats, true));

      $waste_years = $Device->countWasteByYear();
      // $this->set('waste_year_data', $waste_years);
      $wstats = array();
      foreach($waste_years as $year){
          $wstats[$year->year] = $year->waste;
      }
      // $this->set('waste_bar_chart_stats', array_reverse($wstats, true));

      //Account recovery
      // $this->set('title', 'Account recovery');

      if(strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && isset($_POST['email']) && !empty($_POST['email'])){
        $email = $_POST['email'];
        if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            $response['danger'] = 'Please input a <strong>valid</strong> email.';
        } else {
          $user = $User->where('email', $email)->first();
          if(!empty($user)){
            $id = $user->id;
            $data = array();
            // generate recovery code
            $bytes = 32;
            $data['recovery'] = substr( bin2hex(openssl_random_pseudo_bytes($bytes)), 0, 24 );
            // add date timestamp
            $data['recovery_expires'] = strftime( '%Y-%m-%d %X', time() + (24 * 60 * 60));

            // update record
            $user->update([
                'recovery' => substr( bin2hex(openssl_random_pseudo_bytes($bytes)), 0, 24 ),
                'recovery_expires' => strftime( '%Y-%m-%d %X', time() + (24 * 60 * 60)),
            ]);

            // send email to User
            $message = "<p>Hi,</p>" .
                     "<p>You've requested to recover your password for the " . env('APP_NAME') . ".</p>" .
                     "<hr/>" .
                     "<p>Please click on this link to recover your password: <a href=\"" . env('APP_URL') . "/user/reset/?recovery=" . $data['recovery'] . "\">" . env('APP_URL') . "/user/reset/?recovery=" . $data['recovery'] . "</a>.</p>" .
                     "<p>If the link doesn't work, please copy and paste it in the address bar of your browser.</p>" .
                     "<p>The link will be active for the next 24 hours.</p>" .
                     "<hr/>" .
                     "<p>If you have any issues, or if you did <strong>not</strong> ask to recover your password, please contact <a href='mailto:" . env('SUPPORT_CONTACT_EMAIL') . "'>" . env('SUPPORT_CONTACT_EMAIL') . "</a>.</p>" .
            "<p>Thanks for using the " . env('APP_NAME') . "!</p>" .
            "<p><em>The Restart Project</em></p>";
            $subject = env('APP_NAME') . ": Password recovery";
            $headers = "From: " . env('APP_EMAIL') . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
            $headers .= "Bcc: " . env('SUPPORT_CONTACT_EMAIL') . "\r\n";

            $sender = mail($email, $subject, $message, $headers);

            if(!$sender){
                $response['danger'] = 'Could not send email with reset instructions.';
            }
            else {
                $response['success'] = 'Email Sent! Please check your inbox and follow instructions.  <strong>Please allow up to 10 minutes for your email to arrive, and please check your spam folder.</strong>';
            }

          } else {
            $response['danger'] = 'This email is not in our database.';
          }

        }
        // $this->set('response', $response);

        return view('user.recover', [
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

      return view('user.recover', [
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

    public function reset(){

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
            foreach($co2_years as $year){
                $stats[$year->year] = $year->co2;
            }
            // $this->set('bar_chart_stats', array_reverse($stats, true));

            $waste_years = $Device->countWasteByYear();
            // $this->set('waste_year_data', $waste_years);
            $wstats = array();
            foreach($waste_years as $year){
                $wstats[$year->year] = $year->waste;
            }
            // $this->set('waste_bar_chart_stats', array_reverse($wstats, true));

      //account recovery
      // $this->set('title', 'Account recovery');

      if( !isset($_GET['recovery']) || empty($_GET['recovery']) ){
        $valid_code = false;
      } else {
        $recovery = filter_var($_GET['recovery'], FILTER_SANITIZE_STRING);
        $user = $User->find(array('recovery' => $recovery));

        if( strtotime($user[0]->recovery_expires) > time() ) {
          $valid_code = true;
          // $this->set('recovery', $recovery);
        }
      }
      // $this->set('valid_code', $valid_code);

      if(strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && isset($_POST['password']) && !empty($_POST['password']) && isset($_POST['confirm_password']) && !empty($_POST['confirm_password'])){
        $recovery = $_POST['recovery'];
        $pwd      = $_POST['password'];
        $cpwd     = $_POST['confirm_password'];
        if(empty($recovery) || !filter_var($recovery, FILTER_SANITIZE_STRING)){
            $response['danger'] = 'Recovery code invalid.';
        }
        else if($pwd !== $cpwd){
          $response['danger'] = 'The passwords do not match';
        }

        else {
          $user = $User->find(array('recovery' => $recovery));
          if(!empty($user)){
            $data = array(
              'password' => crypt($pwd, '$1$'.strrev(md5(env('APP_KEY'))))
            );
            $update = $user->update($user[0]->idusers, $data);
            if($update){
              header('Location: /user/login?reset=ok');
            }
            else {
              $response['danger'] = "Could not update the password.";
            }
          }
          else {
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

      return view('user.reset', [
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
      ]);
    }




    public function all() {
        // $this->set('title', 'Users');

        $user = User::find(Auth::id());
        // $this->set('user', $user);
        // $this->set('header', true);

        if(FixometerHelper::hasRole($user, 'Administrator')){
            $User = new User;
            $userlist = $User->getUserList();
            // $this->set('userlist', $userlist);

            return view('user.all', [
              'title' => 'Users',
              'user' => $user,
              'header' => true,
              'userlist' => $userlist,
            ]);

        } else {
            header('Location: /user/forbidden');
        }
    }

    public function create() {

        $user = Auth::user();

        // Administrators can add users.
        if(FixometerHelper::hasRole($user, 'Administrator')){

            $Roles = new Role;
            $Roles = $Roles->findAll();

            $Groups = new Group;
            $Groups = $Groups->findAll();

            $User = new User;

            if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                $error = array();

                // We got data! Elaborate.
                $name   =       $_POST['name'];
                $email  =       $_POST['email'];
                /*$pwd    =       $_POST['password'];
                $cpwd   =       $_POST['c_password']; */
                $role   =       $_POST['role'];
                $groups  =      $_POST['groups'];

                // dbga($group);

                if(empty($name)){
                    $error['name'] = 'Please input a name.';
                }

                if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
                    $error['email'] = 'Please input a <strong>valid</strong> email.';
                }
                /*
                if(empty($pwd) || empty($cpwd) || !($pwd === $cpwd)){
                    $error['password'] = 'The password cannot be emtpy and must match with the confirmation field.';
                }
                */
                if(empty($role)){
                    $error['role'] = 'Please select a role for the User.';
                }

                if(empty($group)){
                    $group = NULL;
                }
                if(!$User->checkEmail($email)){
                    $error['email'] = 'This email is already in use in our database. Please use another one.';
                }

                if(empty($error)) {
                    // random password
                    $pwd = bin2hex(openssl_random_pseudo_bytes(8));

                    // No errors. We can proceed and create the User.
                    $data = array(  'name'     => $name,
                                    'email'    => $email,
                                    'password' => crypt($pwd, '$1$'.strrev(md5(env('APP_KEY')))),
                                    'role'     => $role,
                                    //'group'    => $group
                                );

                    // add password recovery data
                    $bytes = 32;
                    $data['recovery'] = substr( bin2hex(openssl_random_pseudo_bytes($bytes)), 0, 24 );
                    // add date timestamp
                    $data['recovery_expires'] = strftime( '%Y-%m-%d %X', time() + (24 * 60 * 60));


                    $idUser = $User->create($data)->id;
                    if($idUser){

                        if(isset($groups) && !empty($groups)){
                            $Usersgroups = new UserGroups;
                            $Usersgroups->createUsersGroups($idUser, $groups);
                        }

                        $Session = new Session;
                        $Session->createSession($idUser);

                        if(isset($_FILES) && !empty($_FILES)){
                            $file = new FixometerFile;
                            $file->upload('profile', 'image', $idUser, env('TBL_USERS'), false, true);
                        }

                    }
                    if($idUser){
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
                    }
                    else {
                        $response['danger'] = 'User could not be created';
                    }
                }
                else {
                    $response['danger'] = 'User could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';

                }

                if (!isset($data)) {
                  $data = null;
                }

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
            }

            return view('user.create', [
              'title' => 'New User',
              'user' => $user,
              'header' => true,
              'roles' => $Roles,
              'groups' => $Groups,
            ]);

        }
        else {
            header('Location: /user/forbidden');
        }

    }


    public function edit($id){

        global $fixometer_languages;

        $user = Auth::user();
        $User = new User;

        // Administrators can edit users.
        if(FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::hasRole($user, 'Host')){

            $Roles = new Role;
            $Roles = $Roles->findAll();

            $Groups = new Group;
            $Groups = $Groups->findAll();

            if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
                $data = $_POST;
                $id = $_POST['id'];

                if (!FixometerHelper::hasRole($User->find($id), 'Administrator')) {
                  $sent_groups = $data['groups'];
                }

                $error = false;
                // check for email in use
                $editingUser = $User->find($id);
                if($editingUser->email !== $data['email']){
                    if(!$User->checkEmail($data['email'])){
                        $error['email'] = 'The email you entered is already in use in our database. Please use another one.';
                    }
                }


                if(!empty($data['new-password'])){
                    if($data['new-password'] !== $data['password-confirm']){
                        $error['password'] = 'The passwords are not identical!';
                    }
                    else {
                        $data['password'] = crypt($data['new-password'], '$1$'.strrev(md5(env('APP_KEY'))));
                    }
                }

                unset($data['new-password']);
                unset($data['password-confirm']);

                unset($data['groups']);
                unset($data['profile']);
                unset($data['id']);


                if(!is_array($error)){
                    $u = $User->find($id)->update($data);

                    $expire = time() + (60 * 60 * 24 * 365 * 10);
                    // setcookie(env('LANGUAGE_COOKIE'), $data['language'], $time, '/', $_SERVER['HTTP_HOST']);

                    $ug = new UserGroups;
                    if(isset($sent_groups)) {
                      $ug->createUsersGroups($id, $sent_groups);
                    }


                    if(isset($_FILES) && !empty($_FILES)){
                        $file = new FixometerFile;
                        $file->upload('profile', 'image', $id, env('TBL_USERS'), false, true);
                    }

                    if(!$u) {
                        $response['danger'] = 'Something went wrong. Please check the data and try again.';
                    }
                    else {
                        $response['success'] = 'User updated!';
                        if(FixometerHelper::hasRole($user, 'Host')){
                            header('Location: /host?action=ue&code=200');
                        }


                    }

                    $userdata = User::find($id);

                    $usergroups = array();
                    $ugroups = $User->getUserGroups($id);
                    foreach($ugroups as $g){
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
                }
                else {
                    $userdata = User::find($id);

                    $usergroups = array();
                    $ugroups = $User->getUserGroups($id);
                    foreach($ugroups as $g){
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
            foreach($ugroups as $g){
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

    public function forbidden(){
        $this->set('title',  'Nope.');
        return view('users.forbidden', [
          'title' => 'Nope.',
        ]);
    }


    public function profile($id = null){
        $Auth = new Auth($url);
        if(!$Auth->isLoggedIn()){
            header('Location: /user/login');
        }

        else {
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
            $this->set('groups',  $Groups->ofThisUser($id));

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

    public function logout() {
        unset($_SESSION[env('APP_NAME')][env('APP_KEY')]);//Was $_SESSION[APPNAME][SESSIONKEY]
        session_destroy();
        header('Location: /user/login');
    }

    public function delete(){
        $Auth = new Auth($url);
        if(!$Auth->isLoggedIn()){
            header('Location: /user/login');
        }

        else {
            $user = $Auth->getProfile();
            $this->set('user', $user);
            $this->set('header', true);

            // Administrators can edit users.
            if(FixometerHelper::hasRole($user, 'Administrator') || hasRole($user, 'Host')){
                if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
                    $id = (int)$_POST['id'];
                    $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);

                    // Delete Session
                    $session = new Session;
                    $session->destroySession($id);

                    if($this->User->delete($id)) {
                        header('Location: /user/all?msg=ok');
                    }
                    else {
                        header('Location: /user/all?msg=no');
                    }
                }
            }
        }
    }

    public function lng($lang){
      global $fixometer_languages;
      if(in_array($lang, array_keys($fixometer_languages))){
        $expire = time() + (60 * 60 * 24 * 365 * 10);
        setcookie(LANGUAGE_COOKIE, $lang, $time, '/', $_SERVER['HTTP_HOST']);
        header('Location: /user/login');
      }
    }
}
