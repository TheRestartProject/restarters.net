<?php

    class AdminController extends Controller {
        
        public $TotalWeight;
        public $TotalEmission;
        public $EmissionRatio;
        
        public function __construct($model, $controller, $action){
            parent::__construct($model, $controller, $action);
            
            $Auth = new Auth($url);
            if(!$Auth->isLoggedIn() && $action != 'stats'){
                header('Location: /user/login');
            }
            else {
                $user = $Auth->getProfile();
                $this->user = $user;
                $this->set('user', $user);
                $this->set('header', true);
                
                if(!hasRole($this->user, 'Administrator') &&  $action != 'stats') {
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
        
        
        public function stats($section = 1, $paragraph_only = false){
            //Object Instances
            $Group = new Group;
            $User = new User;
            $Party = new Party;
            $Device = new Device;
            
            $this->set('section', $section);
            $this->set('paragraph_only', $paragraph_only);
            $this->set('grouplist', $Group->findList());
            
            $allparties = $Party->ofThisGroup('admin', true, true);
            
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
                $party->guesstimates = false;
                
                $participants += $party->pax;
                $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);
                
                foreach($party->devices as $device){
                    
                   
                    
                    switch($device->repair_status){
                        case 1:
                            $party->co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);
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
            $this->set('pax', $participants);
            $this->set('hours', $hours_volunteered);
            
            $devices = $Device->ofAllGroups();
            
            
            
            $this->set('showbadges', $Device->guesstimates());
            
            $this->set('need_attention', $need_attention);
            
            $this->set('profile', $User->profilePage($this->user->id));
            
            $this->set('upcomingparties', $Party->findNextParties());
            $this->set('allparties', $allparties);
            
            $this->set('devices', $devices); 
            $this->set('weights', array(0 => array('total_footprints' => $this->TotalEmission, 'total_weights' => $this->TotalWeight)));
            
            $this->set('device_count_status', $Device->statusCount());            
            
            
            // more stats...
            
            /** co2 counters **/
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
            
            
            $co2Total = $Device->getWeights();
            $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));
            
            $this->set('co2Total', $co2Total[0]->total_footprints);
            $this->set('co2ThisYear', $co2ThisYear[0]->co2);
            
            $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));
            
            $this->set('wasteTotal', $co2Total[0]->total_weights);
            $this->set('wasteThisYear', $wasteThisYear[0]->waste);
            
            
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
            $this->set('clusters', $clusters);
            
            // most/least stats for clusters
            $mostleast = array();
            for($i = 1; $i <= 4; $i++){
                $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i);
                $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i);
                $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i);
                
            }
            
            $this->set('mostleast', $mostleast);
            $this->set('top', $Device->findMostSeen(1, null, null)); 
            
        }
        public function index(){
            
            $this->set('title', 'Administrator Dashboard');
            $this->set('charts', true);
            
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
                }
                    
                $this->set('response', $response);    
            }
            
            //Object Instances
            $Group = new Group;
            $User = new User;
            $Party = new Party;
            $Device = new Device;
            
            
            $this->set('grouplist', $Group->findList());
            
            $allparties = $Party->ofThisGroup('admin', true, true);
            
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
                $party->guesstimates = false;
                
                $participants += $party->pax;
                $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);
                
                foreach($party->devices as $device){
                    
                    
                    
                    switch($device->repair_status){
                        case 1:
                            $party->co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);
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
            $this->set('pax', $participants);
            $this->set('hours', $hours_volunteered);
            
            $weights = $Device->getWeights();
            $devices = $Device->ofAllGroups();
            
            $this->set('showbadges', $Device->guesstimates());
            
            $this->set('need_attention', $need_attention);
            
            $this->set('profile', $User->profilePage($this->user->id));
            
            $this->set('upcomingparties', $Party->findNextParties());
            $this->set('allparties', $allparties);
            
            $this->set('devices', $devices); 
            $this->set('weights', $weights);
            
            $this->set('device_count_status', $Device->statusCount());            
            
            
            // more stats...
            
            /** co2 counters **/
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
            
            
            $co2Total = $Device->getWeights();
            $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));
            
            $this->set('co2Total', $co2Total[0]->total_footprints);
            $this->set('co2ThisYear', $co2ThisYear[0]->co2);
            
            $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));
            
            $this->set('wasteTotal', $co2Total[0]->total_weights);
            $this->set('wasteThisYear', $wasteThisYear[0]->waste);
            
            
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
            $this->set('clusters', $clusters);
            
            // most/least stats for clusters
            $mostleast = array();
            for($i = 1; $i <= 4; $i++){
                $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i);
                $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i);
                $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i);
                
            }
            
            $this->set('mostleast', $mostleast);
            $this->set('top', $Device->findMostSeen(1, null, null)); 
        }
    
        public function eventsCsv(){
            
            $this->set('title', 'Administrator Dashboard');
            $this->set('charts', true);
            
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
                }
                    
                $this->set('response', $response);    
            }
            
            //Object Instances
            $Group = new Group;
            $User = new User;
            $Party = new Party;
            $Device = new Device;
            
            
            $this->set('grouplist', $Group->findList());
            
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
                
                $participants += $party->pax;
                $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);
                
                foreach($party->devices as $device){
                    
                    
                    
                    switch($device->repair_status){
                        case 1:
                            $party->co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);
                            $party->ewaste  += (!empty($device->estimate) && $device->category==46 ? $device->estimate : $device->weight);
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
            $this->set('pax', $participants);
            $this->set('hours', $hours_volunteered);
            
            $weights = $Device->getWeights();
            $devices = $Device->ofAllGroups();
            
            $this->set('showbadges', $Device->guesstimates());
            
            $this->set('need_attention', $need_attention);
            
            $this->set('profile', $User->profilePage($this->user->id));
            
            $this->set('upcomingparties', $Party->findNextParties());
            $this->set('allparties', $allparties);
            
            $this->set('devices', $devices); 
            $this->set('weights', $weights);
            
            $this->set('device_count_status', $Device->statusCount());            
            
            
            // more stats...
            
            /** co2 counters **/
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
            
            
            $co2Total = $Device->getWeights();
            $co2ThisYear = $Device->countCO2ByYear(null, date('Y', time()));
            
            $this->set('co2Total', $co2Total[0]->total_footprints);
            $this->set('co2ThisYear', $co2ThisYear[0]->co2);
            
            $wasteThisYear = $Device->countWasteByYear(null, date('Y', time()));
            
            $this->set('wasteTotal', $co2Total[0]->total_weights);
            $this->set('wasteThisYear', $wasteThisYear[0]->waste);
            
            
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
            $this->set('clusters', $clusters);
            
            // most/least stats for clusters
            $mostleast = array();
            for($i = 1; $i <= 4; $i++){
                $mostleast[$i]['most_seen'] = $Device->findMostSeen(null, $i);
                $mostleast[$i]['most_repaired'] = $Device->findMostSeen(1, $i);
                $mostleast[$i]['least_repaired'] = $Device->findMostSeen(3, $i);
                
            }
            
            $this->set('mostleast', $mostleast);
            $this->set('top', $Device->findMostSeen(1, null, null)); 
        }
        
    }
    
