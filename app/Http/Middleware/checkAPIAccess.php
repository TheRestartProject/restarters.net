<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Http\Request;

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
        if ($request->api_token == '') {
            return abort(404, 'Requires api token.');
        }

        $check_apitoken_exists = User::where('api_token', $request->api_token)
        ->exists();

        if (! $check_apitoken_exists) {
            return abort(404, 'Invalid api token.');
        }

        return $next($request);
    }
}
