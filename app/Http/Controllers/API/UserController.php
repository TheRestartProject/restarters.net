<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * List recent changes made to users.
     * Makes use of the audits produced by Laravel audits.
     *
     * Created specifically for use as a Zapier trigger.
     *
     * Only Administrators can access this API call.
     */
    public static function changes(Request $request)
    {
        $authenticatedUser = Auth::user();
        if (! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $dateFrom = $request->input('date_from', null);

        $userAudits = self::getUserAudits($dateFrom);

        $userChanges = [];
        foreach ($userAudits as $userAudit) {
            $user = User::withTrashed()->find($userAudit->auditable_id);
            if (! is_null($user) && $user->changesShouldPushToZapier()) {
                $userChanges[] = self::mapUserAndAuditToUserChange($user, $userAudit);
            }
        }

        return response()->json($userChanges);
    }

    protected static function getUserAudits($dateFrom = null)
    {
        $query = \OwenIt\Auditing\Models\Audit::where('auditable_type', \App\User::class);

        if (! is_null($dateFrom)) {
            $query->where('created_at', '>=', $dateFrom);
        }

        $query->groupBy('event', 'created_at', 'auditable_id')
              ->orderBy('created_at', 'desc');

        return $query->get();
    }

    protected static function mapUserAndAuditToUserChange($user, $audit)
    {
        // Hide fields not relevant for Zapier.
        $user->makeHidden([
            'updated_at',
            'deleted_at',
            'api_token',
            'drip_subscriber_id',
            'recovery',
            'recovery_expires',
            'calendar_hash',
            'number_of_logins',
            'consent_past_data',
            'consent_gdpr',
            'consent_future_data',
            'access_group_tag_id',
            'mediawiki',
            'wiki_sync_status',
        ]);

        $userChange = $user->toArray();
        $userChange['talk_profile_url'] = $user->getTalkProfileUrl();

        $auditCreatedAtAsString = $audit->created_at->toDateTimeString();

        $userChange['user_id'] = $user->id;
        $userChange['id'] = md5($user->id.$auditCreatedAtAsString);
        $userChange['role'] = $user->role()->first()->role;

        $userChange['change_occurred_at'] = $auditCreatedAtAsString;
        $userChange['change_type'] = $audit->event;

        return $userChange;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $authenticatedUser = Auth::user();
        if (! $authenticatedUser->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to update this resource');
        }

        $user = User::find($id);
        if ($user === null) {
            abort(404, 'Resource not found');
        }

        $changesMade = false;

        if ($request->has('username')) {
            $user->username = $request->input('username');
            $changesMade = true;
        }

        if ($changesMade) {
            $user->save();
        }

        // Zapier seems to require some response body.
        return response()->json(['success' => 'success'], 200);
    }
}
