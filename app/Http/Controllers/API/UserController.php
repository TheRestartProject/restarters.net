<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Http\Controllers\Controller;

use Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public static function changes()
    {
        $user = Auth::user();
        if ( ! $user->hasRole('Administrator')) {
            return abort(403, 'The authenticated user is not authorized to access this resource');
        }

        $userAudits = \OwenIt\Auditing\Models\Audit::where('auditable_type', 'App\\User')->groupBy('event', 'created_at')->orderBy('created_at', 'desc')->get();

        $userChanges = [];
        foreach ($userAudits as $audit) {
            $user = User::withTrashed()->find($audit->auditable_id);
            if ( ! is_null($user)) {
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

                $auditCreatedAtAsString = $audit->created_at->toDateTimeString();

                $userChange['user_id'] = $user->id;
                $userChange['id'] = md5($user->id . $auditCreatedAtAsString);
                $userChange['role'] = $user->role()->first()->role;

                $userChange['change_occurred_at'] = $auditCreatedAtAsString;
                $userChange['change_type'] = $audit->event;

                $userChanges[] = $userChange;
            }
        }

        return response()->json($userChanges);
    }
}
