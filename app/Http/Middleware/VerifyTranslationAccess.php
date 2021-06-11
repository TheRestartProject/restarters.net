<?php

namespace App\Http\Middleware;

use Auth;
use App\User;
use Closure;
use FixometerHelper;

class VerifyTranslationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $has_permission = User::join('users_permissions', 'users_permissions.user_id', '=', 'users.id')
                                  ->join('permissions', 'permissions.idpermissions', '=', 'users_permissions.permission_id')
                                    ->where('users.id', Auth::user()->id)
                                      ->where('permissions.slug', 'verify-translation-access')
                                        ->select('users.*')
                                          ->first();

        if (!empty($has_permission)) {
            return $next($request);
        } else {
            abort(404);
        }
    }
}
