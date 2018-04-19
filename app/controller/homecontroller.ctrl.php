<?php

    class HomeController extends Controller {
        
        
        public function index(){
            $this->set('charts', true);
            $this->set('gmaps', true);
            $this->set('js', 
                    array('head' => array(
                        '/ext/markers.js'
            )));

            
            $Devices    = new Device;
            $Parties    = new Party;
            $Groups     = new Group;
            
            $devices = array();
            $devices['fixed'] = $Devices->howMany(array('repair_status' => 1));
            $devices['repairable'] = $Devices->howMany(array('repair_status' => 2));
            $devices['dead'] = $Devices->howMany(array('repair_status' => 3));
            
            $groups = $Groups->findAll();
            $parties = $Parties->findLatest(7);
            
            $this->set('devices', $devices);
            $this->set('parties', $parties);
            $this->set('groups', $groups);
        }
    }
    