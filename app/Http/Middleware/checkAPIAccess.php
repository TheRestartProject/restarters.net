<?php

namespace App\Http\Middleware;

use App\User;
use Closure;

class checkAPIAccess
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
        // dd($request->route('apikey'));

        if ($request->apikey == '') {
            return redirect('/');
        }

        $users = User::where('access_key', $request->apikey)->count();

        if ($users != 1) {
            return response('Invalid access key');
        }

        return $next($request);
    }
}
