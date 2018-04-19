<?php

    class RoleController extends Controller {
        
        protected $user;
        
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
            if(hasRole($this->user, 'Administrator')){ 
                $this->set('title', 'Roles');
                $this->set('roleList', $this->Role->findAll());
            }
        }
        
        public function edit($id){
            if(hasRole($this->user, 'Administrator')){ 
                
                if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
                
                    $permissions = $_POST['permissions'] ;
                    $formid = (int)substr(strrchr($_POST['formId'], '_'), 1);
                    
                    $update = $this->Role->edit($formid, $permissions);
                    if(!$update) {
                        $response['danger'] = 'Something went wrong. Could <strong>not</strong> update the permissions.';
                    }
                    else {
                        $response['success'] = 'Permissions for this Role have been updated.';
                    }
                    $this->set('response', $response);
                    
                }
                
                
                $role = $this->Role->findOne($id);
                $this->set('title', 'Edit <span class="orange">' . $role->role . '</span> Role');
                
                $permissionsList = $this->Role->rolePermissions($role->idroles);
                $activePerms = array();
                foreach($permissionsList as $p){
                    $activePerms[] = $p->permission;
                }
                
                $this->set('formId', $role->idroles);                 
                $this->set('permissions', $this->Role->permissions());
                $this->set('activePermissions', $activePerms);
                
            }
        }
        
    }
    