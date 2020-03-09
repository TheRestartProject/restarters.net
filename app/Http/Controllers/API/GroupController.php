<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Http\Controllers\Controller;

use Auth;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * List changes made to groups.
     * Makes use of the audits produced by Laravel audits.
     *
     * Created specifically for use as a Zapier trigger.
     *
     * Only Administrators can access this API call.
     */
    public static function getGroupChanges(Request $request)
    {
        $authenticatedUser = Auth::user();
        if ( ! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $dateFrom = $request->input('date_from', null);

        $groupAudits = self::getGroupAudits($dateFrom);


        $groupChanges = [];
        foreach ($groupAudits as $groupAudit) {
            $group = Group::find($groupAudit->auditable_id);
            if (! is_null($group) ) {
                $groupChanges[] = self::mapDetailsAndAuditToChange($group, $groupAudit);
            }
        }

        return response()->json($groupChanges);
    }


    /**
     * To provide a more uniform API, this is just a wrapper around
     * the method in the GroupController for now.
     *
     * That method should be moved out of the controller.
     *
     */
    public static function getGroupsByUserGroupTag(Request $request)
    {
        $authenticatedUser = Auth::user();

        $groupController = new \App\Http\Controllers\GroupController();

        $groups = $groupController->getGroupsByKey($request, $authenticatedUser->api_token);

        return response()->json($groups);
    }


    /**
     * Get all of the audits related to groups from the audits table.
     *
     */
    public static function getGroupAudits($dateFrom = null)
    {
        $query = \OwenIt\Auditing\Models\Audit::where('auditable_type', 'App\\Group');

        if (!is_null($dateFrom)) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $query->groupBy('created_at')
              ->orderBy('created_at', 'desc');

        return $query->get();
    }


    /**
     * Map from the group and audit information as recorded by the audits library,
     * into the format needed for Zapier.
     */
    public static function mapDetailsAndAuditToChange($group, $groupAudit)
    {
        $group->makeHidden(['updated_at', 'wordpress_post_id', 'ShareableLink', 'shareable_code']);
        $groupChange = $group->toArray();

        // Zapier makes use of this unique hash as an id for the change for deduplication.
        $auditCreatedAtAsString = $groupAudit->created_at->toDateTimeString();
        $groupChange['id'] = md5($group->idgroups . $auditCreatedAtAsString);
        $groupChange['group_id'] = $group->idgroups;
        $groupChange['change_occurred_at'] = $auditCreatedAtAsString;
        $groupChange['change_type'] = $groupAudit->event;

        return $groupChange;
    }


    public static function getGroupList()
    {
        $groups = Group::orderBy('created_at', 'desc');

        $groups = $groups->get();
        foreach ($groups as $group) {
                mb_convert_encoding($group, 'UTF-8', 'UTF-8');
        }

        return response()->json($groups);
    }
}
