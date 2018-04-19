<?php

    class HostController extends Controller {

        public $TotalWeight;
        public $TotalEmission;
        public $EmissionRatio;

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

                if(!hasRole($this->user, 'Host') && !hasRole($this->user, 'Administrator')) {
                    header('Location: /user/forbidden');
                }
                else {
                    $Device = new Device;
                    $weights = $Device->getWeights();

                    $this->TotalWeight = $weights[0]->total_weights;
                    $this->TotalEmission = $weights[0]->total_footprints;
                    $this->EmissionRatio = $this->TotalEmission / $this->TotalWeight;


                }
            }
        }

        public function index($groupid = null){

            $this->set('title', 'Host Dashboard');
            $this->set('showbadges', true);
            $this->set('charts', false);

            $this->set('css', array('/components/perfect-scrollbar/css/perfect-scrollbar.min.css'));
            $this->set('js', array('foot' => array('/components/perfect-scrollbar/js/min/perfect-scrollbar.jquery.min.js')));

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
                        break;
                    case 'de':
                        if($code == 200 ) { $response['success'] = 'Party deleted.'; }
                        elseif( $code == 403 ) { $response['danger'] = 'Couldn\'t delete the party!'; }
                        elseif( $code == 500 ) { $response['warning'] = 'The party has been deleted, but <strong>something went wrong while deleting it from WordPress</strong>. <br /> You\'ll need to do that manually!';  }
                        break;
                }

                $this->set('response', $response);
            }

            //Object Instances
            $Group  = new Group;
            $User   = new User;
            $Party  = new Party;
            $Device = new Device;
            $groups = $Group->ofThisUser($this->user->id);

            // get list of ids to check in if condition
            $gids = array();
            foreach($groups as $group){
              $gids[] = $group->idgroups;
            }

            if( isset($groupid) && is_numeric($groupid) && ( hasRole($this->user, 'Administrator') || in_array($groupid, $gids) ) ) { //
                
                //$group = (object) array_fill_keys( array('idgroups') , $groupid);
                $group = $Group->findOne($groupid);
                $this->set('grouplist', $Group->findList());



            }
            else {
                $group = $groups[0];
                unset($groups[0]);
            }
            $this->set('userGroups', $groups);

            $allparties = $Party->ofThisGroup($group->idgroups, true, true);

            $participants = 0;
            $hours_volunteered = 0;

            $need_attention = 0;
            foreach($allparties as $i => $party){
                if($party->device_count == 0){
                    $need_attention++;
                }

                $party->co2 = 0;
                $party->fixed_devices = 0;
                $party->repairable_devices = 0;
                $party->dead_devices = 0;

                $participants += $party->pax;
                $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);

                foreach($party->devices as $device){
                    if($device->repair_status == DEVICE_FIXED){
                        $party->co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);

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
            }
            $this->set('pax', $participants);
            $this->set('hours', $hours_volunteered);
            $weights = $Device->getWeights($group->idgroups);
            $this->set('weights', $weights);

            $devices = $Device->ofThisGroup($group->idgroups);

            /*
            foreach($devices as $i => $device){

            }
            */

            $this->set('need_attention', $need_attention);

            $this->set('group', $group);
            $this->set('profile', $User->profilePage($this->user->id));

            $this->set('upcomingparties', $Party->findNextParties($group->idgroups));
            $this->set('allparties', $allparties);

            $this->set('devices', $Device->ofThisGroup($group->idgroups));


            $this->set('device_count_status', $Device->statusCount());
            $this->set('group_device_count_status', $Device->statusCount($group->idgroups));

            // more stats...

            /** co2 counters **/
            $co2_years = $Device->countCO2ByYear($group->idgroups);
            $this->set('year_data', $co2_years);
            $stats = array();
            foreach($co2_years as $year){
                $stats[$year->year] = $year->co2;
            }
            $this->set('bar_chart_stats', array_reverse($stats, true));

            $waste_years = $Device->countWasteByYear($group->idgroups);

            //dbga($waste_years);

            $this->set('waste_year_data', $waste_years);
            $wstats = array();
            foreach($waste_years as $year){
                $wstats[$year->year] = $year->waste;
            }
            $this->set('waste_bar_chart_stats', array_reverse($wstats, true));


            // $co2Total = $Device->getWeights();
            $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));

            $this->set('co2Total', $this->TotalEmission);
            $this->set('co2ThisYear', $co2ThisYear[0]->co2);

            $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));

            $this->set('wasteTotal', $this->TotalWeight);
            $this->set('wasteThisYear', $wasteThisYear[0]->waste);


            $clusters = array();

            for($i = 1; $i <= 4; $i++) {
                $cluster = $Device->countByCluster($i, $group->idgroups);
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
                    $cluster = $Device->countByCluster($i, $group->idgroups, $y);

                    $total = 0;
                    foreach($cluster as $state){
                        $total += $state->counter;
                    }
                    $cluster['total'] = $total;
                    $clusters[$y][$i] = $cluster;
                }
            }
            $this->set('clusters', $clusters);

            // most/least stats for clusters
            $mostleast = array();
            for($i = 1; $i <= 4; $i++){
                $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i, $group->idgroups);
                $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i, $group->idgroups);
                $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i, $group->idgroups);

            }

            $this->set('mostleast', $mostleast);

            $this->set('top', $Device->findMostSeen(1, null, $group->idgroups));


        }


    }
