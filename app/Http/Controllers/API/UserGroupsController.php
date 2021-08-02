<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use App\UserGroups;
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
        if (! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $dateFrom = $request->input('date_from', null);

        $userGroupAudits = self::getUserGroupAudits($dateFrom);

        $userGroupChanges = [];
        foreach ($userGroupAudits as $audit) {
            $userGroupAssociation = UserGroups::withTrashed()->find($audit->auditable_id);
            if (! is_null($userGroupAssociation) && $userGroupAssociation->isConfirmed()) {
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
        $query = \OwenIt\Auditing\Models\Audit::where('auditable_type', \App\UserGroups::class);

        if (! is_null($dateFrom)) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $query->groupBy('event', 'created_at')
              ->orderBy('created_at', 'desc');

        return $query->get();
    }

    protected static function mapDetailsAndAuditToChange($userGroupAssociation, $audit)
    {
        $auditCreatedAtAsString = $audit->created_at->toDateTimeString();

        $userGroupAssociation->makeHidden(['role', 'status', 'user', 'group', 'deleted_at']);
        $userGroupChange = $userGroupAssociation->toArray();

        $userGroupChange['id'] = md5($userGroupAssociation->id.$auditCreatedAtAsString);
        $userGroupChange['change_type'] = $audit->event;
        $userGroupChange['change_occurred_at'] = $auditCreatedAtAsString;

        $userGroupChange['user_id'] = $userGroupAssociation->user;
        $userGroupChange['user_email'] = User::find($userGroupAssociation->user)->email;
        $role = Role::find($userGroupAssociation->role);
        if (! is_null($role)) {
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

    /**
     * Leave the specified group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function leave(Request $request, $id)
    {
        $authenticatedUser = Auth::user();
        if (! $authenticatedUser) {
            return abort(403, 'Not logged in');
        }

        $member = UserGroups::where('group', $id)
            ->where('user', $authenticatedUser->id)
            ->where('status', 1)
            ->first();

        if (! $member) {
            abort(404, 'Not a member');
        }

        $member->delete();

        $group = Group::where('idgroups', $id)->first();

        return response()->json([
            'success' => true,
            'all_restarters_count' => $group->all_restarters_count,
            'all_hosts_count' => $group->all_hosts_count,
        ], 200);
    }
}
