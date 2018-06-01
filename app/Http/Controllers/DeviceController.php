<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\Device;
use App\Category;
use App\Group;
use App\Party;

use Auth;
use FixometerHelper;
use FixometerFile;

class DeviceController extends Controller
{
  // public function __construct($model, $controller, $action){
  //     parent::__construct($model, $controller, $action);
  //
  //     $Auth = new Auth($url);
  //     if(!$Auth->isLoggedIn()){
  //         header('Location: /user/login');
  //     }
  //     else {
  //
  //         $user = $Auth->getProfile();
  //         $this->user = $user;
  //         $this->set('user', $user);
  //         $this->set('header', true);
  //
  //         if(FixometerHelper::hasRole($this->user, 'Host')){
  //             $Group = new Group;
  //             $Party = new Party;
  //             $group = $Group->ofThisUser($this->user->id);
  //             $this->set('usergroup', $group[0]);
  //             $parties = $Party->ofThisGroup($group[0]->idgroups);
  //
  //             foreach($parties as $party){
  //                 $this->hostParties[] = $party->idevents;
  //             }
  //             $User = new User;
  //             $this->set('profile', $User->profilePage($this->user->id));
  //
  //             return view('device.index', [
  //               'user' => $user,
  //               'header' => true,
  //               'usergroup' => $group[0],
  //               'profile' => $User->profilePage($this->user->id),
  //             ]);
  //         }
  //     }
  // }

  public function index($search = null){

      $Category   = new Category;
      $Group      = new Group;
      $Device     = new Device;

      $categories = $Category->listed();

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

          'category'    => isset($_GET['categories']) ? filter_var($_GET['categories'], FILTER_SANITIZE_STRING) : null,//isset($_GET['categories']) ? implode(', ', filter_var_array($_GET['categories'], FILTER_SANITIZE_NUMBER_INT)) : null,
          'group'       => isset($_GET['groups']) ? filter_var($_GET['groups'], FILTER_SANITIZE_STRING) : null,//isset($_GET['groups']) ? implode(', ', filter_var_array($_GET['groups'], FILTER_SANITIZE_NUMBER_INT)) : null,

          'event_date'  => array($fromTimeStamp,  $toTimeStamp)

        );


        $list = $Device->getList($params);

      } else {
        $list = $Device->getList();
      }

      // $this->set('list', $list);

      return view('device.index', [
        'title' => 'Devices',
        'categories' => $categories,
        'groups' => $Group->findAll(),
        'list' => $list,
      ]);

  }

  public function edit($id){
      // $this->set('title', 'Edit Device');
      $user = Auth::user();
      if(FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::hasRole($user, 'Host') ){

          $Device = new Device;

          if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && filter_var($id, FILTER_VALIDATE_INT)){

              $data = $_POST;
              // remove the extra "files" field that Summernote generates -
              unset($data['files']);
              unset($data['users']);

              // formatting dates for the DB
              //$data['event_date'] = dbDateNoTime($data['event_date']);

              $update = array(
                              'event'             => $data['event'],
                              'category'          => $data['category'],
                              'category_creation' => $data['category'],
                              'repair_status'     => $data['repair_status'],
                              'spare_parts'       => $data['spare_parts'],
                              'brand'             => $data['brand'],
                              'model'             => $data['model'],
                              'problem'           => $data['problem'],
                              );

              $u = $Device->where('iddevices', $id)->update($update);

              if(!$u) {
                  $response['danger'] = 'Something went wrong. Please check the data and try again.';
              }
              else {
                  $response['success'] = 'Device updated!';


                  /** let's create the image attachment! **/
                  if(isset($_FILES) && !empty($_FILES)){
                      $file = new FixometerFile;
                      $file->upload('file', 'image', $id, TBL_EVENTS);
                  }

              }

              // $this->set('response', $response);
          }
          $Events = New Party;
          $Categories = New Category;

          $UserEvents = $Events->findAll();


          // $this->set('categories', $Categories->findAll());
          // $this->set('events', $UserEvents);

          $device = $Device->findOne($id);
          // $this->set('title', 'Edit Device');
          // $this->set('formdata', $Device);

          if (!isset($response)) {
            $response = null;
          }

          return view('device.edit', [
            'title' => 'Edit Device',
            'response' => $response,
            'categories' => $Categories->findAll(),
            'events' => $UserEvents,
            'formdata' => $device,
          ]);

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

          return view('device.edit', [
            'title' => 'Edit Device',
            'categories' => $Categories->findAll(),
            'formdata' => $Device,
          ]);

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
      $user = Auth::user();

      if(FixometerHelper::hasRole($user, 'Guest')){
          header('Location: /user/forbidden');

      }
      else {
          $Events = New Party;
          $Categories = New Category;

          $UserEvents = $Events->ofThisUser($user->id);

          if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
              $error = array();
              $data = array_filter($_POST);
              $Device = new Device;

              if(!FixometerHelper::verify($data['event'])){ $error['event'] = 'Please select a Restart party.'; }
              if(!FixometerHelper::verify($data['category'])){ $error['category'] = 'Please select a category for this device'; }
              if(!FixometerHelper::verify($data['repair_status'])){ $error['repair_status'] = 'Please select a repair status.'; }

              if(!empty($error)){
                  $response['danger'] = 'The device repair has <strong>not</strong> been saved.';
              }
              else {
                  // add user id
                  $data['repaired_by'] = $user->id;
                  // add initial category (for backlogging upon revision)
                  $data['category_creation'] = $data['category'];

                  $insert = array(
                                  'event'             => $data['event'],
                                  'category'          => $data['category'],
                                  'category_creation' => $data['category'],
                                  'repair_status'     => $data['repair_status'],
                                  'spare_parts'       => $data['spare_parts'],
                                  'brand'             => $data['brand'],
                                  'model'             => $data['model'],
                                  'problem'           => $data['problem'],
                                  'repaired_by'       => $data['repaired_by'],
                                  );

                  // save this!
                  $insert = $Device->create($insert);
                  if(!$insert){
                      $response['danger'] = 'Error while saving the device tot he DB.';
                  }
                  else {
                      $response['success'] = 'Device saved!';
                  }

              }
          }

          if (!isset($error)) {
            $error = null;
          }

          if (!isset($response)) {
            $response = null;
          }

          if (!isset($data)) {
            $data = null;
          }

          return view('device.create', [
            'title' => 'New Device',
            'categories' => $Categories->findAll(),
            'events' => $UserEvents,
            'response' => $response,
            'udata' => $data,
            'error' => $error,
          ]);

      }


  }



  public function delete($id){
      $Device = new Device;
      $user = Auth::user();

      if(FixometerHelper::hasRole($user, 'Administrator') || (FixometerHelper::hasRole($user, 'Host')) ){
          // get device party
          $curr = $Device->findOne($id);
          $party = $curr->event;
          // echo $party; //die();

          $r = $Device->delete($id);

          return redirect('/party/manage/'.$party);

      }
      else {
          return redirect('/party/manage/'.$party);
      }
  }

    // public function test() {
    //   $g = new Device;
    //   dd($g->export());
    // }
}
