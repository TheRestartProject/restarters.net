<?php

    class UserController extends Controller {


        public function login(){

            $Auth = new Auth($url);
            if($Auth->isLoggedIn()){

                $user = $Auth->getProfile();

                if(hasRole($user, 'Administrator')){
                    header('Location: /admin');
                }
                elseif(hasRole($user, 'Host')){
                    header('Location: /host');
                }
                else {
                    header('Location: /user/forbidden');
                }

            }
            else {


                $this->set('title', 'Login');
                $this->set('charts', true);

                // set up stuff for engagin login page
                $Device = new Device;
                $Party = new Party;

                $weights= $Device->getWeights();
                $devices= $Device->statusCount();

                $this->set('weights', $weights);
                $this->set('devices', $devices);

                $this->set('nextparties', $Party->findNextParties());
                $this->set('allparties', $Party->findAll());

                $co2_years = $Device->countCO2ByYear();
                $this->set('year_data', $co2_years);
                $stats = array();
                foreach($co2_years as $year){
                    $stats[$year->year] = $year->co2;
                }
                $this->set('bar_chart_stats', array_reverse($stats, true));

                $waste_years = $Device->countWasteByYear();
                $this->set('waste_year_data', $waste_years);
                $wstats = array();
                foreach($waste_years as $year){
                    $wstats[$year->year] = $year->waste;
                }
                $this->set('waste_bar_chart_stats', array_reverse($wstats, true));

                if($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET)){
                    $reset_result = $_GET['reset'];

                    if (!empty($reset_result) && $reset_result == 'ok') {
                        $response['success'] = '<strong>Password reset successfully</strong>. You can now login with your new password.';

                        $this->set('response', $response);
                    }
                }

                if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){

                    $response = array();

                    $uput_email = $_POST['email'];
                    $uput_password = $_POST['password'];

                    if(empty($uput_email) || !filter_var($uput_email, FILTER_VALIDATE_EMAIL)){
                        $response['danger'] = '<strong>Invalid/Empty email</strong>. Please input a valid email address.';
                    }
                    if(empty($uput_password)){
                        $response['danger'] = '<strong>Empty Password</strong>. Please input a password.';
                    }

                    if(!isset($response['danger'])){
                        // No errors, we can proceed and see if we can auth this guy here.

                        $user = $this->User->find(array(
                                                        'email' => $uput_email,
                                                        'password' => crypt($uput_password, '$1$' . SECRET)
                                                    )
                                                );
                                                

                        if(!empty($user)){
                            $Auth = new Auth;
                            if(!$Auth->isLoggedIn()){

                                $pass = $Auth->authorize($user[0]->idusers);

                            }
                            else {
                                $pass = true;
                            }

                            if($pass == true){
                              // fetch lang preference
                              $lang = (isset($_COOKIE[LANGUAGE_COOKIE]) ? $_COOKIE[LANGUAGE_COOKIE] : 'en');
                              $this->User->update(array( 'language' => $lang ), $user[0]->idusers);


                                if(hasRole($user[0], 'Administrator')){
                                    header('Location: /admin');
                                }
                                elseif(hasRole($user[0], 'Host')){
                                    header('Location: /host');
                                }
                                else {
                                    header('Location: /user/forbidden');
                                }

                            }
                        }
                        else {
                            $response['danger'] = 'No user account was found for the email and password provided. Please check your details and try again.';
                            $this->set('response', $response);
                            //header('Location: /user/login');
                        }
                    }
                    else {
                        $this->set('response', $response);
                    }

                }
            }
        }

        public function recover(){

               //To display Stats
                $Device = new Device;
                $Party = new Party;

                $weights= $Device->getWeights();
                $devices= $Device->statusCount();

                $this->set('weights', $weights);
                $this->set('devices', $devices);

                $this->set('nextparties', $Party->findNextParties());
                $this->set('allparties', $Party->findAll());

                $co2_years = $Device->countCO2ByYear();
                $this->set('year_data', $co2_years);
                $stats = array();
                foreach($co2_years as $year){
                    $stats[$year->year] = $year->co2;
                }
                $this->set('bar_chart_stats', array_reverse($stats, true));

                $waste_years = $Device->countWasteByYear();
                $this->set('waste_year_data', $waste_years);
                $wstats = array();
                foreach($waste_years as $year){
                    $wstats[$year->year] = $year->waste;
                }
                $this->set('waste_bar_chart_stats', array_reverse($wstats, true));

          //Account recovery
          $this->set('title', 'Account recovery');

          if(strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && isset($_POST['email']) && !empty($_POST['email'])){
            $email = $_POST['email'];
            if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
                $response['danger'] = 'Please input a <strong>valid</strong> email.';
            } else {
              $user = $this->User->find(array('email' => $email));
              if(!empty($user)){
                $id = $user[0]->idusers;
                $data = array();
                // generate recovery code
                $bytes = 32;
                $data['recovery'] = substr( bin2hex(openssl_random_pseudo_bytes($bytes)), 0, 24 );
                // add date timestamp
                $data['recovery_expires'] = strftime( '%Y-%m-%d %X', time() + (24 * 60 * 60));

                // update record
                $this->User->update($data, $id);

                // send email to User
                $message = "<p>Hi,</p>" .
                         "<p>You've requested to recover your password for the " . APPNAME . ".</p>" .
                         "<hr/>" .
                         "<p>Please click on this link to recover your password: <a href=\"" . BASE_URL . "/user/reset/?recovery=" . $data['recovery'] . "\">" . BASE_URL . "/user/reset/?recovery=" . $data['recovery'] . "</a>.</p>" .
                         "<p>If the link doesn't work, please copy and paste it in the address bar of your browser.</p>" .
                         "<p>The link will be active for the next 24 hours.</p>" .
                         "<hr/>" .
                         "<p>If you have any issues, or if you did <strong>not</strong> ask to recover your password, please contact <a href='mailto:" . SUPPORT_CONTACT_EMAIL . "'>" . SUPPORT_CONTACT_EMAIL . "</a>.</p>" .
                "<p>Thanks for using the " . APPNAME . "!</p>" .
                "<p><em>The Restart Project</em></p>";
                $subject = APPNAME . ": Password recovery";
                $headers = "From: " . APPEMAIL . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                $headers .= "Bcc: " . SUPPORT_CONTACT_EMAIL . "\r\n";

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
            $this->set('response', $response);
          }
        }

        public function reset(){

               //To display Stats
                $Device = new Device;
                $Party = new Party;

                $weights= $Device->getWeights();
                $devices= $Device->statusCount();

                $this->set('weights', $weights);
                $this->set('devices', $devices);

                $this->set('nextparties', $Party->findNextParties());
                $this->set('allparties', $Party->findAll());

                $co2_years = $Device->countCO2ByYear();
                $this->set('year_data', $co2_years);
                $stats = array();
                foreach($co2_years as $year){
                    $stats[$year->year] = $year->co2;
                }
                $this->set('bar_chart_stats', array_reverse($stats, true));

                $waste_years = $Device->countWasteByYear();
                $this->set('waste_year_data', $waste_years);
                $wstats = array();
                foreach($waste_years as $year){
                    $wstats[$year->year] = $year->waste;
                }
                $this->set('waste_bar_chart_stats', array_reverse($wstats, true));

          //account recovery
          $this->set('title', 'Account recovery');

          if( !isset($_GET['recovery']) || empty($_GET['recovery']) ){
            $valid_code = false;
          } else {
            $recovery = filter_var($_GET['recovery'], FILTER_SANITIZE_STRING);
            $user = $this->User->find(array('recovery' => $recovery));

            if( strtotime($user[0]->recovery_expires) > time() ) {
              $valid_code = true;
              $this->set('recovery', $recovery);
            }
          }
          $this->set('valid_code', $valid_code);

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
              $user = $this->User->find(array('recovery' => $recovery));
              if(!empty($user)){
                $data = array(
                  'password' => crypt($pwd, '$1$'.SECRET)
                );
                $update = $this->User->update($data, $user[0]->idusers);
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
          $this->set('response', $response);
        }




        public function all() {
            $this->set('title', 'Users');

            $Auth = new Auth($url);
            if(!$Auth->isLoggedIn()){
                header('Location: /user/login');
            }
            else {
                $user = $Auth->getProfile();
                $this->set('user', $user);
                $this->set('header', true);
                if(hasRole($user, 'Administrator')){
                    $userlist = $this->User->getUserList();
                    $this->set('userlist', $userlist);
                }
                else {
                    header('Location: /user/forbidden');
                }
            }
        }

        public function create() {
            $this->set('title', 'New User');

            $Auth = new Auth($url);
            if(!$Auth->isLoggedIn()){
                header('Location: /user/login');
            }
            else {
                $user = $Auth->getProfile();
                $this->set('user', $user);
                $this->set('header', true);

                // Administrators can add users.
                if(hasRole($user, 'Administrator')){

                    $Roles = new Role;
                    $Roles =$Roles->findAll();

                    $Groups = new Group;
                    $Groups = $Groups->findAll();

                    $this->set('roles', $Roles);
                    $this->set('groups', $Groups);

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
                        if(!$this->User->checkEmail($email)){
                            $error['email'] = 'This email is already in use in our database. Please use another one.';
                        }

                        if(empty($error)) {
                            // random password
                            $pwd = bin2hex(openssl_random_pseudo_bytes(8));

                            // No errors. We can proceed and create the User.
                            $data = array(  'name'     => $name,
                                            'email'    => $email,
                                            'password' => crypt($pwd, '$1$'.SECRET),
                                            'role'     => $role,
                                            //'group'    => $group
                                        );

                            // add password recovery data
                            $bytes = 32;
                            $data['recovery'] = substr( bin2hex(openssl_random_pseudo_bytes($bytes)), 0, 24 );
                            // add date timestamp
                            $data['recovery_expires'] = strftime( '%Y-%m-%d %X', time() + (24 * 60 * 60));


                            $idUser = $this->User->create($data);
                            if($idUser){

                                if(isset($groups) && !empty($groups)){
                                    $Usersgroups = new Usersgroups;
                                    $Usersgroups->createUsersGroups($idUser, $groups);
                                }

                                $Session = new Session;
                                $Session->createSession($idUser);

                                if(isset($_FILES) && !empty($_FILES)){
                                    $file = new File;
                                    $file->upload('profile', 'image', $idUser, TBL_USERS, false, true);
                                }

                            }
                            if($idUser){
                              //Send out email

                              // send email to User
                              $message = "<p>Hi,</p>" .
                                       "<p>This is an automatic email to let you know that we have just created an account for you on the <strong>" . APPNAME . "</strong>.</p>" .
                                       "<p>Please click on this link to set your password: <a href=\"" . BASE_URL . "/user/reset/?recovery=" . $data['recovery'] . "\">" . BASE_URL . "/user/reset/?recovery=" . $data['recovery'] . "</a>.</p>" .
                                       "<p>If the link doesn't work, please copy and paste it in the address bar of your browser.</p>" .
                                       "<p>The link will be active for the next 24 hours.</p>" .
                              "<p>If you have any issues, please contact <a href='mailto:" . SUPPORT_CONTACT_EMAIL . "'>" . SUPPORT_CONTACT_EMAIL . "</a>.</p>" .
                                       "<p>Thanks for using the " . APPNAME . "!</p>" .
                                       "<p><em>The Restart Project</em></p>";
                              $subject = APPNAME . ": Account created - please set your password";
                              $headers = "From: " . APPEMAIL . "\r\n";
                              $headers .= "MIME-Version: 1.0\r\n";
                              $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                              $headers .= "Bcc: " . SUPPORT_CONTACT_EMAIL . "\r\n";

                              $sender = mail($email, $subject, $message, $headers);

                              $response['success'] = 'User created correctly.  <strong>An email has been sent to the user to ask them to set their password.</strong>';
                            }
                            else {
                                $response['danger'] = 'User could not be created';
                            }
                        }
                        else {
                            $response['danger'] = 'User could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';

                        }

                        $this->set('response', $response);
                        $this->set('error', $error);
                        $this->set('originalData', $data);
                    }

                }
                else {
                    header('Location: /user/forbidden');
                }
            }

        }


        public function edit($id){
            $this->set('title', 'Edit User');

            global $fixometer_languages;
            $this->set('langs', $fixometer_languages);

            $Auth = new Auth($url);
            if(!$Auth->isLoggedIn()){
                header('Location: /user/login');
            }

            else {
                $user = $Auth->getProfile();
                $this->set('user', $user);
                $this->set('header', true);



                // Administrators can edit users.
                if(hasRole($user, 'Administrator') || hasRole($user, 'Host')){

                    if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
                        $data = $_POST;
                        $id = $_POST['id'];

                        $sent_groups = $data['groups'];


                        $error = false;
                        // check for email in use
                        $editingUser = $this->User->findOne($id);
                        if($editingUser->email !== $data['email']){
                            if(!$this->User->checkEmail($data['email'])){
                                $error['email'] = 'The email you entered is already in use in our database. Please use another one.';
                            }
                        }


                        if(!empty($data['new-password'])){
                            if($data['new-password'] !== $data['password-confirm']){
                                $error['password'] = 'The passwords are not identical!';
                            }
                            else {
                                $data['password'] = crypt($data['new-password'], '$1$'.SECRET);
                            }
                        }

                        unset($data['new-password']);
                        unset($data['password-confirm']);

                        unset($data['groups']);
                        unset($data['profile']);
                        unset($data['id']);


                        if(!is_array($error)){
                            $u = $this->User->update($data, $id);

                            $expire = time() + (60 * 60 * 24 * 365 * 10);
                            setcookie(LANGUAGE_COOKIE, $data['language'], $time, '/', $_SERVER['HTTP_HOST']);

                            $ug = new Usersgroups;
                            $ug->createUsersGroups($id, $sent_groups);



                            if(isset($_FILES) && !empty($_FILES)){
                                $file = new File;
                                $file->upload('profile', 'image', $id, TBL_USERS, false, true);
                            }

                            if(!$u) {
                                $response['danger'] = 'Something went wrong. Please check the data and try again.';
                            }
                            else {
                                $response['success'] = 'User updated!';
                                if(hasRole($user, 'Host')){
                                    header('Location: /host?action=ue&code=200');
                                }


                            }
                            $this->set('response', $response);
                        }
                        else {
                            $this->set('error', $error);
                        }


                    }

                    $Roles = new Role;
                    $Roles =$Roles->findAll();

                    $Groups = new Group;
                    $Groups = $Groups->findAll();

                    $this->set('roles', $Roles);
                    $this->set('groups', $Groups);

                    $userdata = $this->User->findOne($id);

                    $usergroups = array();
                    $ugroups = $this->User->getUserGroups($id);
                    foreach($ugroups as $g){
                        $usergroups[] = $g->group;
                    }

                    $userdata->groups = $usergroups;
                    $this->set('data', $userdata);


                }
            }



        }

        public function forbidden(){
            $this->set('title',  'Nope.');
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

            }
        }

        public function logout() {
            unset($_SESSION[APPNAME][SESSIONKEY]);
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
                if(hasRole($user, 'Administrator') || hasRole($user, 'Host')){
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
