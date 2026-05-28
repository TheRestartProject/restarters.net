<?php

namespace App\Http\Controllers\API;

use App\Helpers\Fixometer;
use App\Http\Controllers\Controller;
use App\Http\Resources\Permission;
use App\Http\Resources\RoleAdmin;
use App\Role;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoleController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/roles",
     *      operationId="listRolesv2",
     *      tags={"Roles"},
     *      summary="List roles with their permissions",
     *      description="Administrator only. Returns each role together with the IDs of its granted permissions and a comma-separated display string.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RoleAdmin"))
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function listRolesv2(): JsonResponse
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $rows = (new Role)->findAll();
        $permsByRole = $this->permissionsByRole();

        $data = array_map(function ($row) use ($permsByRole) {
            return (new RoleAdmin([
                'id' => $row->id,
                'name' => $row->role,
                'permissions' => $permsByRole[$row->id] ?? [],
                'permissions_list' => $row->permissions_list ?? '',
            ]))->toArray(request());
        }, $rows);

        return response()->json(['data' => $data]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/roles/{id}",
     *      operationId="getRolev2",
     *      tags={"Roles"},
     *      summary="Get a single role",
     *      description="Administrator only.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/RoleAdmin"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Role not found")
     * )
     */
    public function getRolev2($id): JsonResponse
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $role = $this->findRoleOr404($id);
        $permissions = array_map(
            fn ($p) => (int) $p->idpermissions,
            (new Role)->rolePermissions($role->idroles)
        );

        $names = DB::select(
            'SELECT GROUP_CONCAT(permission ORDER BY permission SEPARATOR ", ") AS lst
               FROM permissions
              WHERE idpermissions IN (' . (count($permissions) ? implode(',', array_fill(0, count($permissions), '?')) : 'NULL') . ')',
            $permissions
        );
        $list = $names && isset($names[0]->lst) ? (string) $names[0]->lst : '';

        return response()->json([
            'data' => (new RoleAdmin([
                'id' => $role->idroles,
                'name' => $role->role,
                'permissions' => $permissions,
                'permissions_list' => $list,
            ]))->toArray(request()),
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/permissions",
     *      operationId="listPermissionsv2",
     *      tags={"Roles"},
     *      summary="List all permissions",
     *      description="Administrator only. Used to populate the role permission matrix.",
     *      security={{"apiToken":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Permission"))
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function listPermissionsv2(): JsonResponse
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $rows = DB::select('SELECT idpermissions AS id, permission AS name FROM permissions ORDER BY idpermissions ASC');
        $data = array_map(
            fn ($r) => (new Permission(['id' => $r->id, 'name' => $r->name]))->toArray(request()),
            $rows
        );

        return response()->json(['data' => $data]);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/roles/{id}/permissions",
     *      operationId="updateRolePermissionsv2",
     *      tags={"Roles"},
     *      summary="Replace the permissions granted to a role",
     *      description="Administrator only. Sends the full set of permission IDs; the server replaces the role's grants atomically.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"permissions"},
     *              @OA\Property(
     *                  property="permissions",
     *                  type="array",
     *                  @OA\Items(type="integer"),
     *                  example={4, 6}
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Permissions replaced",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/RoleAdmin"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Role not found"),
     *      @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function updateRolePermissionsv2(Request $request, $id): JsonResponse
    {
        if (!Fixometer::hasRole(Auth::user(), 'Administrator')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $role = $this->findRoleOr404($id);

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,idpermissions'],
        ]);

        $ok = (new Role)->edit($role->idroles, array_map('intval', $validated['permissions']));
        if (!$ok) {
            return response()->json(['message' => 'Could not update permissions'], 500);
        }

        return $this->getRolev2($role->idroles);
    }

    private function findRoleOr404($id): Role
    {
        $role = Role::where('idroles', $id)->first();
        if (!$role) {
            throw new NotFoundHttpException('Role not found.');
        }
        return $role;
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
