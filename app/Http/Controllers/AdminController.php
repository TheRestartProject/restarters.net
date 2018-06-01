<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\User;
use App\Group;
use App\Party;
use App\Device;

use FixometerHelper;
use Auth;

class AdminController extends Controller
{
  public $TotalWeight;
  public $TotalEmission;
  public $EmissionRatio;

  // public function __construct($model, $controller, $action){
  //     parent::__construct($model, $controller, $action);
  //
  //     if (Auth::check()) {
  //         $user = User::getProfile(Auth::id());
  //         $this->set('user', $user);//send to the view
  //         $this->set('header', true);//send to the view
  //
  //         if(!FixometerHelper::hasRole($user, 'Administrator') &&  $action != 'stats') {
  //             header('Location: /user/forbidden');
  //         }
  //
  //         else {
  //             $Device = new Device;
  //             $weights = $Device->getWeights();
  //
  //             $this->TotalWeight = $weights[0]->total_weights;//send to view
  //             $this->TotalEmission = $weights[0]->total_footprints;//send to view
  //             if ($this->TotalWeight != 0) {//send to view
  //               $this->EmissionRatio = $this->TotalEmission / $this->TotalWeight;
  //             } else {
  //               $this->EmissionRatio = $this->TotalEmission;
  //             }
  //
  //
  //         }
  //     } else {
  //       header('Location: /user/login');
  //     }
  // }

  public function stats($section = 1, $paragraph_only = false){
      //Object Instances
      $Group = new Group;
      $User = new User;
      $Party = new Party;
      $Device = new Device;

      $allparties = $Party->ofThisGroup('admin', true, true);

      $participants = 0;
      $hours_volunteered = 0;

      $weights = $Device->getWeights();
      $TotalWeight = $weights[0]->total_weights;
      $TotalEmission = $weights[0]->total_footprints;
      if ($TotalWeight != 0) {
         $EmissionRatio = $TotalEmission / $TotalWeight;
      } else {
         $EmissionRatio = $TotalEmission;
      }

      $need_attention = 0;
      foreach($allparties as $i => $party){
          if($party->device_count == 0){
              $need_attention++;
          }

          $party->co2 = 0;
          $party->fixed_devices = 0;
          $party->repairable_devices = 0;
          $party->dead_devices = 0;
          $party->guesstimates = false;

          $participants += $party->pax;
          $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);

          foreach($party->devices as $device){



              switch($device->repair_status){
                  case 1:
                      $party->co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $EmissionRatio) : $device->footprint);
                      $party->fixed_devices++;
                      break;
                  case 2:
                      $party->repairable_devices++;
                      break;
                  case 3:
                      $party->dead_devices++;
                      break;
              }
              if($device->category == 46){
                  $party->guesstimates = true;
              }
          }

          $party->co2 = number_format(round($party->co2 * $Device->displacement), 0, '.' , ',');
      }

      $devices = $Device->ofAllGroups();

      // more stats...

      /** co2 counters **/
      $co2_years = $Device->countCO2ByYear();
      $stats = array();
      foreach($co2_years as $year){
          $stats[$year->year] = $year->co2;
      }

      $waste_years = $Device->countWasteByYear();
      $wstats = array();
      foreach($waste_years as $year){
          $wstats[$year->year] = $year->waste;
      }


      $co2Total = $Device->getWeights();
      $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));

      $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));


      $clusters = array();

      for($i = 1; $i <= 4; $i++) {
          $cluster = $Device->countByCluster($i);
          $total = 0;
          foreach($cluster as $state){
              $total += $state->counter;
          }
          $cluster['total'] = $total;
          $clusters['all'][$i] = $cluster;
      }


      for($y = date('Y', time()); $y>=2013; $y--){

          for($i = 1; $i <= 4; $i++) {
              //$cluster = $Device->countByCluster($i, $group->idgroups);
              $cluster = $Device->countByCluster($i, null, $y);

              $total = 0;
              foreach($cluster as $state){
                  $total += $state->counter;
              }
              $cluster['total'] = $total;
              $clusters[$y][$i] = $cluster;
          }
      }

      // most/least stats for clusters
      $mostleast = array();
      for($i = 1; $i <= 4; $i++){
          $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i);
          $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i);
          $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i);

      }

      $user = User::find(Auth::id());

      return view('admin.stats', [
        'section' => $section,
        'paragraph_only' => $paragraph_only,
        'grouplist' => $Group->findList(),
        'pax' => $participants,
        'hours' => $hours_volunteered,
        'showbadges' => $Device->guesstimates(),
        'need_attention' => $need_attention,
        'user' => $user,
        'profile' => $user->getProfile($user->id),
        'upcomingparties' => $Party->findNextParties(),
        'allparties' => $allparties,
        'devices' => $devices,
        'weights' => array(0 => array('total_footprints' => $TotalEmission, 'total_weights' => $TotalWeight)),
        'device_count_status' => $Device->statusCount(),
        'year_data' => $co2_years,
        'bar_chart_stats' => array_reverse($stats, true),
        'waste_year_data' => $waste_years,
        'waste_bar_chart_stats' => array_reverse($wstats, true),
        'co2Total' => $co2Total[0]->total_footprints,
        'co2ThisYear' => $co2ThisYear[0]->co2,
        'wasteTotal' => $co2Total[0]->total_weights,
        'wasteThisYear' => $wasteThisYear[0]->waste,
        'clusters' => $clusters,
        'mostleast' => $mostleast,
        'top' => $Device->findMostSeen(1, null, null),
      ]);

  }

  public static function index(){

      // $this->set('title', 'Administrator Dashboard');
      // $this->set('charts', true);

      //Not required now since jquery has been added globally
      // $this->set('css', array('/components/perfect-scrollbar/css/perfect-scrollbar.min.css'));
      // $this->set('js', array('foot' => array('/components/perfect-scrollbar/js/min/perfect-scrollbar.jquery.min.js')));
      if(isset($_GET['action']) && isset($_GET['code'])){
          $actn = $_GET['action'];
          $code = $_GET['code'];

          switch($actn){
              case 'gu':
                  $response['success'] = 'Group updated.';
                  break;
              case 'pe':
                  $response['success'] = 'Party updated.';
                  break;
              case 'pc':
                  $response['success'] = 'Party created.';
                  break;
              case 'ue':
                  $response['success'] = 'Profile updated.';
          }

          // $this->set('response', $response);
      }

      //Object Instances
      $Group = new Group;
      $User = new User;
      $Party = new Party;
      $Device = new Device;

      $allparties = $Party->ofThisGroup('admin', true, true);

      $participants = 0;
      $hours_volunteered = 0;
      $need_attention = 0;

      $weights = $Device->getWeights();
      $TotalWeight = $weights[0]->total_weights;
      $TotalEmission = $weights[0]->total_footprints;
      if ($TotalWeight != 0) {
         $EmissionRatio = $TotalEmission / $TotalWeight;
      } else {
         $EmissionRatio = $TotalEmission;
      }

      foreach($allparties as $i => $party){
          if($party->device_count == 0){
              $need_attention++;
          }

          $party->co2 = 0;
          $party->fixed_devices = 0;
          $party->repairable_devices = 0;
          $party->dead_devices = 0;
          $party->guesstimates = false;

          $participants += $party->pax;
          $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);

          foreach($party->devices as $device){



              switch($device->repair_status){
                  case 1:
                      $party->co2 += (!empty($device->estimate) && $device->category == 46 && is_numeric($device->estimate) ? ($device->estimate * $EmissionRatio) : $device->footprint);
                      $party->fixed_devices++;
                      break;
                  case 2:
                      $party->repairable_devices++;
                      break;
                  case 3:
                      $party->dead_devices++;
                      break;
              }
              if($device->category == 46){
                  $party->guesstimates = true;
              }
          }

          $party->co2 = number_format(round($party->co2 * $Device->displacement), 0, '.' , ',');
      }

      $weights = $Device->getWeights();
      $devices = $Device->ofAllGroups();

      // more stats...

      /** co2 counters **/
      $co2_years = $Device->countCO2ByYear();
      $co2_years = array();
      // $this->set('year_data', $co2_years);
      $stats = array();
      foreach($co2_years as $year){
          $stats[$year->year] = $year->co2;
      }

      $waste_years = $Device->countWasteByYear();
      $wstats = array();
      foreach($waste_years as $year){
          $wstats[$year->year] = $year->waste;
      }


      $co2Total = $Device->getWeights();
      $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));


      $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));

      $clusters = array();

      for($i = 1; $i <= 4; $i++) {
          $cluster = $Device->countByCluster($i);
          $total = 0;
          foreach($cluster as $state){
              $total += $state->counter;
          }
          $cluster['total'] = $total;
          $clusters['all'][$i] = $cluster;
      }


      for($y = date('Y', time()); $y>=2013; $y--){

          for($i = 1; $i <= 4; $i++) {
              $cluster = $Device->countByCluster($i, null, $y);

              $total = 0;
              foreach($cluster as $state){
                  $total += $state->counter;
              }
              $cluster['total'] = $total;
              $clusters[$y][$i] = $cluster;
          }
      }

      // most/least stats for clusters
      $mostleast = array();
      for($i = 1; $i <= 4; $i++){
          $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i);
          $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i);
          $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i);

      }


      if (!isset($response)) {
        $response = null;
      }

      return view('admin.index', [
        'title' => 'Administrator Dashboard',
        'charts' => true,
        'response' => $response,
        'grouplist' => $Group->findList(),
        'pax' => $participants,
        'hours' => $hours_volunteered,
        'showbadges' => $Device->guesstimates(),
        'need_attention' => $need_attention,
        'profile' => $User->getProfile(Auth::id()),
        'upcomingparties' => $Party->findNextParties(),
        'allparties' => $allparties,
        'devices' => $devices,
        'weights' => $weights,
        'device_count_status' => $Device->statusCount(),
        'year_data' => $co2_years,
        'bar_chart_stats' => array_reverse($stats, true),
        'waste_year_data' => $waste_years,
        'waste_bar_chart_stats' => array_reverse($wstats, true),
        'co2Total' => $co2Total[0]->total_footprints,
        'co2ThisYear' => $co2ThisYear[0]->co2,
        'wasteTotal' => $co2Total[0]->total_weights,
        'wasteThisYear' => $wasteThisYear[0]->waste,
        'clusters' => $clusters,
        'mostleast' => $mostleast,
        'top' => $Device->findMostSeen(1, null, null),
      ]);

  }

  public function eventsCsv(){

      // $this->set('title', 'Administrator Dashboard');
      // $this->set('charts', true);
      //
      // $this->set('css', array('/components/perfect-scrollbar/css/perfect-scrollbar.min.css'));
      // $this->set('js', array('foot' => array('/components/perfect-scrollbar/js/min/perfect-scrollbar.jquery.min.js')));

      if(isset($_GET['action']) && isset($_GET['code'])){
          $actn = $_GET['action'];
          $code = $_GET['code'];

          switch($actn){
              case 'gu':
                  $response['success'] = 'Group updated.';
                  break;
              case 'pe':
                  $response['success'] = 'Party updated.';
                  break;
              case 'pc':
                  $response['success'] = 'Party created.';
                  break;
              case 'ue':
                  $response['success'] = 'Profile updated.';
          }

          // $this->set('response', $response);
      }

      //Object Instances
      $Group = new Group;
      $User = new User;
      $Party = new Party;
      $Device = new Device;


      // $this->set('grouplist', $Group->findList());

      $allparties = $Party->ofThisGroup2('admin', true, true);

      $participants = 0;
      $hours_volunteered = 0;

      $need_attention = 0;
      foreach($allparties as $i => $party){
          if($party->device_count == 0){
              $need_attention++;
          }

          $party->co2 = 0;
          $party->ewaste = 0;
          $party->fixed_devices = 0;
          $party->repairable_devices = 0;
          $party->dead_devices = 0;
          $party->guesstimates = false;

          $weights = $Device->getWeights();
          $TotalWeight = $weights[0]->total_weights;
          $TotalEmission = $weights[0]->total_footprints;
          if ($TotalWeight != 0) {
             $EmissionRatio = $TotalEmission / $TotalWeight;
          } else {
             $EmissionRatio = $TotalEmission;
          }

          $participants += $party->pax;
          $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);

          foreach($party->devices as $device){


              switch($device->repair_status){
                  case 1:
                      $party->co2 += (!empty($device->estimate) && $device->category == 46 && is_numeric($device->estimate) ? ($device->estimate * $EmissionRatio) : $device->footprint);
                      $party->ewaste  += (!empty($device->estimate) && $device->category==46 && is_numeric($device->estimate) ? $device->estimate : $device->weight);
                      $party->fixed_devices++;
                      break;
                  case 2:
                      $party->repairable_devices++;
                      break;
                  case 3:
                      $party->dead_devices++;
                      break;
              }
              if($device->category == 46){
                  $party->guesstimates = true;
              }
          }

          $party->co2 = number_format(round($party->co2 * $Device->displacement), 0, '.' , ',');
      }
      // $this->set('pax', $participants);
      // $this->set('hours', $hours_volunteered);

      $weights = $Device->getWeights();
      $devices = $Device->ofAllGroups();

      // $this->set('showbadges', $Device->guesstimates());
      //
      // $this->set('need_attention', $need_attention);
      //
      // $this->set('profile', $User->profilePage($this->user->id));
      //
      // $this->set('upcomingparties', $Party->findNextParties());
      // $this->set('allparties', $allparties);

      // $this->set('devices', $devices);
      // $this->set('weights', $weights);
      //
      // $this->set('device_count_status', $Device->statusCount());


      // more stats...

      /** co2 counters **/
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


      $co2Total = $Device->getWeights();
      $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));

      // $this->set('co2Total', $co2Total[0]->total_footprints);
      // $this->set('co2ThisYear', $co2ThisYear[0]->co2);

      $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));

      // $this->set('wasteTotal', $co2Total[0]->total_weights);
      // $this->set('wasteThisYear', $wasteThisYear[0]->waste);


      $clusters = array();

      for($i = 1; $i <= 4; $i++) {
          $cluster = $Device->countByCluster($i);
          $total = 0;
          foreach($cluster as $state){
              $total += $state->counter;
          }
          $cluster['total'] = $total;
          $clusters['all'][$i] = $cluster;
      }


      for($y = date('Y', time()); $y>=2013; $y--){

          for($i = 1; $i <= 4; $i++) {
              //$cluster = $Device->countByCluster($i, $group->idgroups);
              $cluster = $Device->countByCluster($i, null, $y);

              $total = 0;
              foreach($cluster as $state){
                  $total += $state->counter;
              }
              $cluster['total'] = $total;
              $clusters[$y][$i] = $cluster;
          }
      }
      // $this->set('clusters', $clusters);

      // most/least stats for clusters
      $mostleast = array();
      for($i = 1; $i <= 4; $i++){
          $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i);
          $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i);
          $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i);

      }

      // $this->set('mostleast', $mostleast);
      // $this->set('top', $Device->findMostSeen(1, null, null));

      return view('admin.eventsCsv', [//csv
        'title' => 'Administrator Dashboard',
        'charts' => true,
        'response' => $response,
        'grouplist' => $Group->findList(),
        'pax' => $participants,
        'hours' => $hours_volunteered,
        'showbadges' => $Device->guesstimates(),
        'need_attention' => $need_attention,
        'profile' => $User->profilePage($user->id),
        'upcomingparties' => $Party->findNextParties(),
        'allparties' => $allparties,
        'devices' => $devices,
        'weights' => $weights,
        'device_count_status' => $Device->statusCount(),
        'year_data' => $co2_years,
        'bar_chart_stats' => array_reverse($stats, true),
        'waste_year_data' => $waste_years,
        'waste_bar_chart_stats' => array_reverse($wstats, true),
        'co2Total' => $co2Total[0]->total_footprints,
        'co2ThisYear' => $co2ThisYear[0]->co2,
        'wasteTotal' => $co2Total[0]->total_weights,
        'wasteThisYear' => $wasteThisYear[0]->waste,
        'clusters' => $clusters,
        'mostleast' => $mostleast,
        'top' => $Device->findMostSeen(1, null, null),
      ]);
  }

}
