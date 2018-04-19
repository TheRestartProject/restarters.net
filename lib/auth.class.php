<?php
    
    /**
     * Authorizes URL and checks for users being Logged In
     * Validates and authorizes user sessions
     * */
    
    class Auth extends Session {
        
        public $url;
        protected $authorized = false;
        protected $openroutes = array(
                                'user/login',
                                'home',
                                
                                );
        
        public function checkRoute($url){
            
            $this->url = $url;
            if(in_array($url, $this->openroutes)){
                $this->authorized = true;
                return true;
            }
            elseif($this->isLoggedIn()){
                $this->authorized = true;
                return true;
            }
            else {
                $this->authorized = false;
                return false;
            }
        }
        
        
        public function isLoggedIn(){
            if(isset($_SESSION[APPNAME][SESSIONKEY]) && !empty($_SESSION[APPNAME][SESSIONKEY])){
                $this->authorized = true;                
                return true;
            }
            else {
                $this->authorized = false;
                return false; 
            }
        }
        
       
        public function authorize($user){
            // remember: use crypt($input, $crypted) == $crypted to verify if passwords match
            $Session = new Session;
            $token = $this->token();
            $sessionset = $Session->setSession($user, $token);
            
            $this->authorized = true;
            
            return $sessionset;
        }
        
        private function token(){
            $salt = '$1$' . md5(substr(time(), -8))  . SESSIONSALT;
            return crypt($token, $salt);
        }
        
        public function getProfile(){
            $session = $this->getSession();
            return $session; 
        }
        
        
    }