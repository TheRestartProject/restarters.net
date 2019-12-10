<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Role;
use App\User;
use App\UserGroups;
use App\Http\Controllers\Controller;

use Auth;
use Illuminate\Http\Request;

class UserGroupsController extends Controller
{
    /**
     * Created as a trigger for Zapier.
     *
     * Only confirmed group memberships - pending invitations not pulled in.
     *
     * Only Administrators allowed to access this endpoint.
     */
    public static function changes()
    {
        $authenticatedUser = Auth::user();
        if ( ! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $userGroupAudits = self::getUserGroupAudits();

        $userGroupChanges = [];
        foreach ($userGroupAudits as $audit) {
            $userGroupAssociation = UserGroups::withTrashed()->find($audit->auditable_id);
            if ( ! is_null($userGroupAssociation) && $userGroupAssociation->isConfirmed()) {
                $userGroupChanges[] = self::mapDetailsAndAuditToChange($userGroupAssociation, $audit);
            }
        }

        return response()->json($userGroupChanges);
    }

    protected static function getUserGroupAudits()
    {
        return \OwenIt\Auditing\Models\Audit::where('auditable_type', 'App\\UserGroups')
            ->groupBy('event', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected static function mapDetailsAndAuditToChange($userGroupAssociation, $audit)
    {
        $auditCreatedAtAsString = $audit->created_at->toDateTimeString();

        $userGroupAssociation->makeHidden(['role', 'status','user','group','deleted_at']);
        $userGroupChange = $userGroupAssociation->toArray();

        $role = Role::find($userGroupAssociation->role);
        if ( ! is_null($role)) {
            $userGroupChange['role'] = $role->role;
        } else {
            $userGroupChange['role'] = 'Unknown';
        }
        $userGroupChange['user_id'] = $userGroupAssociation->user;
        $userGroupChange['user_email'] = User::find($userGroupAssociation->user)->email;
        $userGroupChange['group_id'] = $userGroupAssociation->group;
        $userGroupChange['group_name'] = Group::find($userGroupAssociation->group)->name;
        $userGroupChange['id'] = md5($userGroupAssociation->id . $auditCreatedAtAsString);

        $userGroupChange['change_type'] = $audit->event;
        $userGroupChange['change_occurred_at'] = $auditCreatedAtAsString;

        return $userGroupChange;
    }
}
