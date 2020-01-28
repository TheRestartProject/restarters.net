<?php

namespace App\Http\Middleware;

use \Illuminate\Http\Request;
use App\User;
use Closure;

class checkAPIAccess
{

    /**
     * [handle description]
     * Handle an incoming API Key Request.
     * Fail if the Request does not contain a API Key
     * and if the Request contains a API Key but does not match,
     * Else proceed to the next Request.
     *
     * @author  Christopher Kelker
     * @version 1.0.0
     * @date    2019-03-14
     * @param   Request    $request
     * @param   Closure    $next
     * @return  [type]
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->api_key == '') {
            return abort(404, 'Requires api key.');
        }

        $check_apikey_exists = User::where('api_key', $request->api_key)
        ->exists();

        if ( ! $check_apikey_exists) {
            return abort(404, 'Invalid api key.');
        }

        return $next($request);
    }
}
