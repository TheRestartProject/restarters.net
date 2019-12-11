<?php

namespace App\Http\Controllers\API;

use App\Group;
use App\Http\Controllers\Controller;

use Auth;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public static function getGroupChanges()
    {
        $authenticatedUser = Auth::user();
        if ( ! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $groupAudits = self::getGroupAudits();

        $groupChanges = [];
        foreach ($groupAudits as $groupAudit) {
            $group = Group::find($groupAudit->auditable_id);
            if (! is_null($group) ) {
                $groupChanges[] = self::mapDetailsAndAuditToChange($group, $groupAudit);
            }
        }

        return response()->json($groupChanges);
    }

    public static function getGroupAudits()
    {
        return \OwenIt\Auditing\Models\Audit::where('auditable_type', 'App\\Group')
            ->groupBy('created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

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
