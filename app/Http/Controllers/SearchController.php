<?php

namespace App\Http\Controllers;

use App\Device;
use App\Group;
use App\GroupTags;
use App\Party;
use App\Search;
use App\User;
use App\Helpers\FootprintRatioCalculator;
use Auth;
use FixometerHelper;

use DateTime;

class SearchController extends Controller {

  public $TotalWeight;
  public $TotalEmission;
  public $EmissionRatio;

  public function __construct(){ //($model, $controller, $action)
  //     parent::__construct($model, $controller, $action);
  //
  //     $Auth = new Auth($url);
  //     if(!$Auth->isLoggedIn() && $action != 'stats'){
  //         header('Location: /user/login');
  //     }
  //     else {
  //
  //         $user = $Auth->getProfile();
  //         $this->user = $user;
  //         $this->set('user', $user);
  //         $this->set('header', true);
  //
          $Device = new Device;
          $weights = $Device->getWeights();

          $this->TotalWeight = $weights[0]->total_weights;
          $this->TotalEmission = $weights[0]->total_footprints;

          $footprintRatioCalculator = new FootprintRatioCalculator();
          $this->EmissionRatio = $footprintRatioCalculator->calculateRatio();

  //
  //         if(FixometerHelper::hasRole($this->user, 'Host')){
  //             $User = new User;
  //             $this->set('profile', $User->profilePage($this->user->id));
  //         }
  //     }
  }

  public function index($response = null){

    // $this->set('charts', true);
    //
    // $this->set('css', array('/components/perfect-scrollbar/css/perfect-scrollbar.min.css'));
    // $this->set('js', array('foot' => array(
    //   '/components/perfect-scrollbar/js/min/perfect-scrollbar.jquery.min.js',
    // )));

      /** Init all needed classes **/
      $Groups = new Group;
      $Parties = new Party;
      $Device = new Device;
      $Search = new Search;
      // $this->set('title', 'Filter Stats');

      $user = User::find(Auth::id());

      $allowedParties = [];
      /** Get default data for the search dropdowns **/
      if(FixometerHelper::hasRole($user, 'Administrator')){
        $groups = $Groups->findList();
        $parties = $Parties->findAllSearchable();
        foreach( $parties as $i => $party ) {
          $parties[$i]->venue = !is_null($parties[$i]->venue) ? $parties[$i]->venue : $parties[$i]->location;
          $allowedParties[] = $party->id;
        }
      }

      elseif(FixometerHelper::hasRole($user, 'Host')) {
        $groups =   $Groups->ofThisUser($user->id);
        $groupIds = array();
        foreach( $groups as $i => $group ) {
          $groups[$i]->id = $group->idgroups;
          $groupIds[] = $group->idgroups;
        }

        $parties =  $Parties->ofTheseGroups($groupIds, true);

        foreach( $parties as $i => $party ) {
          $parties[$i]->id = $party->idevents;
          $parties[$i]->venue = !is_null($parties[$i]->venue) ? $parties[$i]->venue : $parties[$i]->location;
          $allowedParties[] = $party->idevents;
        }
      }
      /** set parties to be grouped by group **/
      $sorted_parties = array();
      foreach($parties as $party){
        $sorted_parties[$party->group_name][] = $party;
      }
      // $this->set('sorted_parties', $sorted_parties);
      // $this->set('parties', $parties);
      // $this->set('groups', $groups);

      if(isset($_GET['fltr']) && !empty($_GET['fltr'])) {
        $searched_groups = null;
        $searched_parties = null;
        $toTimeStamp = null;
        $fromTimeStamp = null;
        $group_tags = null;

        /** collect params **/
        if(isset($_GET['groups'])){
          $searched_groups = filter_var_array($_GET['groups'], FILTER_SANITIZE_NUMBER_INT);
        }

        if(isset($_GET['parties'])){
          $searched_parties = filter_var_array($_GET['parties'], FILTER_SANITIZE_NUMBER_INT);
        }


        if(isset($_GET['from-date']) && !empty($_GET['from-date'])){
          if (!DateTime::createFromFormat('Y-m-d', $_GET['from-date'])) {
            $response['danger'] = 'Invalid "from date"';
            $fromTimeStamp = null;
          }
          else {
            $fromDate = DateTime::createFromFormat('Y-m-d', $_GET['from-date']);
            $fromTimeStamp = strtotime($fromDate->format('Y-m-d'));
          }
        }

        if(isset($_GET['to-date']) && !empty($_GET['to-date'])){
          if (!DateTime::createFromFormat('Y-m-d', $_GET['to-date'])) {
            $response['danger'] = 'Invalid "to date"';
          }
          else {
            $toDate = DateTime::createFromFormat('Y-m-d', $_GET['to-date']);
            $toTimeStamp = strtotime($toDate->format('Y-m-d'));
          }
        }

        if( isset($_GET['group_tags']) && is_array($_GET['group_tags']) ){
          $group_tags = $_GET['group_tags'];
        }

        $PartyList = $Search->parties($searched_parties, $searched_groups, $fromTimeStamp, $toTimeStamp, $group_tags, $allowedParties);
        if(count($PartyList) > 0 ){
          //dbga($PartyList[8]);
          $partyIds = array();
          $participants = 0;
          $hours_volunteered = 0;
          $totalCO2 = 0;
          $totalWeight = 0;
          $need_attention = 0;
        //  dbga($PartyList[12]->devices);
          foreach ($PartyList as $party) {
              $partyIds[] = $party->idevents;

              $party->co2 = 0;
              $party->ewaste = 0;
              $party->fixed_devices = 0;
              $party->repairable_devices = 0;
              $party->dead_devices = 0;

              $participants += $party->pax;
              $hours_volunteered += $party->hoursVolunteered();

              foreach ($party->devices as $device) {

                  switch ($device->repair_status) {

                      case 1:
                          $party->fixed_devices++;

                          $party->co2 += $device->co2Diverted($this->EmissionRatio, $Device->displacement);

                          $party->ewaste += $device->ewasteDiverted();

                          break;
                      case 2:
                          $party->repairable_devices++;
                          break;
                      case 3:
                          $party->dead_devices++;
                          break;
                  }
              }

              $totalWeight += $party->ewaste;
              $totalCO2 += $party->co2;
          }

          /** Cluster dataviz **/
          $clusters = array();

          for($i = 1; $i <= 4; $i++) {
              $cluster = $Search->countByCluster($partyIds, $i);
              $total = 0;
              foreach($cluster as $state){
                  $total += $state->counter;
              }
              $cluster['total'] = $total;
              $clusters['all'][$i] = $cluster;
          }

          // $this->set('clusters', $clusters);

          // most/least stats for clusters
          $mostleast = array();
          for($i = 1; $i <= 4; $i++){
              $mostleast[$i]['most_seen'] = $Search->findMostSeen($partyIds,null, $i);
              $mostleast[$i]['most_repaired'] = $Search->findMostSeen($partyIds,1, $i);
              $mostleast[$i]['least_repaired'] = $Search->findMostSeen($partyIds,3, $i);

          }

          // $this->set('mostleast', $mostleast);


          // $this->set('pax', $participants);
          // $this->set('hours', $hours_volunteered);
          // $this->set('totalWeight', $totalWeight);
          // $this->set('totalCO2', $totalCO2);
          // $this->set('device_count_status', $this->Search->deviceStatusCount($partyIds));
          // $this->set('top', $this->Search->findMostSeen($partyIds, 1, null));
          // $this->set('PartyList', $PartyList);
        }
        else {
          $response['warning'] = 'No results for this set of parameters!';
        }
      }

      if (!isset($clusters)) {
        $clusters = null;
      }

      if (!isset($mostleast)) {
        $mostleast = null;
      }

      if (!isset($participants)) {
        $participants = null;
      }

      if (!isset($hours_volunteered)) {
        $hours_volunteered = null;
      }

      if (!isset($totalWeight)) {
        $totalWeight = null;
      }

      if (!isset($totalCO2)) {
        $totalCO2 = null;
      }

      if (!isset($partyIds)) {
        return view('search.index', [
          'charts' => true,
          'title' => 'Filter Stats',
          'sorted_parties' => $sorted_parties,
          'parties' => $parties,
          'groups' => $groups,
          'clusters' => $clusters,
          'mostleast' => $mostleast,
          'pax' => $participants,
          'hours' => $hours_volunteered,
          'totalWeight' => $totalWeight,
          'totalCO2' => $totalCO2,
          'response' => $response,
          'user' => $user,
          'group_tags' => GroupTags::all()
        ]);
      } else {
        return view('search.index', [
          'charts' => true,
          'title' => 'Filter Stats',
          'sorted_parties' => $sorted_parties,
          'parties' => $parties,
          'groups' => $groups,
          'clusters' => $clusters,
          'mostleast' => $mostleast,
          'pax' => $participants,
          'hours' => $hours_volunteered,
          'totalWeight' => $totalWeight,
          'totalCO2' => $totalCO2,
          'device_count_status' => $Search->deviceStatusCount($partyIds),
          'top' => $Search->findMostSeen($partyIds, 1, null),
          'PartyList' => $PartyList,
          'response' => $response,
          'user' => $user,
          'group_tags' => GroupTags::all()
        ]);
      }

  }

}
