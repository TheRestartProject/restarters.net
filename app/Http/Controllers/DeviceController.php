<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Brands;
use App\Category;
use App\Cluster;
use App\Device;
use App\EventsUsers;
use App\Group;
use App\Party;
use App\User;
use App\DeviceList;
use Auth;
use FixometerHelper;
use FixometerFile;
use Illuminate\Support\Facades\Validator;
use View;

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
      //Use Eloquent for pagination NB: Will break search
      $list = DeviceList::orderBy('sorter', 'DSC')->paginate(25);

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
      if(FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::userHasEditPartyPermission($party, $user->id) ){

          $Device = new Device;

          if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && filter_var($id, FILTER_VALIDATE_INT)){

              $data = $_POST;
              // remove the extra "files" field that Summernote generates -
              unset($data['files']);
              unset($data['users']);

              // formatting dates for the DB
              //$data['event_date'] = dbDateNoTime($data['event_date']);

              $update = array(
                              // 'event'             => $data['event'],
                              'category'          => $data['category'],
                              'category_creation' => $data['category'],
                              'repair_status'     => $data['repair_status'],
                              'spare_parts'       => $data['spare_parts'],
                              'brand'             => $data['brand'],
                              'model'             => $data['model'],
                              'problem'           => $data['problem'],
                              'age'               => $data['age'],
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
                      $file->upload('devicePhoto', 'image', $id, env('TBL_EVENTS'));
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

      if( FixometerHelper::hasRole($user, 'Restarter') ){
          header('Location: /user/forbidden');
      } else {
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
                      $response['danger'] = 'Error while saving the device to the DB.';
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

  public function ajaxCreate(Request $request) {

    $rules = [
        'category' => 'required|filled',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 200);
    }

    $category       = $request->input('category');
    $weight         = $request->input('weight');
    $brand          = $request->input('brand');
    $model          = $request->input('model');
    $age            = $request->input('age');
    $problem        = $request->input('problem');
    $repair_status  = $request->input('repair_status');
    $repair_details = $request->input('repair_details');
    $spare_parts    = $request->input('spare_parts');
    $quantity       = $request->input('quantity');
    $event_id       = $request->input('event_id');

    // add quantity loop
    for ($i=0; $i < $quantity; $i++) {

      $device[$i] = new Device;
      $device[$i]->category = $category;
      $device[$i]->category_creation = $category;
      $device[$i]->estimate = $weight;
      $device[$i]->brand = $brand;
      $device[$i]->model = $model;
      $device[$i]->age = $age;
      $device[$i]->problem = $problem;
      $device[$i]->repair_status = $repair_status;

      if( $repair_details == 1 ){
        $device[$i]->more_time_needed = 1;
      } else {
        $device[$i]->more_time_needed = 0;
      }

      if( $repair_details == 2 ){
        $device[$i]->professional_help = 1;
      } else {
        $device[$i]->professional_help = 0;
      }

      if( $repair_details == 3 ){
        $device[$i]->do_it_yourself = 1;
      } else {
        $device[$i]->do_it_yourself = 0;
      }

      $device[$i]->spare_parts = $spare_parts;
      $device[$i]->event = $event_id;
      $device[$i]->repaired_by = Auth::id();
      $device[$i]->save();

    }
    // end quantity loop

    $brands = Brands::all();
    $clusters = Cluster::all();
    $is_attending = EventsUsers::where('event', $event_id)->where('user', Auth::user()->id)->first();

    //Change to handle loop
    foreach ($device as $d) {

      $views[] = View::make('partials.tables.row-device', [
        'device' => $d,
        'clusters' => $clusters,
        'brands' => $brands,
        'is_attending' => $is_attending,
      ])->render();

    }
    //end of handle loop

    $event = Party::find($event_id);

    $Device = new Device;
    $weights = $Device->getWeights();

    $TotalWeight = $weights[0]->total_weights;
    $TotalEmission = $weights[0]->total_footprints;
    $EmissionRatio = $TotalEmission / $TotalWeight;
    $stats = $event->getEventStats($EmissionRatio);

    $return['html'] = $views;
    $return['success'] = true;
    $return['stats'] = $stats;

    return response()->json($return);

    //$brand_name = Brands::find($brand)->brand_name;

    // $data = [];
    //
    // if ($post_data['repair_status'] == 2) {
    //   switch ($post_data['repair_details']) {
    //     case 1:
    //         Device::create([
    //           'event' => $request->input('event_id'),
    //           'category' => $post_data['category'],
    //           'category_creation' => $post_data['category'],
    //           'brand' => $brand,
    //           'model' => $post_data['model'],
    //           'age' => $post_data['age'],
    //           'problem' => $post_data['problem'],
    //           'spare_parts' => $post_data['spare_parts'],
    //           'repair_status' => $post_data['repair_status'],
    //           'repaired_by' => Auth::id(),
    //           'more_time_needed' => 1,
    //         ]);
    //         break;
    //     case 2:
    //         Device::create([
    //           'event' => $request->input('event_id'),
    //           'category' => $post_data['category'],
    //           'category_creation' => $post_data['category'],
    //           'brand' => $brand,
    //           'model' => $post_data['model'],
    //           'age' => $post_data['age'],
    //           'problem' => $post_data['problem'],
    //           'spare_parts' => $post_data['spare_parts'],
    //           'repair_status' => $post_data['repair_status'],
    //           'repaired_by' => Auth::id(),
    //           'professional_help' => 1,
    //         ]);
    //         break;
    //     case 3:
    //         Device::create([
    //           'event' => $request->input('event_id'),
    //           'category' => $post_data['category'],
    //           'category_creation' => $post_data['category'],
    //           'brand' => $brand,
    //           'model' => $post_data['model'],
    //           'age' => $post_data['age'],
    //           'problem' => $post_data['problem'],
    //           'spare_parts' => $post_data['spare_parts'],
    //           'repair_status' => $post_data['repair_status'],
    //           'repaired_by' => Auth::id(),
    //           'do_it_yourself' => 1,
    //         ]);
    //         break;
    //   }
    //
    //   if ($post_data['repair_status'] == 0) {
    //     $data['error'] = "Device couldn't be added - no repair details added";
    //   }
    //
    // } else {
    // }

  }

  public function ajaxEdit(Request $request, $id) {

    $category       = $request->input('category');
    $weight         = $request->input('weight');
    $brand          = $request->input('brand');
    $model          = $request->input('model');
    $age            = $request->input('age');
    $problem        = $request->input('problem');
    $repair_status  = $request->input('repair_status');
    $repair_details = $request->input('repair_details');
    $spare_parts    = $request->input('spare_parts');
    $event_id       = $request->input('event_id');
    $wiki           = $request->input('wiki');

    if( empty($repair_status) ) //Override
      $repair_status = 0;

    if( $repair_status != 2 ) //Override
      $repair_details = 0;

    $in_event = EventsUsers::where('event', $event_id)->where('user', Auth::user()->id)->first();

    if(FixometerHelper::hasRole(Auth::user(), 'Administrator') || is_object($in_event) ){

      // if ($repair_status == 2) {
      //   switch ($repair_details) {
      //     case 1:
      //         Device::find($id)->update([
      //           'category' => $category,
      //           'category_creation' => $category,
      //           'brand' => $brand,
      //           'model' => $model,
      //           'age' => $age,
      //           'problem' => $problem,
      //           'spare_parts' => $spare_parts,
      //           'repair_status' => $repair_status,
      //           'more_time_needed' => 1,
      //           'wiki' => $wiki,
      //         ]);
      //         break;
      //     case 2:
      //         Device::find($id)->update([
      //           'category' => $category,
      //           'category_creation' => $category,
      //           'brand' => $brand,
      //           'model' => $model,
      //           'age' => $age,
      //           'problem' => $problem,
      //           'spare_parts' => $spare_parts,
      //           'repair_status' => $repair_status,
      //           'professional_help' => 1,
      //           'wiki' => $wiki,
      //         ]);
      //         break;
      //     case 3:
      //         Device::find($id)->update([
      //           'category' => $category,
      //           'category_creation' => $category,
      //           'brand' => $brand,
      //           'model' => $model,
      //           'age' => $age,
      //           'problem' => $problem,
      //           'spare_parts' => $spare_parts,
      //           'repair_status' => $repair_status,
      //           'do_it_yourself' => 1,
      //           'wiki' => $wiki,
      //         ]);
      //         break;
      //   }


        if( $repair_details == 1 ){
          $more_time_needed = 1;
        } else {
          $more_time_needed = 0;
        }

        if( $repair_details == 2 ){
          $professional_help = 1;
        } else {
          $professional_help = 0;
        }

        if( $repair_details == 3 ){
          $do_it_yourself = 1;
        } else {
          $do_it_yourself = 0;
        }

        Device::find($id)->update([
          'category' => $category,
          'category_creation' => $category,
          'brand' => $brand,
          'model' => $model,
          'age' => $age,
          'problem' => $problem,
          'spare_parts' => $spare_parts,
          'repair_status' => $repair_status,
          'more_time_needed' => $more_time_needed,
          'do_it_yourself' => $professional_help,
          'professional_help' => $do_it_yourself,
          'wiki' => $wiki,
        ]);

        $event = Party::find($event_id);

        $Device = new Device;
        $weights = $Device->getWeights();

        $TotalWeight = $weights[0]->total_weights;
        $TotalEmission = $weights[0]->total_footprints;
        $EmissionRatio = $TotalEmission / $TotalWeight;
        $stats = $event->getEventStats($EmissionRatio);
        $data['stats'] = $stats;

        // if ($repair_status == 0) {
        //   $data['error'] = "Device couldn't be updated - no repair details added";
        //   return response()->json($data);
        // }

        $data['success'] = "Device updated!";

        return response()->json($data);

      // } else {
      //
      //   Device::find($id)->update([
      //     'category' => $category,
      //     'category_creation' => $category,
      //     'brand' => $brand,
      //     'model' => $model,
      //     'age' => $age,
      //     'problem' => $problem,
      //     'spare_parts' => $spare_parts,
      //     'repair_status' => $repair_status,
      //     'more_time_needed' => 0,
      //     'professional_help' => 0,
      //     'do_it_yourself' => 0,
      //     'wiki' => $wiki,
      //   ]);
      //
      //   $event = Party::find($event_id);
      //
      //   $Device = new Device;
      //   $weights = $Device->getWeights();
      //
      //   $TotalWeight = $weights[0]->total_weights;
      //   $TotalEmission = $weights[0]->total_footprints;
      //   $EmissionRatio = $TotalEmission / $TotalWeight;
      //   $stats = $event->getEventStats($EmissionRatio);
      //   $data['stats'] = $stats;
      //
      //   $data['success'] = "Device updated!";
      //
      //   return response()->json($data);
      //
      // }

    }

  }

  public function delete(Request $request, $id){
      $Device = new Device;
      $user = Auth::user();

      // get device party
      $curr = $Device->find($id);
      $party = $curr->event;

      if(FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::userHasEditPartyPermission($party, $user->id) ){
          $r = $curr->delete();
          if( $request->ajax() ){
            return response()->json(['success' => true]);
          } else {
            return redirect('/party/view/'.$party)->with('success', 'Device has been deleted!');
          }
      } else {
          if( $request->ajax() ){
            return response()->json(['success' => false]);
          } else {
            return redirect('/party/view/'.$party->with('warning', 'You do not have the right permissions for deleting a device'));
          }
      }
  }

    // public function test() {
    //   $g = new Device;
    //   dd($g->export());
    // }
}
