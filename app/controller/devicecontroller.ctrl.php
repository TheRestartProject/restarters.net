<?php

    class DeviceController extends Controller {

        public function __construct($model, $controller, $action){
            parent::__construct($model, $controller, $action);

            $Auth = new Auth($url);
            if(!$Auth->isLoggedIn()){
                header('Location: /user/login');
            }
            else {

                $user = $Auth->getProfile();
                $this->user = $user;
                $this->set('user', $user);
                $this->set('header', true);

                if(hasRole($this->user, 'Host')){
                    $Group = new Group;
                    $Party = new Party;
                    $group = $Group->ofThisUser($this->user->id);
                    $this->set('usergroup', $group[0]);
                    $parties = $Party->ofThisGroup($group[0]->idgroups);

                    foreach($parties as $party){
                        $this->hostParties[] = $party->idevents;
                    }
                    $User = new User;
                    $this->set('profile', $User->profilePage($this->user->id));
                }
            }
        }

        public function index(){

            $this->set('title', 'Devices');
            $this->set('css', array('/components/jquery.bootgrid/dist/jquery.bootgrid.min.css'));
            $this->set('js', array('head' => array(
              '/components/jquery.bootgrid/dist/jquery.bootgrid.js',
              '/components/jquery.bootgrid/dist/jquery.bootgrid.fa.js',
              '/dist/js/device_list.js'
            )));

            $Category   = new Category;
            $Group      = new Group;

            $categories = $Category->listed();
            $this->set('categories', $categories);
            $this->set('groups', $Group->findAll());

            if(isset($_GET['fltr']) && !empty($_GET['fltr'])){

              // Get params and clean them up
              // DATES...
              if(isset($_GET['from-date']) && !empty($_GET['from-date'])){
                if (!DateTime::createFromFormat('d/m/Y', $_GET['from-date'])) {
                  $response['danger'] = 'Invalid "from date"';
                  $fromTimeStamp = null;
                }
                else {
                  $fromDate = DateTime::createFromFormat('d/m/Y', $_GET['from-date']);
                  $fromTimeStamp = strtotime($fromDate->format('Y-m-d'));
                }
              }
              else{
                $fromTimeStamp = 1;
              }

              if(isset($_GET['to-date']) && !empty($_GET['to-date'])){
                if (!DateTime::createFromFormat('d/m/Y', $_GET['to-date'])) {
                  $response['danger'] = 'Invalid "to date"';
                }
                else {
                  $toDate = DateTime::createFromFormat('d/m/Y', $_GET['to-date']);
                  $toTimeStamp = strtotime($toDate->format('Y-m-d'));
                }
              }
              else {
                $toTimeStamp = time();
              }

              $params = array(
                'brand'       => filter_var($_GET['brand'], FILTER_SANITIZE_STRING),
                'model'       => filter_var($_GET['model'], FILTER_SANITIZE_STRING),
                'problem'     => filter_var($_GET['free-text'], FILTER_SANITIZE_STRING),

                'category'    => isset($_GET['categories']) ? implode(', ', filter_var_array($_GET['categories'], FILTER_SANITIZE_NUMBER_INT)) : null,
                'group'       => isset($_GET['groups']) ? implode(', ', filter_var_array($_GET['groups'], FILTER_SANITIZE_NUMBER_INT)) : null,

                'event_date'  => array($fromTimeStamp,  $toTimeStamp)

              );


              $list = $this->Device->getList($params);

            } else {
              $list = $this->Device->getList();
            }

            $this->set('list', $list);

        }

        public function edit($id){
            $this->set('title', 'Edit Device');
            if(hasRole($this->user, 'Administrator') || hasRole($this->user, 'Host') ){


                if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && filter_var($id, FILTER_VALIDATE_INT)){

                    $data = $_POST;
                    // remove the extra "files" field that Summernote generates -
                    unset($data['files']);
                    unset($data['users']);

                    // formatting dates for the DB
                    //$data['event_date'] = dbDateNoTime($data['event_date']);


                    $u = $this->Device->update($data, $id);

                    if(!$u) {
                        $response['danger'] = 'Something went wrong. Please check the data and try again.';
                    }
                    else {
                        $response['success'] = 'Device updated!';


                        /** let's create the image attachment! **/
                        if(isset($_FILES) && !empty($_FILES)){
                            $file = new File;
                            $file->upload('file', 'image', $id, TBL_EVENTS);
                        }

                    }

                    $this->set('response', $response);
                }
                $Events = New Party;
                $Categories = New Category;

                $UserEvents = $Events->findAll();


                $this->set('categories', $Categories->findAll());
                $this->set('events', $UserEvents);

                $Device = $this->Device->findOne($id);
                $this->set('title', 'Edit Device');
                $this->set('formdata', $Device);

            }
            else {
                header('Location: /user/forbidden');
            }
        }


        public function ajax_update($id){
            $this->set('title', 'Edit Device');
            if(hasRole($this->user, 'Administrator') || hasRole($this->user, 'Host') ){
                $Categories = new Category;
                $Device = $this->Device->findOne($id);

                $this->set('title', 'Edit Device');
                $this->set('categories', $Categories->listed());
                $this->set('formdata', $Device);

            }
            else {
                header('Location: /user/forbidden');
            }
        }

        public function ajax_update_save($id){
          if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && filter_var($id, FILTER_VALIDATE_INT)){

              $data = $_POST;
              $u = $this->Device->update($data, $id);

              if(!$u) {
                  $response['response_type'] = 'danger';
                  $response['message'] = 'Something went wrong. Please check the data and try again.';

              }
              else {
                  $response['response_type'] = 'success';
                  $response['message'] = 'Device updated!';
                  $response['data'] = $data;
                  $response['id'] = $id;
              }

              echo json_encode($response);
          }
        }




        public function create(){
            if(hasRole($user, 'Guest')){
                header('Location: /user/forbidden');

            }
            else {
                $Events = New Party;
                $Categories = New Category;

                $UserEvents = $Events->ofThisUser($this->user->id);


                $this->set('categories', $Categories->findAll());
                $this->set('events', $UserEvents);

                if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                    $error = array();
                    $data = array_filter($_POST);

                    if(!verify($data['event'])){ $error['event'] = 'Please select a Restart party.'; }
                    if(!verify($data['category'])){ $error['category'] = 'Please select a category for this device'; }
                    if(!verify($data['repair_status'])){ $error['repair_status'] = 'Please select a repair status.'; }

                    if(!empty($error)){
                        $this->set('error', $error);
                        $response['danger'] = 'The device repair has <strong>not</strong> been saved.';
                    }
                    else {
                        // add user id
                        $data['repaired_by'] = $this->user->id;
                        // add initial category (for backlogging upon revision)
                        $data['category_creation'] = $data['category'];

                        // save this!
                        $insert = $this->Device->create($data);
                        if(!$insert){
                            $response['danger'] = 'Error while saving the device tot he DB.';
                        }
                        else {
                            $response['success'] = 'Device saved!';
                        }

                    }

                    $this->set('response', $response);
                    $this->set('udata', $data);
                }

                $this->set('title', 'New Device');
            }


        }



        public function delete($id){
            if(hasRole($this->user, 'Administrator') || (hasRole($this->user, 'Host')) ){
                // get device party
                $curr = $this->Device->findOne($id);
                $party = $curr->event;
                // echo $party; //die();

                $r = $this->Device->delete($id);
                if(!$r){
                    $response = 'd:err';
                }
                else {
                    $response = 'd:ok';
                }

                header('Location: /party/manage/' . $party . '/' . $response);

            }
            else {
                header('Location: /user/forbidden');
            }
        }
    }
