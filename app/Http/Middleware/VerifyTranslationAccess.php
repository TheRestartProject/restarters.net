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

        $has_preference = User::join('users_preferences', 'users_preferences.user_id', '=', 'users.id')
                                  ->join('preferences', 'preferences.id', '=', 'users_preferences.preference_id')
                                    ->where('users.id', Auth::user()->id)
                                      ->where('preferences.slug', 'verify-translation-access')
                                        ->select('users.*')
                                          ->first();
                                          
        if( !empty($has_preference) ){
          return $next($request);
        } else {
          abort(404);
        }

    }
}
