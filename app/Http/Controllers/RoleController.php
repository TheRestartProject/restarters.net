<?php

namespace App\Http\Controllers;

use App\Helpers\Fixometer;
use App\Providers\RouteServiceProvider;
use App\Role;
use Auth;
use DB;

class RoleController extends Controller
{
    /**
     * Render the roles admin page (a Vue SPA that talks to /api/v2/roles
     * and /api/v2/permissions). The permission matrix lives in a modal.
     *
     * @param int|null $editId Optional role id to pre-open in the edit modal
     *                         (used by the legacy /role/edit/{id} bookmark).
     */
    public function index($editId = null)
    {
        $user = Auth::user();

        if (! Fixometer::hasRole($user, 'Administrator')) {
            return redirect(RouteServiceProvider::HOME);
        }

        $rolesRaw = (new Role)->findAll();
        $permsByRole = $this->permissionsByRole();

        $rolesForVue = array_map(function ($row) use ($permsByRole) {
            return [
                'id' => (int) $row->id,
                'name' => $row->role,
                'permissions' => $permsByRole[$row->id] ?? [],
                'permissions_list' => $row->permissions_list ?? '',
            ];
        }, $rolesRaw);

        $permissions = array_map(
            fn ($r) => ['id' => (int) $r->idpermissions, 'name' => $r->permission],
            (new Role)->permissions()
        );

        return view('role.all', [
            'title' => 'Roles',
            'rolesForVue' => $rolesForVue,
            'permissions' => $permissions,
            'apiToken' => $user->api_token,
            'editId' => $editId !== null ? (int) $editId : null,
        ]);
    }

    /**
     * One query → map of role id → list of permission ids granted to that role.
     */
    private function permissionsByRole(): array
    {
        $rows = DB::select('SELECT role, permission FROM roles_permissions');
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->role][] = (int) $r->permission;
        }
        return $out;
    }
}
