<?php

namespace App\Http\Controllers;

use App\Role;
use App\RolePermissions;
use App\User;
use Auth;
use App\Helpers\Fixometer;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    //Custom Functions
    public function index()
    {
        $user = User::find(Auth::id());

        if (Fixometer::hasRole($user, 'Administrator')) {
            //Send user to roles page
            // $this->set('title', 'Roles');
            // $this->set('roleList', $this->Role->findAll());

            $Role = new Role;

            return view('role.all', [//role.index
              'title' => 'Roles',
              'roleList' => $Role->findAll(),
            ]);
        }
    }

    public function edit($id)
    {
        $user = Auth::user();

        if (Fixometer::hasRole($user, 'Administrator')) {
            $role = Role::where('idroles', $id)->first();

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST)) {
                $permissions = $_POST['permissions'];
                $formid = (int) substr(strrchr($_POST['formId'], '_'), 1);

                $update = $role->edit($formid, $permissions);
                if (! $update) {
                    $response['danger'] = 'Something went wrong. Could <strong>not</strong> update the permissions.';
                } else {
                    $response['success'] = 'Permissions for this Role have been updated.';
                }
            }

            $permissionsList = $role->rolePermissions($role->idroles);
            $activePerms = [];
            foreach ($permissionsList as $p) {
                $activePerms[] = $p->permission;
            }

            if (! isset($response)) {
                $response = null;
            }

            return view('role.edit', [
              'response' => $response,
              'title' => 'Edit <span class="orange">'.$role->role.'</span> Role',
              'formId' => $role->idroles,
              'permissions' => $role->permissions(),
              'activePermissions' => $activePerms,
              'role_name' => $role->role,
            ]);
        }
    }

    // public function test() {
    //   $r = new Role;
    //   dd($r->permissions());//NB::findAll on Role isn't working
    // }
}
