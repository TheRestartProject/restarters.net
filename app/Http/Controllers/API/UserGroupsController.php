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
    public static function changes(Request $request)
    {
        $authenticatedUser = Auth::user();
        if ( ! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $dateFrom = $request->input('date_from', null);

        $userGroupAudits = self::getUserGroupAudits($dateFrom);

        $userGroupChanges = [];
        foreach ($userGroupAudits as $audit) {
            $userGroupAssociation = UserGroups::withTrashed()->find($audit->auditable_id);
            if ( ! is_null($userGroupAssociation) && $userGroupAssociation->isConfirmed()) {
                $user = $userGroupAssociation->volunteer;
                $group = Group::find($userGroupAssociation->group);
                if ($user->changesShouldPushToZapier() && $group->changesShouldPushToZapier()) {
                    $userGroupChanges[] = self::mapDetailsAndAuditToChange($userGroupAssociation, $audit);
                }
            }
        }

        return response()->json($userGroupChanges);
    }

    protected static function getUserGroupAudits($dateFrom = null)
    {
        $query = \OwenIt\Auditing\Models\Audit::where('auditable_type', 'App\\UserGroups');

        if (!is_null($dateFrom)) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $query->groupBy('event', 'created_at')
              ->orderBy('created_at', 'desc');

        return $query->get();
    }

    protected static function mapDetailsAndAuditToChange($userGroupAssociation, $audit)
    {
        $auditCreatedAtAsString = $audit->created_at->toDateTimeString();

        $userGroupAssociation->makeHidden(['role', 'status','user','group','deleted_at']);
        $userGroupChange = $userGroupAssociation->toArray();

        $userGroupChange['id'] = md5($userGroupAssociation->id . $auditCreatedAtAsString);
        $userGroupChange['change_type'] = $audit->event;
        $userGroupChange['change_occurred_at'] = $auditCreatedAtAsString;

        $userGroupChange['user_id'] = $userGroupAssociation->user;
        $userGroupChange['user_email'] = User::find($userGroupAssociation->user)->email;
        $role = Role::find($userGroupAssociation->role);
        if ( ! is_null($role)) {
            $userGroupChange['role'] = $role->role;
        } else {
            $userGroupChange['role'] = 'Unknown';
        }

        $userGroupChange['group_id'] = $userGroupAssociation->group;
        $group = Group::find($userGroupAssociation->group);
        $userGroupChange['group_name'] = $group->name;
        $userGroupChange['group_area'] = $group->area;
        $userGroupChange['group_country'] = $group->country;

        return $userGroupChange;
    }
}
