<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App\Helpers\Fixometer;
use App\User;
use Auth;
use Closure;

class VerifyTranslationAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $has_permission = User::join('users_permissions', 'users_permissions.user_id', '=', 'users.id')
                                  ->join('permissions', 'permissions.idpermissions', '=', 'users_permissions.permission_id')
                                    ->where('users.id', Auth::user()->id)
                                      ->where('permissions.slug', 'verify-translation-access')
                                        ->select('users.*')
                                          ->first();

        if (! empty($has_permission)) {
            return $next($request);
        } else {
            abort(404);
        }
    }
}
