<?php

    class DashboardController extends Controller {
        
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
            }
        }
        
        
        public function index() {
            /** js setup **/
            $this->set('gmaps', true);
            
            if(hasRole($user, 'Host')){
                self::hostdashboard();
            }
            
            /*
            $this->set('title', 'Dashboard');
            $this->set('charts', true);
            
            $Parties    = new Party;
            $Devices    = new Device;
            $Groups     = new Group;
            
            
            $this->set('upcomingParties', $Parties->findNextParties());
            
            $devicesByYear = array();
            for( $i = 1; $i < 4; $i++ ){
                
                $devices = $Devices->getByYears($i);
                $deviceList = array();
                foreach( $devices as $listed ) {
                    $deviceList[$listed->event_year] = $listed->total_devices;
                }
                $devicesByYear[$i] = $deviceList;
                
            }
            $this->set('devicesByYear', $devicesByYear);
            */
        }
        
        
        public function hostdashboard(){
            
        }
    }
    