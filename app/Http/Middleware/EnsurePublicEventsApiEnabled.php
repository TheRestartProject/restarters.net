<?php

namespace App\Http\Middleware;

use Closure;

class EnsurePublicEventsApiEnabled
{
    public function handle($request, Closure $next)
    {
        if (! config('restarters.features.public_events_api', false)) {
            abort(404);
        }

        return $next($request);
    }
}
