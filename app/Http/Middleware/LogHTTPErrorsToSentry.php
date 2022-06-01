<?php

namespace App\Http\Middleware;

use Closure;

class LogHTTPErrorsToSentry {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $httpCode = $response->getStatusCode();

        if ($httpCode >= 400 && $httpCode < 600) {
            // Log all 4xx and 5xx errors to Sentry.  Once we do that we'll find out if there are any we should
            // suppress.
            \Sentry\CaptureMessage("HTTP return code $httpCode");
        }

        return $response;
    }
}