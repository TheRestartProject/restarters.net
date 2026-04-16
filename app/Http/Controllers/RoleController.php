<?php

namespace App\Http\Controllers;

use App\Attributes\Feature;
use App\Attributes\UserStory;
use App\Helpers\Fixometer;
use App\Providers\RouteServiceProvider;
use App\Role;
use App\RolePermissions;
use App\User;
use Auth;
use Illuminate\Http\Request;

#[Feature('Administration', description: 'Platform administration and configuration')]
class RoleController extends Controller
{
    //Custom Functions
    #[UserStory('As an Admin, I can view all roles and their permissions', persona: 'Admin', theme: 'Roles & permissions')]
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

        return redirect(RouteServiceProvider::HOME);
    }

    #[UserStory('As an Admin, I can edit the permissions assigned to a role', persona: 'Admin', theme: 'Roles & permissions')]
    public function edit($id, Request $request)
    {
        $user = Auth::user();

        if (Fixometer::hasRole($user, 'Administrator')) {
            $role = Role::where('idroles', $id)->first();

            if ($request->getMethod() == 'POST') {
                $permissions = $request->get('permissions');
                $formid = (int) substr(strrchr($request->get('formId'), '_'), 1);

                $update = $role->edit($formid, $permissions);
                if (! $update) {
                    $response['danger'] = 'Something went wrong. Could <strong>not</strong> update the permissions.';
                    \Sentry\CaptureMessage($response['danger']);
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
}
