<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Helpers\Fixometer;
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
     *
     * @OA\Get(
     *      path="/api/usersgroups/changes",
     *      operationId="getUserGroupChanges",
     *      tags={"UserGroups"},
     *      summary="List confirmed group-membership changes",
     *      description="Administrator only. Used by Zapier as a trigger. Built from the audit log for App\UserGroups, restricted to confirmed (status=1) memberships whose user and group both still opt in to Zapier pushes (User/Group::changesShouldPushToZapier()). Pending invitations are not included.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(
     *          name="date_from",
     *          description="Only include audit events created on or after this date/time. Omit for all history.",
     *          required=false,
     *          in="query",
     *          @OA\Schema(type="string", format="date-time")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Membership changes, most recently audited first",
     *          @OA\JsonContent(type="array", @OA\Items(
     *              @OA\Property(property="idusers_groups", type="integer", description="Primary key of the users_groups row"),
     *              @OA\Property(property="id", type="string", description="md5 hash of idusers_groups + change_occurred_at; unique per change record"),
     *              @OA\Property(property="change_type", type="string", description="Audit event type, e.g. created/updated/deleted", example="updated"),
     *              @OA\Property(property="change_occurred_at", type="string", format="date-time"),
     *              @OA\Property(property="user_id", type="integer"),
     *              @OA\Property(property="user_email", type="string"),
     *              @OA\Property(property="role", type="string", description="Role name for the membership, or 'Unknown' if the role record no longer exists"),
     *              @OA\Property(property="group_id", type="integer"),
     *              @OA\Property(property="group_name", type="string"),
     *              @OA\Property(property="group_area", type="string"),
     *              @OA\Property(property="group_country", type="string")
     *          ))
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated"),
     *      @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
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
        $userGroupChange['group_country'] = Fixometer::getCountryFromCountryCode($group->country_code);

        return $userGroupChange;
    }

    /**
     * Leave the specified group.
     *
     * @OA\Delete(
     *      path="/api/usersgroups/{id}",
     *      operationId="leaveGroupLegacy",
     *      tags={"UserGroups"},
     *      summary="Leave a group (legacy endpoint)",
     *      description="Legacy - despite the routes/api.php comment, the Nuxt client actually uses DELETE /api/v2/groups/{id}/members/me (GroupMembershipController::leavev2). Kept for backwards compatibility. Idempotent: if the authenticated user has no confirmed membership of the group (e.g. already left), this still returns a 200 success response rather than an error.",
     *      security={{"apiToken":{}}},
     *      @OA\Parameter(name="id", description="Group id", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Left (or was already not a member)",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="all_restarters_count", type="integer", description="Group's restarter-count column after the change"),
     *              @OA\Property(property="all_hosts_count", type="integer", description="Group's host-count column after the change")
     *          )
     *      ),
     *      @OA\Response(response=401, ref="#/components/responses/Unauthenticated")
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function leave(Request $request, int $id)
    {
        $authenticatedUser = Auth::user();
        if (! $authenticatedUser) {
            return abort(403, 'Not logged in');
        }

        $member = UserGroups::where('group', $id)
            ->where('user', $authenticatedUser->id)
            ->where('status', 1)
            ->first();

        // If we don't find the membership, it could be because they have never been a member, but it could
        // also be because they have just left.  We've seen this in Sentry on the live system, perhaps due to
        // double-clicking or user error.  In this case it's better to return success.
        if ($member) {
            $member->delete();
        }

        $group = Group::where('idgroups', $id)->first();

        return response()->json([
            'success' => true,
            'all_restarters_count' => $group->all_restarters_count,
            'all_hosts_count' => $group->all_hosts_count,
        ], 200);
    }
}
