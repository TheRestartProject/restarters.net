<?php

namespace App\Http\Controllers\API;

use App\Group;
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
     */
    public static function changes()
    {
        $user = Auth::user();
        if ( ! $user->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $userGroupAudits = \OwenIt\Auditing\Models\Audit::where('auditable_type', 'App\\UserGroups')->groupBy('event', 'created_at')->orderBy('created_at', 'desc')->get();

        $userGroupChanges = [];
        foreach ($userGroupAudits as $audit) {
            $userGroupAssociation = UserGroups::withTrashed()->find($audit->auditable_id);
            $auditCreatedAtAsString = $audit->created_at->toDateTimeString();
            if ( ! is_null($userGroupAssociation) && $userGroupAssociation->isConfirmed()) {
                $userGroupAssociation->makeHidden(['role', 'status','user','group','deleted_at']);
                $userGroupChange = $userGroupAssociation->toArray();
                $userGroupChange['user_id'] = $userGroupAssociation->user;
                $userGroupChange['user_email'] = User::find($userGroupAssociation->user)->email;
                $userGroupChange['group_id'] = $userGroupAssociation->group;
                $userGroupChange['group_name'] = Group::find($userGroupAssociation->group)->name;
                $userGroupChange['id'] = md5($userGroupAssociation->id . $auditCreatedAtAsString);

                $userGroupChange['change_type'] = $audit->event;
                $userGroupChange['change_occurred_at'] = $auditCreatedAtAsString;
                $userGroupChanges[] = $userGroupChange;
            }
        }

        return response()->json($userGroupChanges);
    }
}
