<?php

    class RssController extends Controller {
        
        
        
        public function parties() {
            $Parties = new Party;
            $this->set('parties', $Parties->findAll());
            
        }
        
        public function groups() {
            $Groups = new Group;
            $this->set('groups', $Groups->findAll());
            
        }
        
    }
    