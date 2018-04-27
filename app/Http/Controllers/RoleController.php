<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Role;
use App\RolePermissions;

use Auth;
use FixometerHelper;

class RoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct($model, $controller, $action)
    {
        parent::__construct($model, $controller, $action);

        $this->middleware('auth');

        $user = Auth::getProfile();
        $this->user = $user;
        $this->set('user', $user);
        $this->set('header', true);
    }

    //Custom Functions
    public function index() {
        if(FixometerHelper::hasRole($this->user, 'Administrator')){
            //Send user to roles page
            // $this->set('title', 'Roles');
            // $this->set('roleList', $this->Role->findAll());
        }
    }

    public function edit($id){
        if(FixometerHelper::hasRole($this->user, 'Administrator')){

            $role = Role::find($id);

            if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){

                $permissions = $_POST['permissions'] ;
                $formid = (int)substr(strrchr($_POST['formId'], '_'), 1);

                $update = $role->edit($formid, $permissions);
                if(!$update) {
                    $response['danger'] = 'Something went wrong. Could <strong>not</strong> update the permissions.';
                }
                else {
                    $response['success'] = 'Permissions for this Role have been updated.';
                }
                $this->set('response', $response);

            }

            $this->set('title', 'Edit <span class="orange">' . $role->role . '</span> Role');

            $permissionsList = $role->rolePermissions($role->idroles);
            $activePerms = array();
            foreach($permissionsList as $p){
                $activePerms[] = $p->permission;
            }

            $this->set('formId', $role->idroles);
            $this->set('permissions', $role->permissions());
            $this->set('activePermissions', $activePerms);

        }
    }

    public function test() {
      $r = new Role;
      dd($r->permissions());//NB::findAll on Role isn't working
    }
}
