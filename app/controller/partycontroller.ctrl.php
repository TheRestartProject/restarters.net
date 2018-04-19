<?php

    class PartyController extends Controller {

        protected $hostParties = array();
        protected $permissionsChecker;
        public $TotalWeight;
        public $TotalEmission;
        public $EmissionRatio;
       
        public function __construct($model, $controller, $action)
        {
            parent::__construct($model, $controller, $action);

            $Auth = new Auth($url);
            if(!$Auth->isLoggedIn() && $action != 'stats'){
                header('Location: /user/login');
            }

            $user = $Auth->getProfile();
            $this->user = $user;
            $this->set('user', $user);
            $this->set('header', true);

            if (hasRole($this->user, 'Host'))
            {
                $Group = new Group;
                $group = $Group->ofThisUser($this->user->id);
                $this->set('usergroup', $group[0]);
                $parties = $this->Party->ofThisGroup($group[0]->idgroups);

                foreach($parties as $party){
                    $this->hostParties[] = $party->idevents;
                }
                $User = new User;
                $this->set('profile', $User->profilePage($this->user->id));

                $Device = new Device;
                $weights = $Device->getWeights();

                $this->TotalWeight = $weights[0]->total_weights;
                $this->TotalEmission = $weights[0]->total_footprints;
                $this->EmissionRatio = $this->TotalEmission / $this->TotalWeight;
            }

            $this->permissionsChecker = new PermissionsChecker($this->user, $this->hostParties);
        }

        public function index()
        {
            $this->set('title', 'Parties');
            $this->set('list', $this->Party->findAll());
        }

        public function create()
        {
            if (!$this->permissionsChecker->userHasCreatePartyPermission()) {
                header('Location: /user/forbidden');
            }

            $Groups = new Group;

            $this->set('grouplist', $Groups->findList());

            $this->set('title', 'New Party');
            $this->set('gmaps', true);
            $this->set('js',
                        array('head' => array(
                                        '/ext/geocoder.js'
                        )));

            $this->set('group_list', $Groups->findAll());

            if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                $error = array();

                // Add SuperHero Restarter!
                $_POST['users'][] = 29;
                if(empty($_POST['volunteers'])) {
                    $volunteers = count($_POST['users']);
                }
                else {
                    $volunteers = $_POST['volunteers'];
                }

                // We got data! Elaborate.
                $event_date =       $_POST['event_date'];
                $start      =       $_POST['start'];
                $end        =       $_POST['end'];
                $pax        =       $_POST['pax'];
                $free_text  =       $_POST['free_text'];
                $venue      =       $_POST['venue'];
                $location   =       $_POST['location'];
                $latitude   =       $_POST['latitude'];
                $longitude  =       $_POST['longitude'];
                $group      =       $_POST['group'];


                // saving this for wordpress
                $wp_date = $event_date;

                // formatting dates for the DB
                $event_date = date('Y-m-d', strtotime(engDate($event_date)));

                if(!verify($event_date)){
                    $error['event_date'] = 'We must have a starting date and time.';
                }
                if(!verify($start)){
                    $error['name'] = 'We must have a starting date and time.';
                }
                if(!empty($latitude) || !empty($longitude)) {
                    // check that these values are floats.
                    $check_lat = filter_var($latitude, FILTER_VALIDATE_FLOAT);
                    $check_lon = filter_var($longitude, FILTER_VALIDATE_FLOAT);

                    if(!$check_lat || !$check_lon){
                        $error['location'] = 'Coordinates must be in the correct format.';
                    }

                }


                if(empty($error)) {

                    $startTime = date('Y-m-d', $event_date) . ' ' . $start;
                    $endTime = date('Y-m-d', $event_date) . ' ' . $end;

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
                                    'volunteers'    => $volunteers
                                    );
                    $idParty = $this->Party->create($data);



                    if($idParty){
                       
                        /** check and create User List **/
                        $_POST['users'][] = 29;
                        if(isset($_POST['users']) && !empty($_POST['users'])){
                            $users = $_POST['users'];
                            $this->Party->createUserList($idParty, $users);
                        }


                        /** let's create the image attachment! **/
                        if(isset($_FILES) && !empty($_FILES)){
                            $file = new File;
                            $file->upload('file', 'image', $idParty, TBL_EVENTS);
                        }

                        if(SYSTEM_STATUS != 'development') {
                            /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                            $Host = $Groups->findHost($group);

                            $custom_fields = array(
                                            array('key' => 'party_host',            'value' => $Host->hostname),
                                            array('key' => 'party_hostavatarurl',   'value' => UPLOADS_URL . 'mid_' .$Host->path),
                                            array('key' => 'party_grouphash',       'value' => $group),
                                            array('key' => 'party_venue',           'value' => $venue),
                                            array('key' => 'party_location',        'value' => $location),
                                            array('key' => 'party_time',            'value' => $start . ' - ' . $end),
                                            array('key' => 'party_date',            'value' => $event_date),
                                            array('key' => 'party_timestamp',       'value' => strtotime($event_date)),
                                            array('key' => 'party_timestamp_end',   'value' => strtotime($endTime)),
                                            array('key' => 'party_stats',           'value' => $idParty),
                                            array('key' => 'party_lat',             'value' => $latitude),
                                            array('key' => 'party_lon',             'value' => $longitude)

                                            );


                            /** Start WP XML-RPC **/
                            $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                            $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);


                            $content = array(
                                            'post_type' => 'party',
                                            'custom_fields' => $custom_fields
                                            );
                            $party_name = !empty($data['venue']) ? $data['venue'] : $data['location'];
                            $wpid = $wpClient->newPost($party_name, $free_text, $content);

                            $this->Party->update(array('wordpress_post_id' => $wpid), $idParty);
                        }

                        if(hasRole($this->user, 'Host')){
                         
                            $this->sendCreationNotificationEmail($venue, $location, $event_date, $start, $end, $group);
                            header('Location: /host?action=pc&code=200');
                              
                          }else if(hasRole($this->user, 'Administrator')){
                            header('Location: /admin?action=pc&code=200');
                          }
                     }
                    else {
                        $response['danger'] = 'Party could <strong>not</strong> be created. Something went wrong with the database.';
                    }

                }
                else {
                    $response['danger'] = 'Party could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
                }
                $this->set('response', $response);
                $this->set('error', $error);
                $this->set('udata', $_POST);
            }

        }

        public function sendCreationNotificationEmail($venue, $location, $event_date, $start, $end, $group_id){
            $Groups = new Group;

            $group = $Groups->findOne($group_id);
            $group_name = $group->name;

            $hostname = $this->user->name;

            // send email to Admin
            $message = "<p>Hi,</p>" .
            "<p>This is an automatic email to let you know that <strong>". $hostname . " </strong>has created a party on the <strong>" . APPNAME . "</strong>.</p>" .
            "<p><strong>Group Name:</strong> ". $group_name ." <p>" .
            "<p><strong>Party Name:</strong> ". $venue ." </p>" .
            "<p><strong>Party Location:</strong> " . $location ." </p>" .
            "<p><strong>Party Date:</strong> ". $event_date ." </p>" .
            "<p><strong>Party Start Time:</strong> ". $start ." </p>" .
            "<p><strong>Party End Time:</strong> ". $end ." </p>" ;

            $subject = APPNAME . ": Party created by the host : " . $hostname . " ";
            $headers = "From: " . APPEMAIL . "\r\n";
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
            $email= NOTIFICATION_EMAIL;
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
                $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);


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

        public function edit($id) {

            if (!$this->permissionsChecker->userHasEditPartyPermission($id)) {
                header('Location: /user/forbidden');
            }

            $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
            $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);

            $Groups = new Group;
            $File = new File;

            if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
                $id = $_POST['id'];
                $data = $_POST;
                unset($data['files']);
                unset($data['file']);
                unset($data['users']);
                unset($data['id']);

                // Add SuperHero Restarter!
                $_POST['users'][] = 29;
                if(empty($data['volunteers'])) {
                    $data['volunteers'] = count($_POST['users']);
                }

                // saving this for WP
                $wp_date = $data['event_date'];

                // formatting dates for the DB
                $data['event_date'] = dbDateNoTime($data['event_date']);
                $timestamp = strtotime($data['event_date']);

                $u = $this->Party->update($data, $id);

                if(!$u) {
                    $response['danger'] = 'Something went wrong. Please check the data and try again.';
                }
                else {
                    $response['success'] = 'Party updated!';

                    if(SYSTEM_STATUS != 'development') {
                        /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                        $theParty = $this->Party->findThis($id);
                        $Host = $Groups->findHost($data['group']);
                        $custom_fields = array(
                                        array('key' => 'party_host',            'value' => $Host->hostname),
                                        array('key' => 'party_hostavatarurl',   'value' => UPLOADS_URL . 'mid_' . $Host->path),
                                        array('key' => 'party_grouphash',       'value' => $data['group']),
                                        array('key' => 'party_venue',           'value' => $data['venue']),
                                        array('key' => 'party_location',        'value' => $data['location']),
                                        array('key' => 'party_time',            'value' => $data['start'] . ' - ' . $data['end']),
                                        array('key' => 'party_date',            'value' => $wp_date),
                                        array('key' => 'party_timestamp',       'value' => $theParty->event_timestamp),
                                        array('key' => 'party_timestamp_end',   'value' => $theParty->event_end_timestamp),
                                        array('key' => 'party_stats',           'value' => $id),
                                        array('key' => 'party_lat',             'value' => $data['latitude']),
                                        array('key' => 'party_lon',             'value' => $data['longitude'])
                                        );


                        $content = array(
                                        'post_type' => 'party',
                                        'post_title' => !empty($data['venue']) ? $data['venue'] : $data['location'],
                                        'post_content' => $data['free_text'],
                                        'custom_fields' => $custom_fields
                                        );


                        // Check for WP existence in DB
                        if(!empty($theParty->wordpress_post_id)){

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
                        else {
                            // Brand new post -> we send it up and update the Fixometer
                            $wpid = $wpClient->newPost($Host->groupname, $free_text, $content);
                            $this->Party->update(array('wordpress_post_id' => $wpid), $id);
                        }
                    }

                    if(isset($_POST['users']) && !empty($_POST['users'])){
                        $users = $_POST['users'];
                        $this->Party->createUserList($id, $users);
                    }


                    /** let's create the image attachment! **/
                    if(isset($_FILES) && !empty($_FILES)){
                        if(is_array($_FILES['file']['name'])) {
                            $files = rearrange($_FILES['file']);
                            foreach($files as $upload){
                                $File->upload($upload, 'image', $id, TBL_EVENTS);
                            }
                        }
                        else { }
                    }
                }
                if(hasRole($this->user, 'Host')){
                    header('Location: /host?action=pe&code=200');
                }
                $this->set('response', $response);
            }

            $images = $File->findImages(TBL_EVENTS, $id);

            $this->set('gmaps', true);
            $this->set('js', array( 'head' => array( '/ext/geocoder.js')));

            $Party = $this->Party->findOne($id);
            $this->set('images', $images);
            $this->set('title', 'Edit Party');
            $this->set('group_list', $Groups->findAll());
            $this->set('formdata', $Party);

            $remotePost = $wpClient->getPost($Party->wordpress_post_id);
            $this->set('remotePost', $remotePost);

            $this->set('grouplist', $Groups->findList());
        }


        public function manage($id){
            if( !hasRole($this->user, 'Host') && !hasRole($this->user, 'Administrator')){
                header('Location: /user/forbidden');
            }
            else {

                $this->set('js',
                            array('foot' => array(
                                            '/components/jquery.floatThead/dist/jquery.floatThead.min.js'
                            )));

                $Device     = new Device;
                $Category   = new Category;
                $User       = new User;
                $Group      = new Group;

                $this->set('grouplist', $Group->findList());

                if(isset($_POST) && !empty($_POST) && is_numeric($_POST['idparty']) && ($_POST['idparty'] > 0) ) {
                    $response = null;

                    $partydata = $_POST['party'];
                    $idparty = $_POST['idparty'];
                    $this->Party->update($partydata, $idparty);

                    if(isset($_POST['device'])){
                        $devices = $_POST['device'];

                        // Rearrange files to more friendly Array
                        if(isset($_FILES) && !empty($_FILES)){
                          $files = reflow($_FILES['device']);
                          $File = new File;
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
                                if (featureIsEnabled(FEATURE__DEVICE_PHOTOS)) {
                                  if($files[$i]['error'] == 0){
                                    $File->simpleUpload($files[$i], 'device', $iddevice, 'Device S/N Image');
                                  }
                                }
                            }

                            else {
                                //echo "creating---";
                                $device['category_creation'] = $device['category'];
                                $iddevice = $Device->create($device);
                                if (featureIsEnabled(FEATURE__DEVICE_PHOTOS)) {
                                  if($files[$i]['error'] == 0){
                                    $File->simpleUpload($files[$i], 'device', $iddevice, 'Device S/N Image');
                                  }
                                }
                            }

                            $response['success'] = 'Party info updated!';
                        }
                    }


                    if(SYSTEM_STATUS != 'development') {
                        /** WP Sync **/
                        $party = $this->Party->findThis($idparty, true);

                        $Groups = new Group;
                        $partygroup = $party->group;
                        $Host = $Groups->findHost($party->group);

                        $custom_fields = array(
                                            array('key' => 'party_host',            'value' => $Host->hostname),
                                            array('key' => 'party_hostavatarurl',   'value' => UPLOADS_URL . 'mid_' . $Host->path),
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
                        $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);



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
                            header('Location: /host/index/' . $partygroup);
                        }
                        else {
                            header('Location: /host');
                        }
                    }
                    else {
                        //echo "No.";

                    }
                    */
                  $this->set('response', $response);
                }


                $party      = $this->Party->findThis($id, true);
                $categories = $Category->listed();
                $restarters = $User->find(array('idroles' => 4));


                $party->co2 = 0;
                $party->ewaste = 0;
                $party->fixed_devices = 0;
                $party->repairable_devices = 0;
                $party->dead_devices = 0;


                if(!empty($party->devices)){
                    foreach($party->devices as $device){

                        if($device->repair_status == DEVICE_FIXED){
                            $party->co2     += (!empty($device->estimate) && $device->category==46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);
                            $party->ewaste  += (!empty($device->estimate) && $device->category==46 ? $device->estimate : $device->weight);
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

                $party->co2 = number_format(round($party->co2 * $Device->displacement), 0, '.' , ',');

                $this->set('party', $party);
                $this->set('devices', $party->devices);
                $this->set('categories', $categories);
                $this->set('restarters', $restarters);
            }
        }


        public function delete($id){
            if(hasRole($this->user, 'Administrator') || (hasRole($this->user, 'Host') && in_array($id, $this->hostParties))){
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
                        $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);

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

                if(hasRole($this->user, 'Host')){
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


        public function stats($id, $class = null){
            $Device = new Device;

            $this->set('framed', true);
            $party = $this->Party->findThis($id, true);

            if($party->device_count == 0){
                $need_attention++;
            }

            $party->co2 = 0;
            $party->fixed_devices = 0;
            $party->repairable_devices = 0;
            $party->dead_devices = 0;
            $party->ewaste = 0;

            foreach($party->devices as $device){

                if($device->repair_status == DEVICE_FIXED){
                    $party->co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);
                    $party->ewaste += (!empty($device->estimate) && $device->category == 46  ? $device->estimate : $device->weight);
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
            $this->set('party', $party);
            if(!is_null($class)) {
                $this->set('class', 'wide');
            }

        }

        public function deleteimage(){
            if(is_null($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])){
                return false;
            }

            else {
                $id = $_GET['id'];
                $path = $_GET['path'];
                $Image = new File;

                $Image->deleteImage($id, $path);



                echo json_encode(array('hey' => 'Deleting stuff here!'));


            }
        }

    }
